<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/db.php';
$page_title = 'Rentals';

$msg = '';
// Update reservation status (check-in / return)
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $act = $_POST['action'];
    $rid = (int)$_POST['reservation_id'];
    if ($act === 'checkin') {
        $conn->query("UPDATE reservations SET status='active' WHERE reservation_id=$rid");
        $conn->query("UPDATE motorcycles SET status='rented' WHERE motorcycle_id=(SELECT motorcycle_id FROM reservations WHERE reservation_id=$rid)");
        $sid = (int)$_SESSION['staff_id'];
        $cond = mysqli_real_escape_string($conn, $_POST['condition_out']??'Good condition');
        $conn->query("INSERT INTO rentals (reservation_id,staff_id,checkout_time,condition_out) VALUES ($rid,$sid,NOW(),'$cond')");
        $msg='success|Checked out successfully.';
    } elseif ($act === 'return') {
        $conn->query("UPDATE reservations SET status='returned' WHERE reservation_id=$rid");
        $conn->query("UPDATE motorcycles SET status='available' WHERE motorcycle_id=(SELECT motorcycle_id FROM reservations WHERE reservation_id=$rid)");
        $cond  = mysqli_real_escape_string($conn, $_POST['condition_in']??'Good condition');
        $extra = (float)($_POST['extra_charges']??0);
        $conn->query("UPDATE rentals SET return_time=NOW(), condition_in='$cond', extra_charges=$extra WHERE reservation_id=$rid");
        $msg='success|Motorcycle returned successfully.';
    } elseif ($act === 'cancel') {
        $conn->query("UPDATE reservations SET status='cancelled' WHERE reservation_id=$rid");
        $msg='success|Reservation cancelled.';
    }
}

$filter = $_GET['status'] ?? '';
$sql = "SELECT r.*, c.first_name, c.last_name, m.model, m.plate_number, m.type,
               p.status as pay_status, p.method
        FROM reservations r
        JOIN customers c ON r.customer_id=c.customer_id
        JOIN motorcycles m ON r.motorcycle_id=m.motorcycle_id
        LEFT JOIN payments p ON p.reservation_id=r.reservation_id
        WHERE 1=1";
if ($filter) $sql .= " AND r.status='".mysqli_real_escape_string($conn,$filter)."'";
$sql .= " ORDER BY r.created_at DESC";
$reservations = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
[$msg_type,$msg_text] = $msg ? explode('|',$msg,2) : ['',''];
?>
<div class="page-header">
  <div>
    <div class="page-title">Rentals</div>
    <div class="page-sub"><?= count($reservations) ?> reservations</div>
  </div>
  <a href="/motorent/pages/bookings.php" class="btn btn-primary">+ New booking</a>
</div>

<?php if($msg_text): ?><div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg_text) ?></div><?php endif; ?>

<div class="filter-bar">
  <input id="table-search" class="search-input" placeholder="Search by name, unit...">
  <a href="?" class="btn btn-sm <?= !$filter?'btn-primary':'' ?>">All</a>
  <a href="?status=confirmed" class="btn btn-sm <?= $filter==='confirmed'?'btn-primary':'' ?>">Confirmed</a>
  <a href="?status=active" class="btn btn-sm <?= $filter==='active'?'btn-primary':'' ?>">Active</a>
  <a href="?status=returned" class="btn btn-sm <?= $filter==='returned'?'btn-primary':'' ?>">Returned</a>
  <a href="?status=cancelled" class="btn btn-sm <?= $filter==='cancelled'?'btn-primary':'' ?>">Cancelled</a>
</div>

<div class="table-wrap">
<table>
  <thead><tr><th>#</th><th>Customer</th><th>Motorcycle</th><th>Dates</th><th>Total</th><th>Payment</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach($reservations as $r):
    $smap = ['confirmed'=>'info','active'=>'success','returned'=>'neutral','cancelled'=>'danger'];
    $pmap = ['paid'=>'success','pending'=>'warning','refunded'=>'info'];
    $overdue = $r['status']==='active' && $r['end_date'] < date('Y-m-d');
  ?>
  <tr>
    <td><strong>#<?= $r['reservation_id'] ?></strong></td>
    <td><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></td>
    <td><?= htmlspecialchars($r['model']) ?><br><span style="font-size:11px;color:var(--text3)"><?= $r['plate_number'] ?></span></td>
    <td><?= date('M j',strtotime($r['start_date'])) ?> – <?= date('M j',strtotime($r['end_date'])) ?></td>
    <td>₱<?= number_format($r['total_amount']) ?></td>
    <td><span class="badge badge-<?= $pmap[$r['pay_status']]??'neutral' ?>"><?= ucfirst($r['pay_status']??'—') ?></span><br><span style="font-size:11px;color:var(--text3)"><?= $r['method']??'' ?></span></td>
    <td>
      <span class="badge badge-<?= $smap[$r['status']]??'neutral' ?>"><?= ucfirst($r['status']) ?></span>
      <?php if($overdue): ?><br><span class="badge badge-danger" style="margin-top:2px">Overdue</span><?php endif; ?>
    </td>
    <td>
      <?php if($r['status']==='confirmed'): ?>
      <button class="btn btn-sm btn-primary" onclick="openModal('checkin-<?= $r['reservation_id'] ?>')">Check out</button>
      <form method="POST" style="display:inline"><input type="hidden" name="action" value="cancel"><input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>"><button class="btn btn-sm btn-danger" data-confirm="Cancel this reservation?">Cancel</button></form>
      <?php elseif($r['status']==='active'): ?>
      <button class="btn btn-sm" onclick="openModal('return-<?= $r['reservation_id'] ?>')">Return</button>
      <?php else: ?>
      <span style="color:var(--text3);font-size:12px">—</span>
      <?php endif; ?>
    </td>
  </tr>

  <!-- Check-out modal -->
  <?php if($r['status']==='confirmed'): ?>
  <tr><td colspan="8" style="padding:0;border:none">
  <div id="checkin-<?= $r['reservation_id'] ?>" style="display:none;background:var(--blue-bg);padding:14px 20px">
    <form method="POST" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
      <input type="hidden" name="action" value="checkin">
      <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
      <div class="form-group" style="flex:1;min-width:200px"><label>Condition on checkout</label><input name="condition_out" value="Good condition — no damage" style="font-size:12px"></div>
      <button type="submit" class="btn btn-primary btn-sm">Confirm checkout</button>
      <button type="button" class="btn btn-sm" onclick="closeModal('checkin-<?= $r['reservation_id'] ?>')">Cancel</button>
    </form>
  </div>
  </td></tr>
  <?php endif; ?>

  <!-- Return modal -->
  <?php if($r['status']==='active'): ?>
  <tr><td colspan="8" style="padding:0;border:none">
  <div id="return-<?= $r['reservation_id'] ?>" style="display:none;background:var(--green-bg);padding:14px 20px">
    <form method="POST" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
      <input type="hidden" name="action" value="return">
      <input type="hidden" name="reservation_id" value="<?= $r['reservation_id'] ?>">
      <div class="form-group" style="flex:1;min-width:200px"><label>Condition on return</label><input name="condition_in" value="Good condition — no damage" style="font-size:12px"></div>
      <div class="form-group" style="width:140px"><label>Extra charges (₱)</label><input name="extra_charges" type="number" step="0.01" value="0" style="font-size:12px"></div>
      <button type="submit" class="btn btn-primary btn-sm">Confirm return</button>
      <button type="button" class="btn btn-sm" onclick="closeModal('return-<?= $r['reservation_id'] ?>')">Cancel</button>
    </form>
  </div>
  </td></tr>
  <?php endif; ?>

  <?php endforeach; ?>
  </tbody>
</table>
</div>

<script>
function openModal(id) { document.getElementById(id).style.display=''; }
function closeModal(id) { document.getElementById(id).style.display='none'; }
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
