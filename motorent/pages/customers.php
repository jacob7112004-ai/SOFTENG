<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/db.php';
$page_title = 'Customers';

$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $act = $_POST['action'];
    if ($act==='add') {
        $stmt = $conn->prepare("INSERT INTO customers (first_name,last_name,email,phone,address,date_of_birth,license_number,doc_status) VALUES (?,?,?,?,?,?,?,'pending')");
        $stmt->bind_param('sssssss',$_POST['first_name'],$_POST['last_name'],$_POST['email'],$_POST['phone'],$_POST['address'],$_POST['date_of_birth'],$_POST['license_number']);
        $stmt->execute() ? $msg='success|Customer added.' : $msg='danger|Error: '.$conn->error;
    } elseif ($act==='verify') {
        $stmt = $conn->prepare("UPDATE customers SET doc_status=? WHERE customer_id=?");
        $stmt->bind_param('si',$_POST['doc_status'],$_POST['customer_id']);
        $stmt->execute();
        $msg='success|Document status updated.';
    } elseif ($act==='delete') {
        $stmt = $conn->prepare("DELETE FROM customers WHERE customer_id=?");
        $stmt->bind_param('i',$_POST['customer_id']);
        $stmt->execute() ? $msg='success|Customer removed.' : $msg='danger|Cannot delete — customer has reservations.';
    }
}

$customers = $conn->query("SELECT c.*, COUNT(r.reservation_id) as total_rentals FROM customers c LEFT JOIN reservations r ON c.customer_id=r.customer_id GROUP BY c.customer_id ORDER BY c.created_at DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
[$msg_type,$msg_text] = $msg ? explode('|',$msg,2) : ['',''];
?>
<div class="page-header">
  <div>
    <div class="page-title">Customers</div>
    <div class="page-sub"><?= count($customers) ?> registered customers</div>
  </div>
  <button class="btn btn-primary" onclick="document.getElementById('add-modal').style.display='flex'">+ Add customer</button>
</div>

<?php if($msg_text): ?><div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg_text) ?></div><?php endif; ?>
<div class="filter-bar"><input id="table-search" class="search-input" placeholder="Search customers..."></div>

<div class="table-wrap">
<table>
  <thead><tr><th>Name</th><th>Contact</th><th>License</th><th>Rentals</th><th>Doc status</th><th>Joined</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach($customers as $c):
    $initials = strtoupper(substr($c['first_name'],0,1).substr($c['last_name'],0,1));
    $dmap = ['verified'=>'success','pending'=>'warning','rejected'=>'danger'];
  ?>
  <tr>
    <td>
      <div style="display:flex;align-items:center;gap:8px">
        <div class="staff-avatar"><?= $initials ?></div>
        <div>
          <div style="font-weight:500"><?= htmlspecialchars($c['first_name'].' '.$c['last_name']) ?></div>
          <div style="font-size:11px;color:var(--text3)"><?= htmlspecialchars($c['email']) ?></div>
        </div>
      </div>
    </td>
    <td><?= htmlspecialchars($c['phone']??'—') ?></td>
    <td><?= htmlspecialchars($c['license_number']??'—') ?></td>
    <td><?= $c['total_rentals'] ?></td>
    <td>
      <form method="POST" style="display:inline-flex;align-items:center;gap:6px">
        <input type="hidden" name="action" value="verify">
        <input type="hidden" name="customer_id" value="<?= $c['customer_id'] ?>">
        <select name="doc_status" onchange="this.form.submit()" style="font-size:12px;padding:4px 6px;border-radius:6px">
          <?php foreach(['pending','verified','rejected'] as $s): ?>
          <option value="<?= $s ?>" <?= $c['doc_status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </td>
    <td><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
    <td>
      <?php if(isAdmin()): ?>
      <form method="POST" style="display:inline">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="customer_id" value="<?= $c['customer_id'] ?>">
        <button class="btn btn-sm btn-danger" data-confirm="Delete this customer?">Delete</button>
      </form>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<!-- Add modal -->
<div id="add-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;align-items:center;justify-content:center">
  <div style="background:var(--bg2);border-radius:var(--radius-lg);padding:28px;width:540px;max-height:90vh;overflow-y:auto">
    <div style="display:flex;justify-content:space-between;margin-bottom:20px">
      <strong style="font-size:16px">Add customer</strong>
      <button onclick="document.getElementById('add-modal').style.display='none'" class="btn btn-sm">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-row">
        <div class="form-group"><label>First name</label><input name="first_name" required></div>
        <div class="form-group"><label>Last name</label><input name="last_name" required></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
        <div class="form-group"><label>Phone</label><input name="phone"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>License number</label><input name="license_number"></div>
        <div class="form-group"><label>Date of birth</label><input type="date" name="date_of_birth"></div>
      </div>
      <div class="form-group" style="margin-bottom:14px"><label>Address</label><textarea name="address" rows="2"></textarea></div>
      <div class="form-actions">
        <button type="button" class="btn" onclick="document.getElementById('add-modal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-primary">Add customer</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
