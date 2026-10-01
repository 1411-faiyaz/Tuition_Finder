<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
$phone_old = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone_old = $phone;

    $errors = validate_required(['Phone number' => $phone, 'Password' => $password]);

    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT id, name, role, status, password FROM users WHERE phone = ?');
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                if ($user['role'] === 'tutor' && $user['status'] === 'rejected') {
                    $errors[] = 'Your tutor application was not approved. Please contact support.';
                } else {
                    $_SESSION['user_id']     = $user['id'];
                    $_SESSION['user_name']   = $user['name'];
                    $_SESSION['user_role']   = $user['role'];
                    $_SESSION['user_status'] = $user['status'];

                    if ($user['role'] === 'tutor') {
                        redirect('tutor/dashboard.php');
                    } elseif ($user['role'] === 'guardian') {
                        redirect('guardian/dashboard.php');
                    } else {
                        redirect('admin/dashboard.php');
                    }
                }
            } else {
                $errors[] = 'Incorrect phone number or password.';
            }
        } else {
            $errors[] = 'Incorrect phone number or password.';
        }
        $stmt->close();
    }
}

$page_title = 'Sign In';
$root = '';
include __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:460px; padding:60px 20px;">
  <div class="form-card">
    <h2 class="mt-0 text-center">Welcome Back</h2>
    <p class="text-center" style="color:var(--muted); margin-top:-10px;">Sign in to your Tuition Finder account</p>

    <?php if ($errors): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $e) echo '<div>' . h($e) . '</div>'; ?>
      </div>
    <?php endif; ?>

    <form method="post" class="validate-form" novalidate>
      <div class="form-group">
        <label for="phone">Phone Number</label>
        <input type="tel" id="phone" name="phone" required value="<?= h($phone_old) ?>" placeholder="Enter your phone number">
      </div>
            <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required placeholder="Enter your password">
        <p class="hint" style="text-align:right; margin-top:6px;"><a href="forgot-password.php">Forgot password?</a></p>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Sign In</button>
      <p class="small-note">Don't have an account? <a href="signup.php">Sign up here</a></p>
      
      <!-- <p class="small-note">Demo admin &mdash; phone: <b>01700000000</b>, password: <b>admin123</b></p> -->
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
