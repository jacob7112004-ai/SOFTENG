<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/db.php';
$page_title = 'Payments';

$msg='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $stmt = $conn->prepare("UPDATE payments SET status=? WHERE payment_id=?");
    $stmt->bind_param('si',$_POST['status'],$_POST['payment_id']);
    $stmt->execute();
    $msg='success|Payment status updated.';
}

$month_rev  = $conn->query("SELECT COALESCE(SUM(amount),0) v FROM payments WHERE MONTH(paid_at)=MONTH(CURDATE()) AND YEAR(paid_at)=YEAR(CURDATE()) AND status='paid'")->fetch_assoc()['v'];
$pending_amt= $conn->query("SELECT COALESCE(SUM(amount),0) v FROM payments WHERE status='pending'")->fetch_assoc()['v'];
$deposits   = $conn->query("SELECT COALESCE(SUM(r.deposit_amount),0) v FROM reservations r WHERE r.status IN ('confirmed','active')")->fetch_assoc()['v'];

$payments = $conn->query("SELECT p.*, r.reservation_id, c.first_name, c.last_name FROM payments p JOIN reservations r ON p.reservation_id=r.reservation_id JOIN customers c ON r.customer_id=c.customer_id ORDER BY p.paid_at DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
[$msg_type,$msg_text] = $msg ? explode('|',$msg,2) : ['',''];
?>
<div class="page-header">
  <div><div class="page-title">Payments</div><div class="page-sub">Transaction history</div></div>
</div>

<?php if($msg_text): ?><div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg_text) ?></div><?php endif; ?>

<div class="metrics-grid" style="grid-template-columns:repeat(3,1fr)">
  <div class="metric-card"><div class="metric-label">This month</div><div class="metric-value">₱<?= number_format($month_rev) ?></div><div class="metric-sub metric-up">Revenue received</div></div>
  <div class="metric-card"><div class="metric-label">Pending</div><div class="metric-value">₱<?= number_format($pending_amt) ?></div><div class="metric-sub">Awaiting payment</div></div>
  <div class="metric-card"><div class="metric-label">Deposits held</div><div class="metric-value">₱<?= number_format($deposits) ?></div><div class="metric-sub">Active reservations</div></div>
</div>

<div class="filter-bar"><input id="table-search" class="search-input" placeholder="Search transactions..."></div>

<div class="table-wrap">
<table>
  <thead><tr><th>ID</th><th>Booking #</th><th>Customer</th><th>Method</th><th>Amount</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
  <tbody>
  <?php foreach($payments as $p):
    $pmap=['paid'=>'success','pending'=>'warning','refunded'=>'info'];
  ?>
  <tr>
    <td>TXN-<?= str_pad($p['payment_id'],4,'0',STR_PAD_LEFT) ?></td>
    <td>#<?= $p['reservation_id'] ?></td>
    <td><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?></td>
    <td><?= $p['method'] ?></td>
    <td>₱<?= number_format($p['amount']) ?></td>
    <td><span class="badge badge-<?= $pmap[$p['status']]??'neutral' ?>"><?= ucfirst($p['status']) ?></span></td>
    <td><?= date('M j, Y', strtotime($p['paid_at'])) ?></td>
    <td>
      <form method="POST" style="display:inline-flex;gap:6px">
        <input type="hidden" name="payment_id" value="<?= $p['payment_id'] ?>">
        <select name="status" style="font-size:12px;padding:4px 6px;border-radius:6px">
          <?php foreach(['pending','paid','refunded'] as $s): ?>
          <option value="<?= $s ?>" <?= $p['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-sm">Update</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
