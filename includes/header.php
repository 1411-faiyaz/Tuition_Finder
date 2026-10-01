<?php
/**
 * Expects (optionally set by the including page before require):
 *   $root       - relative path prefix to project root, e.g. '' or '../'
 *   $page_title - string for <title>
 * Also expects require_once functions.php to have already run.
 */
$root = $root ?? '';
$page_title = $page_title ?? 'Tuition Finder';
$user = current_user();
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($page_title) ?> | Tuition Finder</title>
<link rel="stylesheet" href="<?= $root ?>assets/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <a class="logo" href="<?= $root ?>index.php">Tuition<span>Finder</span></a>

    <nav class="main-nav">
      <?php if (!$user): ?>
        <a href="<?= $root ?>index.php#find-tutor">Find Tutor</a>
        <a href="<?= $root ?>index.php#join-tutor">Join as Tutor</a>
      <?php elseif ($user['role'] === 'tutor'): ?>
        <a href="<?= $root ?>tutor/dashboard.php">Home</a>
        <a href="<?= $root ?>tutor/browse-tuitions.php">Tuition Board</a>
        <a href="<?= $root ?>tutor/my-applications.php">Activity</a>
        <a href="<?= $root ?>tutor/profile.php">My Profile</a>
        <a href="<?= $root ?>tutor/salary-chatbot.php">Salary Predictor</a>
        <a href="<?= $root ?>messages/inbox.php">Messages</a>
      <?php elseif ($user['role'] === 'guardian'): ?>
        <a href="<?= $root ?>guardian/dashboard.php">Dashboard</a>
        <a href="<?= $root ?>guardian/browse-tutors.php">Explore Tutors</a>
        <a href="<?= $root ?>guardian/tuitions.php">Tuition Posts</a>
        <a href="<?= $root ?>guardian/applications.php">Applications</a>
        <a href="<?= $root ?>messages/inbox.php">Messages</a>
      <?php elseif ($user['role'] === 'admin'): ?>
        <a href="<?= $root ?>admin/dashboard.php">Dashboard</a>
        <a href="<?= $root ?>admin/manage-users.php">Users</a>
        <a href="<?= $root ?>admin/manage-tuitions.php">Tuition Posts</a>
      <?php endif; ?>
    </nav>

    <div class="header-actions">
      <?php if ($user): ?>
        <span class="user-chip"><?= h($user['name']) ?> <small>(<?= h(ucfirst($user['role'])) ?>)</small></span>
        <a class="btn btn-outline btn-sm" href="<?= $root ?>logout.php">Sign Out</a>
      <?php else: ?>
        <a class="btn btn-outline btn-sm" href="<?= $root ?>signup.php">Sign Up</a>
        <a class="btn btn-primary btn-sm" href="<?= $root ?>login.php">Sign In</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php if ($flash): ?>
  <div class="container">
    <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
  </div>
<?php endif; ?>

<main>
