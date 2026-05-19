<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/db.php';
$page_title = 'New Booking';

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $cid  = (int)$_POST['customer_id'];
    $mid  = (int)$_POST['motorcycle_id'];
    $sd   = $_POST['start_date'];
    $ed   = $_POST['end_date'];
    $total= (float)$_POST['total_amount'];
    $dep  = (float)$_POST['deposit_amount'];
    $meth = $_POST['payment_method'];

    // Check availability
    $clash = $conn->query("SELECT COUNT(*) c FROM reservations WHERE motorcycle_id=$mid AND status NOT IN ('cancelled','returned') AND NOT (end_date < '$sd' OR start_date > '$ed')")->fetch_assoc()['c'];
    if ($clash > 0) {
        $msg = 'danger|This motorcycle is already booked for those dates.';
    } else {
        $conn->begin_transaction();
        $stmt = $conn->prepare("INSERT INTO reservations (customer_id,motorcycle_id,start_date,end_date,status,total_amount,deposit_amount) VALUES (?,?,'".mysqli_real_escape_string($conn,$sd)."','".mysqli_real_escape_string($conn,$ed)."','confirmed',?,?)");
        $stmt->bind_param('iidd',$cid,$mid,$total,$dep);
        $stmt->execute();
        $rid = $conn->insert_id;
        $stmt2 = $conn->prepare("INSERT INTO payments (reservation_id,amount,method,status) VALUES (?,?,'".mysqli_real_escape_string($conn,$meth)."','pending')");
        $stmt2->bind_param('id',$rid,$total);
        $stmt2->execute();
        $conn->commit();
        $msg = 'success|Booking #'.$rid.' created successfully!';
    }
}

$customers  = $conn->query("SELECT * FROM customers WHERE doc_status='verified' ORDER BY first_name")->fetch_all(MYSQLI_ASSOC);
$bikes      = $conn->query("SELECT m.*, l.name as loc FROM motorcycles m LEFT JOIN locations l ON m.location_id=l.location_id WHERE m.status='available' ORDER BY m.brand,m.model")->fetch_all(MYSQLI_ASSOC);
$moto_icons = ['Scooter'=>'🛵','Underbone'=>'🏍','Adventure'=>'🏍','Sport'=>'🏍','Naked'=>'🏍'];

require_once __DIR__ . '/../includes/header.php';
[$msg_type,$msg_text] = $msg ? explode('|',$msg,2) : ['',''];
?>
<div class="page-header">
  <div>
    <div class="page-title">New Booking</div>
    <div class="page-sub">Create a reservation for a verified customer</div>
  </div>
</div>

<?php if($msg_text): ?><div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg_text) ?></div><?php endif; ?>

<form method="POST">
<div class="card">
  <div class="card-title">1. Customer</div>
  <div class="form-row">
    <div class="form-group">
      <label>Select customer</label>
      <select name="customer_id" required>
        <option value="">— Choose verified customer —</option>
        <?php foreach($customers as $c): ?>
        <option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['first_name'].' '.$c['last_name'].' ('.$c['license_number'].')') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="justify-content:flex-end;padding-top:20px">
      <a href="/motorent/pages/customers.php" class="btn btn-sm" target="_blank">+ Add new customer ↗</a>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-title">2. Select motorcycle</div>
  <input type="hidden" name="motorcycle_id" id="motorcycle_id" required>
  <?php if(empty($bikes)): ?>
    <div class="alert alert-warning">No motorcycles available right now.</div>
  <?php else: ?>
  <div class="moto-grid">
    <?php foreach($bikes as $b): ?>
    <div class="moto-card" data-id="<?= $b['motorcycle_id'] ?>" data-rate="<?= $b['daily_rate'] ?>" data-deposit="<?= $b['deposit_amount'] ?>">
      <div class="moto-icon"><?= $moto_icons[$b['type']]??'🏍' ?></div>
      <div class="moto-name"><?= htmlspecialchars($b['brand'].' '.$b['model']) ?></div>
      <div class="moto-meta"><?= $b['type'] ?> · <?= $b['plate_number'] ?></div>
      <div class="moto-rate">₱<?= number_format($b['daily_rate']) ?>/day</div>
      <div style="font-size:11px;color:var(--text3);margin-top:3px"><?= htmlspecialchars($b['loc']??'') ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-title">3. Schedule &amp; payment</div>
  <div class="form-row">
    <div class="form-group"><label>Start date</label><input type="date" name="start_date" id="start_date" required min="<?= date('Y-m-d') ?>"></div>
    <div class="form-group"><label>End date</label><input type="date" name="end_date" id="end_date" required min="<?= date('Y-m-d') ?>"></div>
    <div class="form-group"><label>Payment method</label>
      <select name="payment_method"><option>Cash</option><option>GCash</option><option>Card</option><option>Maya</option></select>
    </div>
  </div>
  <input type="hidden" name="total_amount" id="total_amount">
  <input type="hidden" name="deposit_amount" id="deposit_amount">
  <div class="summary-box" id="summary-box" style="display:none">
    <div class="summary-row"><span>Duration</span><strong id="sum-days">—</strong></div>
    <div class="summary-row"><span>Daily rate</span><span id="sum-rate">—</span></div>
    <div class="summary-row"><span>Rental subtotal</span><span id="sum-rental">—</span></div>
    <div class="summary-row"><span>Refundable deposit</span><span id="sum-dep">—</span></div>
    <div class="summary-row total"><span>Total due</span><strong id="sum-total">—</strong></div>
  </div>
</div>

<div class="form-actions" style="border:none;padding-top:0">
  <a href="/motorent/index.php" class="btn">Cancel</a>
  <button type="submit" class="btn btn-primary">Confirm booking</button>
</div>
</form>

<script>
document.querySelectorAll('.moto-card[data-id]').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.moto-card').forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
        document.getElementById('motorcycle_id').value = this.dataset.id;
        document.getElementById('summary-box').style.display = '';
        updateSummary();
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
