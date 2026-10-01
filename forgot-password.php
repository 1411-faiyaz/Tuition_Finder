<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mailer.php';

if (is_logged_in()) {
    redirect('index.php');
}

if (isset($_GET['restart'])) {
    unset($_SESSION['reset_user_id'], $_SESSION['reset_otp'], $_SESSION['reset_otp_expires'], $_SESSION['reset_email'], $_SESSION['reset_name']);
    redirect('forgot-password.php');
}

$errors = [];
$success = false;
$old = ['phone' => '', 'email' => ''];

// ---------- STEP 1: verify phone + email, generate & send OTP ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['stage'] ?? '') === 'request') {
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $old   = ['phone' => $phone, 'email' => $email];

    $errors = validate_required(['Phone number' => $phone, 'Email address' => $email]);
    if (!is_valid_phone($phone)) {
        $errors[] = 'Enter a valid 11-digit phone number (e.g. 01712345678).';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT id, name, email FROM users WHERE phone = ?');
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $userRow = $result->fetch_assoc();
            if (empty($userRow['email']) || strcasecmp($userRow['email'], $email) !== 0) {
                $errors[] = 'That email does not match the one on file for this phone number.';
            }
        } else {
            $errors[] = 'No account found with that phone number.';
        }
        $stmt->close();

        if (empty($errors)) {
            $otp = (string)random_int(100000, 999999);
            $_SESSION['reset_user_id']     = $userRow['id'];
            $_SESSION['reset_otp']         = $otp;
            $_SESSION['reset_otp_expires'] = time() + 600;
            $_SESSION['reset_email']       = $userRow['email'];
            $_SESSION['reset_name']        = $userRow['name'];

            $mailError = null;
            if (send_otp_email($userRow['email'], $userRow['name'], $otp, $mailError)) {
                set_flash('success', 'A verification code has been sent to ' . $userRow['email'] . '.');
                redirect('forgot-password.php');
            } else {
                $errors[] = 'Could not send the verification email. ' . h($mailError ?? '') .
                             ' Please check config/mail.php SMTP settings.';
                unset($_SESSION['reset_user_id'], $_SESSION['reset_otp'], $_SESSION['reset_otp_expires']);
            }
        }
    }
}

// ---------- STEP 2: verify OTP + set new password ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['stage'] ?? '') === 'verify') {
    $otpInput = trim($_POST['otp'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($_SESSION['reset_user_id']) || empty($_SESSION['reset_otp'])) {
        $errors[] = 'Your reset session has expired. Please start again.';
    } else {
        $errors = validate_required(['Verification code' => $otpInput, 'New password' => $password, 'Confirm password' => $confirm]);

        if (empty($errors)) {
            if (time() > $_SESSION['reset_otp_expires']) {
                $errors[] = 'This verification code has expired. Please request a new one.';
            } elseif (!hash_equals($_SESSION['reset_otp'], $otpInput)) {
                $errors[] = 'Incorrect verification code.';
            } elseif (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters.';
            } elseif ($password !== $confirm) {
                $errors[] = 'Password and Confirm Password do not match.';
            }
        }

        if (empty($errors)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->bind_param('si', $hashed, $_SESSION['reset_user_id']);
            $stmt->execute();
            $stmt->close();

            unset($_SESSION['reset_user_id'], $_SESSION['reset_otp'], $_SESSION['reset_otp_expires'], $_SESSION['reset_email'], $_SESSION['reset_name']);
            $success = true;
        }
    }
}

$awaitingOtp = !empty($_SESSION['reset_user_id']) && !empty($_SESSION['reset_otp']) && !$success;

$page_title = 'Forgot Password';
$root = '';
include __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:460px; padding:60px 20px;">
  <div class="form-card">

    <?php if ($success): ?>
      <h2 class="mt-0 text-center">Password Reset</h2>
      <div class="alert alert-success">Your password has been reset successfully.</div>
      <a href="login.php" class="btn btn-primary btn-block">Go to Sign In</a>

    <?php elseif ($awaitingOtp): ?>
      <h2 class="mt-0 text-center">Enter Verification Code</h2>
      <p class="text-center" style="color:var(--muted); margin-top:-10px;">
        We sent a 6-digit code to <?= h($_SESSION['reset_email']) ?>
      </p>

      <?php if ($errors): ?>
        <div class="alert alert-error"><?php foreach ($errors as $e) echo '<div>' . h($e) . '</div>'; ?></div>
      <?php endif; ?>

      <form method="post" class="validate-form" novalidate>
        <input type="hidden" name="stage" value="verify">
        <div class="form-group">
          <label for="otp">Verification Code</label>
          <input type="text" id="otp" name="otp" required maxlength="6" inputmode="numeric" placeholder="6-digit code">
        </div>
        <div class="form-group">
          <label for="password">New Password</label>
          <input type="password" id="password" name="password" required data-min-length="6" placeholder="Create a new password">
        </div>
        <div class="form-group">
          <label for="confirm_password">Confirm New Password</label>
          <input type="password" id="confirm_password" name="confirm_password" required data-match="#password" placeholder="Confirm your new password">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
        <p class="small-note"><a href="forgot-password.php?restart=1">Didn't get a code? Start over</a></p>
      </form>

    <?php else: ?>
      <h2 class="mt-0 text-center">Forgot Password</h2>
      <p class="text-center" style="color:var(--muted); margin-top:-10px;">
        Enter your phone number and registered email — we'll send you a verification code.
      </p>

      <?php if ($errors): ?>
        <div class="alert alert-error"><?php foreach ($errors as $e) echo '<div>' . h($e) . '</div>'; ?></div>
      <?php endif; ?>

      <form method="post" class="validate-form" novalidate>
        <input type="hidden" name="stage" value="request">
        <div class="form-group">
          <label for="phone">Phone Number</label>
          <input type="tel" id="phone" name="phone" required data-phone value="<?= h($old['phone']) ?>" placeholder="Enter your registered phone number">
        </div>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" required value="<?= h($old['email']) ?>" placeholder="Enter your registered email">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Send Verification Code</button>
        <p class="small-note"><a href="login.php">Back to Sign In</a></p>
      </form>
    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>