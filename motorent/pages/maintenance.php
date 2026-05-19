<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../includes/db.php';
$page_title = 'Maintenance';

$msg='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $act = $_POST['action'];
    if ($act==='add') {
        $stmt = $conn->prepare("INSERT INTO maintenance (motorcycle_id,type,scheduled_date,status,notes) VALUES (?,?,?,'scheduled',?)");
        $stmt->bind_param('isss',$_POST['motorcycle_id'],$_POST['type'],$_POST['scheduled_date'],$_POST['notes']);
        $stmt->execute() ? $msg='success|Maintenance scheduled.' : $msg='danger|Error: '.$conn->error;
    } elseif ($act==='status') {
        $comp = $_POST['status']==='completed' ? ", completed_date=CURDATE()" : '';
        $conn->query("UPDATE maintenance SET status='".$_POST['status']."'$comp WHERE maintenance_id=".(int)$_POST['maintenance_id']);
        // If completed, set motorcycle back to available
        if ($_POST['status']==='completed') {
            $mid = $conn->query("SELECT motorcycle_id FROM maintenance WHERE maintenance_id=".(int)$_POST['maintenance_id'])->fetch_assoc()['motorcycle_id'];
            $conn->query("UPDATE motorcycles SET status='available' WHERE motorcycle_id=$mid AND status='maintenance'");
        }
        $msg='success|Status updated.';
    }
}

$records = $conn->query("SELECT mn.*, CONCAT(m.brand,' ',m.model) as bike, m.plate_number FROM maintenance mn JOIN motorcycles m ON mn.motorcycle_id=m.motorcycle_id ORDER BY mn.scheduled_date DESC")->fetch_all(MYSQLI_ASSOC);
$bikes   = $conn->query("SELECT motorcycle_id, CONCAT(brand,' ',model,' (',plate_number,')') as label FROM motorcycles ORDER BY brand")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../includes/header.php';
[$msg_type,$msg_text] = $msg ? explode('|',$msg,2) : ['',''];
?>
<div class="page-header">
  <div><div class="page-title">Maintenance</div><div class="page-sub">Service schedules and records</div></div>
  <button class="btn btn-primary" onclick="document.getElementById('add-modal').style.display='flex'">+ Schedule service</button>
</div>

<?php if($msg_text): ?><div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg_text) ?></div><?php endif; ?>
<div class="filter-bar"><input id="table-search" class="search-input" placeholder="Search maintenance..."></div>

<div class="table-wrap">
<table>
  <thead><tr><th>Unit</th><th>Service type</th><th>Scheduled</th><th>Completed</th><th>Status</th><th>Notes</th><th>Action</th></tr></thead>
  <tbody>
  <?php foreach($records as $r):
    $smap=['scheduled'=>'neutral','in_progress'=>'warning','completed'=>'success'];
  ?>
  <tr>
    <td><strong><?= htmlspecialchars($r['bike']) ?></strong><br><span style="font-size:11px;color:var(--text3)"><?= $r['plate_number'] ?></span></td>
    <td><?= htmlspecialchars($r['type']) ?></td>
    <td><?= date('M j, Y',strtotime($r['scheduled_date'])) ?></td>
    <td><?= $r['completed_date'] ? date('M j, Y',strtotime($r['completed_date'])) : '—' ?></td>
    <td><span class="badge badge-<?= $smap[$r['status']] ?>"><?= ucfirst(str_replace('_',' ',$r['status'])) ?></span></td>
    <td style="max-width:180px;font-size:12px;color:var(--text2)"><?= htmlspecialchars($r['notes']??'') ?></td>
    <td>
      <form method="POST" style="display:inline-flex;gap:6px">
        <input type="hidden" name="action" value="status">
        <input type="hidden" name="maintenance_id" value="<?= $r['maintenance_id'] ?>">
        <select name="status" style="font-size:12px;padding:4px 6px;border-radius:6px">
          <?php foreach(['scheduled','in_progress','completed'] as $s): ?>
          <option value="<?= $s ?>" <?= $r['status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-sm">Save</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<!-- Add modal -->
<div id="add-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;align-items:center;justify-content:center">
  <div style="background:var(--bg2);border-radius:var(--radius-lg);padding:28px;width:480px">
    <div style="display:flex;justify-content:space-between;margin-bottom:20px">
      <strong style="font-size:16px">Schedule maintenance</strong>
      <button onclick="document.getElementById('add-modal').style.display='none'" class="btn btn-sm">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group" style="margin-bottom:12px"><label>Motorcycle</label>
        <select name="motorcycle_id" required>
          <?php foreach($bikes as $b): ?><option value="<?= $b['motorcycle_id'] ?>"><?= htmlspecialchars($b['label']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:12px"><label>Service type</label><input name="type" required placeholder="Oil change, Brake check, Tire replacement..."></div>
      <div class="form-group" style="margin-bottom:12px"><label>Scheduled date</label><input type="date" name="scheduled_date" required value="<?= date('Y-m-d') ?>"></div>
      <div class="form-group" style="margin-bottom:16px"><label>Notes</label><textarea name="notes" rows="2" placeholder="Additional details..."></textarea></div>
      <div class="form-actions">
        <button type="button" class="btn" onclick="document.getElementById('add-modal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-primary">Schedule</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
