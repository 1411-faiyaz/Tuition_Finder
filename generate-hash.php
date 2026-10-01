<?php
$password = $_POST['password'] ?? '';
$hash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Password Hash Generator</title>
<style>
  body{font-family:Arial, sans-serif; max-width:500px; margin:60px auto; padding:0 20px;}
  input{width:100%; padding:10px; margin:10px 0; box-sizing:border-box;}
  button{padding:10px 20px; background:#2f6fed; color:#fff; border:none; border-radius:6px; cursor:pointer;}
  code{display:block; background:#f1f5f9; padding:14px; border-radius:6px; word-break:break-all; margin-top:16px;}
</style>
</head>
<body>
  <h2>Password Hash Generator</h2>
  <form method="post">
    <input type="text" name="password" placeholder="Enter a password, e.g. admin123" value="<?= htmlspecialchars($password) ?>">
    <button type="submit">Generate Hash</button>
  </form>
  <?php if ($hash): ?>
    <p><strong>Hash for "<?= htmlspecialchars($password) ?>":</strong></p>
    <code><?= htmlspecialchars($hash) ?></code>
    <!-- <p>Copy this value and paste it into the <code>password</code> column in phpMyAdmin.</p> -->
  <?php endif; ?>
</body>
</html>


<!-- if i want to change admin password

    UPDATE users SET password = 'PASTE_YOUR_HASH_HERE' WHERE phone = '01700000000'; 
 
-->

<!-- INSERT INTO users (role, name, phone, email, password, status)
VALUES ('admin', 'Admin_Name', '01800000000', 'newadmin@example.com', 'PASTE_YOUR_HASH_HERE', 'approved'); 

-->
