<?php
session_start();
if (isset($_SESSION['staff_id'])) { header('Location: /motorent/index.php'); exit; }
require_once __DIR__ . '/includes/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $conn->prepare("SELECT * FROM staff WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $staff = $stmt->get_result()->fetch_assoc();
    if ($staff && password_verify($password, $staff['password'])) {
        $_SESSION['staff_id']   = $staff['staff_id'];
        $_SESSION['staff_name'] = $staff['name'];
        $_SESSION['role']       = $staff['role'];
        header('Location: /motorent/index.php');
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MotoRent — Login</title>
<link rel="stylesheet" href="/motorent/assets/css/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-title">🏍 MotoRent</div>
    <div class="login-sub">Sign in to your account</div>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group" style="margin-bottom:14px">
        <label>Email address</label>
        <input type="email" name="email" required placeholder="admin@motorent.com" value="<?= htmlspecialchars($_POST['email']??'') ?>">
      </div>
      <div class="form-group" style="margin-bottom:20px">
        <label>Password</label>
        <input type="password" name="password" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Sign in</button>
    </form>
    <p style="font-size:12px;color:var(--text3);margin-top:16px;text-align:center">Default: admin@motorent.com / <strong>password</strong></p>
  </div>
</div>
</body>
</html>
