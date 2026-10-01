<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['tutor'], '../login.php', '../index.php');
$user = current_user();

// Stats
$applied = $conn->query("SELECT COUNT(*) c FROM applications WHERE tutor_id={$user['id']}")->fetch_assoc()['c'];
$accepted = $conn->query("SELECT COUNT(*) c FROM applications WHERE tutor_id={$user['id']} AND status='accepted'")->fetch_assoc()['c'];
$rejected = $conn->query("SELECT COUNT(*) c FROM applications WHERE tutor_id={$user['id']} AND status='rejected'")->fetch_assoc()['c'];

// Profile view count placeholder (not tracked in this simplified build)
$profileViews = 0;

// Nearby / open tuitions (excluding ones already applied to)
$openTuitions = $conn->query("
  SELECT tp.* FROM tuition_posts tp
  WHERE tp.status='active'
    AND tp.id NOT IN (SELECT tuition_id FROM applications WHERE tutor_id={$user['id']})
  ORDER BY tp.created_at DESC LIMIT 5
");

$page_title = 'Tutor Dashboard';
$root = '../';
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <?php if ($user['status'] === 'pending'): ?>
    <div class="alert alert-info">Your profile is pending approval. We'll notify you once it's reviewed by our admin team.</div>
  <?php endif; ?>

  <div class="page-head">
    <h1>Welcome back, <?= h($user['name']) ?>!</h1>
    <p>Here's your tutoring activity overview.</p>
  </div>

  <div class="stat-grid">
    <div class="stat-card"><div class="num"><?= (int)$profileViews ?></div><div class="label">Profile View Count</div></div>
    <div class="stat-card"><div class="num"><?= (int)$applied ?></div><div class="label">Tuitions Applied</div></div>
    <div class="stat-card"><div class="num"><?= (int)$accepted ?></div><div class="label">Confirmed Tuitions</div></div>
    <div class="stat-card"><div class="num"><?= (int)$rejected ?></div><div class="label">Rejected Applications</div></div>
  </div>

  <div class="dash-grid">
    <div class="panel">
      <h3>Open Tuitions For You</h3>
      <?php if ($openTuitions->num_rows === 0): ?>
        <div class="empty-state">No new tuitions right now. Check back soon!</div>
      <?php else: while ($t = $openTuitions->fetch_assoc()): ?>
        <div class="list-item">
          <div>
            <div class="title"><?= h($t['subject']) ?></div>
            <div class="meta"><?= h($t['medium']) ?> &bull; <?= h($t['class_level']) ?> &bull; <?= h($t['location']) ?></div>
          </div>
          <div class="price"><?= (int)$t['budget'] ?>/month</div>
        </div>
      <?php endwhile; endif; ?>
      <p style="margin-top:14px;"><a href="browse-tuitions.php">Browse all tuitions &rarr;</a></p>
    </div>

    <div class="panel">
      <h3>Quick Actions</h3>
      <p><a href="profile.php" class="btn btn-outline btn-block" style="margin-bottom:10px;">Complete / Edit My Profile</a></p>
      <p><a href="browse-tuitions.php" class="btn btn-primary btn-block" style="margin-bottom:10px;">Browse Tuition Board</a></p>
      <p><a href="my-applications.php" class="btn btn-outline btn-block" style="margin-bottom:10px;">View My Applications</a></p>
      <p><a href="salary-chatbot.php" class="btn btn-outline btn-block">💰 Salary Prediction Assistant</a></p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
