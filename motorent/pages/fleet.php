<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/db.php';
$page_title = 'Fleet';

$msg = '';
// ADD motorcycle
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='add') {
    $stmt = $conn->prepare("INSERT INTO motorcycles (plate_number,brand,model,year,type,daily_rate,deposit_amount,location_id,status) VALUES (?,?,?,?,?,?,?,?,'available')");
    $stmt->bind_param('sssisdii',
        $_POST['plate_number'],$_POST['brand'],$_POST['model'],
        $_POST['year'],$_POST['type'],$_POST['daily_rate'],
        $_POST['deposit_amount'],$_POST['location_id']);
    $stmt->execute() ? $msg='success|Motorcycle added successfully.' : $msg='danger|Error: '.$conn->error;
}
// UPDATE status
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='status') {
    $stmt = $conn->prepare("UPDATE motorcycles SET status=? WHERE motorcycle_id=?");
    $stmt->bind_param('si',$_POST['status'],$_POST['motorcycle_id']);
    $stmt->execute();
    $msg='success|Status updated.';
}
// DELETE
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='delete') {
    $stmt = $conn->prepare("DELETE FROM motorcycles WHERE motorcycle_id=?");
    $stmt->bind_param('i',$_POST['motorcycle_id']);
    $stmt->execute() ? $msg='success|Motorcycle removed.' : $msg='danger|Cannot delete — has active bookings.';
}

$filter = $_GET['status'] ?? '';
$sql = "SELECT m.*, l.name as location_name FROM motorcycles m LEFT JOIN locations l ON m.location_id=l.location_id";
if ($filter) $sql .= " WHERE m.status='".mysqli_real_escape_string($conn,$filter)."'";
$sql .= " ORDER BY m.plate_number";
$bikes = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
$locations = $conn->query("SELECT * FROM locations ORDER BY name")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
[$msg_type,$msg_text] = $msg ? explode('|',$msg,2) : ['',''];
?>
<div class="page-header">
  <div>
    <div class="page-title">Fleet</div>
    <div class="page-sub"><?= count($bikes) ?> motorcycles shown</div>
  </div>
  <button class="btn btn-primary" onclick="document.getElementById('add-modal').style.display='flex'">+ Add motorcycle</button>
</div>

<?php if($msg_text): ?><div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg_text) ?></div><?php endif; ?>

<div class="filter-bar">
  <input id="table-search" class="search-input" placeholder="Search fleet...">
  <a href="?status=" class="btn btn-sm <?= !$filter?'btn-primary':'' ?>">All</a>
  <a href="?status=available" class="btn btn-sm <?= $filter==='available'?'btn-primary':'' ?>">Available</a>
  <a href="?status=rented" class="btn btn-sm <?= $filter==='rented'?'btn-primary':'' ?>">Rented</a>
  <a href="?status=maintenance" class="btn btn-sm <?= $filter==='maintenance'?'btn-primary':'' ?>">Maintenance</a>
  <a href="?status=damage_review" class="btn btn-sm <?= $filter==='damage_review'?'btn-primary':'' ?>">Damage review</a>
</div>

<div class="table-wrap">
<table>
  <thead><tr><th>Plate</th><th>Brand / Model</th><th>Type</th><th>Year</th><th>Daily rate</th><th>Deposit</th><th>Location</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach($bikes as $b): ?>
  <tr>
    <td><strong><?= htmlspecialchars($b['plate_number']) ?></strong></td>
    <td><?= htmlspecialchars($b['brand'].' '.$b['model']) ?></td>
    <td><?= htmlspecialchars($b['type']) ?></td>
    <td><?= $b['year'] ?></td>
    <td>₱<?= number_format($b['daily_rate']) ?></td>
    <td>₱<?= number_format($b['deposit_amount']) ?></td>
    <td><?= htmlspecialchars($b['location_name']??'—') ?></td>
    <td>
      <?php $bmap=['available'=>'success','rented'=>'info','maintenance'=>'warning','damage_review'=>'danger']; ?>
      <span class="badge badge-<?= $bmap[$b['status']]??'neutral' ?>"><?= str_replace('_',' ',ucfirst($b['status'])) ?></span>
    </td>
    <td>
      <div class="btn-group">
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="status">
          <input type="hidden" name="motorcycle_id" value="<?= $b['motorcycle_id'] ?>">
          <select name="status" onchange="this.form.submit()" style="padding:4px 6px;font-size:12px;border-radius:6px">
            <?php foreach(['available','rented','maintenance','damage_review'] as $s): ?>
            <option value="<?= $s ?>" <?= $b['status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
        <?php if(isAdmin()): ?>
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="motorcycle_id" value="<?= $b['motorcycle_id'] ?>">
          <button type="submit" class="btn btn-sm btn-danger" data-confirm="Delete this motorcycle?">Delete</button>
        </form>
        <?php endif; ?>
      </div>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<!-- Add Modal -->
<div id="add-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;align-items:center;justify-content:center">
  <div style="background:var(--bg2);border-radius:var(--radius-lg);padding:28px;width:520px;max-height:90vh;overflow-y:auto">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <strong style="font-size:16px">Add motorcycle</strong>
      <button onclick="document.getElementById('add-modal').style.display='none'" class="btn btn-sm">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-row">
        <div class="form-group"><label>Plate number</label><input name="plate_number" required placeholder="MCB-009"></div>
        <div class="form-group"><label>Year</label><input name="year" type="number" value="2024" min="2000" max="2030"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Brand</label><input name="brand" required placeholder="Honda"></div>
        <div class="form-group"><label>Model</label><input name="model" required placeholder="Click 125i"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Type</label>
          <select name="type"><option>Scooter</option><option>Underbone</option><option>Adventure</option><option>Sport</option><option>Naked</option></select>
        </div>
        <div class="form-group"><label>Location</label>
          <select name="location_id">
            <?php foreach($locations as $l): ?><option value="<?= $l['location_id'] ?>"><?= htmlspecialchars($l['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Daily rate (₱)</label><input name="daily_rate" type="number" step="0.01" required placeholder="550.00"></div>
        <div class="form-group"><label>Deposit (₱)</label><input name="deposit_amount" type="number" step="0.01" value="1500.00"></div>
      </div>
      <div class="form-actions">
        <button type="button" class="btn" onclick="document.getElementById('add-modal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-primary">Add motorcycle</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
