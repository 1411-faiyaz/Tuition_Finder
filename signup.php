<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('index.php');
}

$role = ($_GET['role'] ?? $_POST['role'] ?? 'tutor') === 'guardian' ? 'guardian' : 'tutor';
$errors = [];
$old = ['name' => '', 'phone' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role     = $_POST['role'] === 'guardian' ? 'guardian' : 'tutor';
    $name     = trim($_POST['name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    $old = ['name' => $name, 'phone' => $phone, 'email' => $email];

        $errors = validate_required(['Full name' => $name, 'Phone number' => $phone, 'Email address' => $email, 'Password' => $password, 'Confirm password' => $confirm]);

    if (!is_valid_phone($phone)) {
        $errors[] = 'Phone number must be 11 digits and start with 01 (e.g. 01712345678).';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Password and Confirm Password do not match.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE phone = ?');
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'This phone number is already registered. Please sign in instead.';
        }
        $stmt->close();
    }

    if (empty($errors)) {
        // Tutors need admin approval before they appear publicly; guardians are approved instantly.
        $status = $role === 'tutor' ? 'pending' : 'approved';
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare('INSERT INTO users (role, name, phone, email, password, status) VALUES (?,?,?,?,?,?)');
        $emailOrNull = $email === '' ? null : $email;
        $stmt->bind_param('ssssss', $role, $name, $phone, $emailOrNull, $hashed, $status);

        if ($stmt->execute()) {
            $newUserId = $stmt->insert_id;
            $stmt->close();

            if ($role === 'tutor') {
                // create an empty profile row so profile.php can update it later
                $conn->query("INSERT INTO tutor_profiles (user_id) VALUES ($newUserId)");
            }

            set_flash('success', 'Account created successfully! Please sign in to continue.' .
                ($role === 'tutor' ? ' Your tutor profile will be visible after admin approval.' : ''));
            redirect('login.php');
        } else {
            $errors[] = 'Something went wrong while creating your account. Please try again.';
        }
    }
}

$page_title = 'Sign Up';
$root = '';
include __DIR__ . '/includes/header.php';
?>

<div class="container auth-wrap">
  <div>
    <h1>Join Tuition Finder Today</h1>
    <p style="color:var(--muted)">Connect with qualified tutors or share your expertise. Whether you're looking for academic support or want to teach others, we're here to help you succeed.</p>
    <ul class="check-list">
      <li>Quality Education &mdash; Access to verified and experienced tutors across all subjects</li>
      <li>Flexible Learning &mdash; Choose your preferred schedule and learning format</li>
      <li>Trusted Platform &mdash; Secure environment with admin-verified profiles</li>
    </ul>
  </div>

  <div class="form-card">
    <h2 class="mt-0 text-center">Create Account</h2>
    <p class="text-center" style="color:var(--muted); margin-top:-10px;">Choose your account type to get started</p>

    <?php if ($errors): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $e) echo '<div>' . h($e) . '</div>'; ?>
      </div>
    <?php endif; ?>

    <form method="post" class="validate-form" novalidate>
      <div class="role-toggle">
        <label><input type="radio" name="role" value="tutor" <?= $role === 'tutor' ? 'checked' : '' ?>><span>Tutor</span></label>
        <label><input type="radio" name="role" value="guardian" <?= $role === 'guardian' ? 'checked' : '' ?>><span>Guardian</span></label>
      </div>

      <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" required value="<?= h($old['name']) ?>" placeholder="Enter your full name">
      </div>
      <div class="form-group">
        <label for="phone">Phone Number</label>
        <input type="tel" id="phone" name="phone" required data-phone value="<?= h($old['phone']) ?>" placeholder="01XXXXXXXXX">
      </div>
      <div class="form-group">
                <label for="email">Email Address</label>
        <input type="email" id="email" name="email" required value="<?= h($old['email']) ?>" placeholder="you@example.com">
        <p class="hint">Used for password recovery — please use an email you can access.</p>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required data-min-length="6" placeholder="Create a strong password">
      </div>
      <div class="form-group">
        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required data-match="#password" placeholder="Confirm your password">
      </div>
      <button type="submit" class="btn btn-primary btn-block" id="submitBtn">Create <?= $role === 'tutor' ? 'Tutor' : 'Guardian' ?> Account</button>
      <p class="small-note">Already have an account? <a href="login.php">Sign in here</a></p>
    </form>
  </div>
</div>
<script>
document.querySelectorAll('input[name="role"]').forEach(function(r){
  r.addEventListener('change', function(){
    document.getElementById('submitBtn').textContent = 'Create ' + (this.value === 'tutor' ? 'Tutor' : 'Guardian') + ' Account';
  });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
