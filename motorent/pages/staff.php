<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isAdmin()) { header('Location: /motorent/index.php'); exit; }
require_once __DIR__ . '/../includes/db.php';
$page_title = 'Staff';

$msg='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $act = $_POST['action'];
    if ($act==='add') {
        $hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO staff (name,email,role,password) VALUES (?,?,?,?)");
        $stmt->bind_param('ssss',$_POST['name'],$_POST['email'],$_POST['role'],$hash);
        $stmt->execute() ? $msg='success|Staff added.' : $msg='danger|Error: '.$conn->error;
    } elseif ($act==='delete') {
        if ((int)$_POST['staff_id'] === (int)$_SESSION['staff_id']) { $msg='danger|Cannot delete yourself.'; }
        else {
            $stmt=$conn->prepare("DELETE FROM staff WHERE staff_id=?");
            $stmt->bind_param('i',$_POST['staff_id']);
            $stmt->execute() ? $msg='success|Staff removed.' : $msg='danger|Error.';
        }
    }
}

$staff_list = $conn->query("SELECT * FROM staff ORDER BY role,name")->fetch_all(MYSQLI_ASSOC);
require_once __DIR__ . '/../includes/header.php';
[$msg_type,$msg_text] = $msg ? explode('|',$msg,2) : ['',''];
?>
<div class="page-header">
  <div><div class="page-title">Staff</div><div class="page-sub">Manage system users</div></div>
  <button class="btn btn-primary" onclick="document.getElementById('add-modal').style.display='flex'">+ Add staff</button>
</div>

<?php if($msg_text): ?><div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg_text) ?></div><?php endif; ?>

<div class="table-wrap">
<table>
  <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach($staff_list as $s): ?>
  <tr>
    <td><div style="display:flex;align-items:center;gap:8px"><div class="staff-avatar"><?= strtoupper(substr($s['name'],0,2)) ?></div><?= htmlspecialchars($s['name']) ?></div></td>
    <td><?= htmlspecialchars($s['email']) ?></td>
    <td><span class="badge <?= $s['role']==='admin'?'badge-info':'badge-neutral' ?>"><?= ucfirst($s['role']) ?></span></td>
    <td>
      <?php if($s['staff_id'] != $_SESSION['staff_id']): ?>
      <form method="POST" style="display:inline">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="staff_id" value="<?= $s['staff_id'] ?>">
        <button class="btn btn-sm btn-danger" data-confirm="Remove this staff member?">Remove</button>
      </form>
      <?php else: ?>
      <span style="font-size:12px;color:var(--text3)">(you)</span>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>

<!-- Add modal -->
<div id="add-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;align-items:center;justify-content:center">
  <div style="background:var(--bg2);border-radius:var(--radius-lg);padding:28px;width:420px">
    <div style="display:flex;justify-content:space-between;margin-bottom:20px">
      <strong style="font-size:16px">Add staff member</strong>
      <button onclick="document.getElementById('add-modal').style.display='none'" class="btn btn-sm">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group" style="margin-bottom:12px"><label>Full name</label><input name="name" required></div>
      <div class="form-group" style="margin-bottom:12px"><label>Email</label><input type="email" name="email" required></div>
      <div class="form-group" style="margin-bottom:12px"><label>Role</label>
        <select name="role"><option value="staff">Staff</option><option value="admin">Admin</option></select>
      </div>
      <div class="form-group" style="margin-bottom:16px"><label>Password</label><input type="password" name="password" required minlength="6"></div>
      <div class="form-actions">
        <button type="button" class="btn" onclick="document.getElementById('add-modal').style.display='none'">Cancel</button>
        <button type="submit" class="btn btn-primary">Add staff</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
