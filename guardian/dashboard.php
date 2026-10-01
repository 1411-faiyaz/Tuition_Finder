<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['guardian'], '../login.php', '../index.php');
$user = current_user();

$activePosts = $conn->query("SELECT COUNT(*) c FROM tuition_posts WHERE guardian_id={$user['id']} AND status='active'")->fetch_assoc()['c'];
$totalApps = $conn->query("SELECT COUNT(*) c FROM applications a JOIN tuition_posts tp ON tp.id=a.tuition_id WHERE tp.guardian_id={$user['id']}")->fetch_assoc()['c'];
$hired = $conn->query("SELECT COUNT(*) c FROM applications a JOIN tuition_posts tp ON tp.id=a.tuition_id WHERE tp.guardian_id={$user['id']} AND a.status='accepted'")->fetch_assoc()['c'];
$unreadMsgs = $conn->query("SELECT COUNT(*) c FROM messages WHERE receiver_id={$user['id']} AND is_read=0")->fetch_assoc()['c'];

$recentApps = $conn->query("
  SELECT a.*, tp.title, u.name AS tutor_name FROM applications a
  JOIN tuition_posts tp ON tp.id = a.tuition_id
  JOIN users u ON u.id = a.tutor_id
  WHERE tp.guardian_id = {$user['id']}
  ORDER BY a.created_at DESC LIMIT 5
");

$recentPosts = $conn->query("SELECT * FROM tuition_posts WHERE guardian_id={$user['id']} ORDER BY created_at DESC LIMIT 5");

$page_title = 'Dashboard';
$root = '../';
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="page-head">
    <h1>Welcome back, <?= h($user['name']) ?>!</h1>
    <p>Manage your tuition posts and find the perfect tutors.</p>
  </div>

  <div class="panel" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
    <div>
      <strong>Need a Tutor?</strong>
      <p style="margin:4px 0 0; color:var(--muted);">Post your tuition requirement and connect with qualified tutors.</p>
    </div>
    <a href="post-tuition.php" class="btn btn-primary">+ Post New Tuition</a>
  </div>

  <div class="stat-grid">
    <div class="stat-card"><div class="num"><?= (int)$activePosts ?></div><div class="label">Active Tuition Posts</div></div>
    <div class="stat-card"><div class="num"><?= (int)$totalApps ?></div><div class="label">Total Applications</div></div>
    <div class="stat-card"><div class="num"><?= (int)$hired ?></div><div class="label">Tutors Hired</div></div>
    <div class="stat-card"><div class="num"><?= (int)$unreadMsgs ?></div><div class="label">Unread Messages</div></div>
  </div>

  <div class="dash-grid">
    <div class="panel">
      <h3>Recent Applications</h3>
      <?php if ($recentApps->num_rows === 0): ?>
        <div class="empty-state">No data found.</div>
      <?php else: while ($a = $recentApps->fetch_assoc()): ?>
        <div class="list-item">
          <div>
            <div class="title"><?= h($a['tutor_name']) ?></div>
            <div class="meta">Applied for: <?= h($a['title']) ?></div>
          </div>
          <span class="badge badge-<?= h($a['status']) ?>"><?= h(ucfirst($a['status'])) ?></span>
        </div>
      <?php endwhile; endif; ?>
      <p style="margin-top:14px;"><a href="applications.php">View all applications &rarr;</a></p>
    </div>

    <div class="panel">
      <h3>Active Tuition Posts</h3>
      <?php if ($recentPosts->num_rows === 0): ?>
        <div class="empty-state">No data found.</div>
      <?php else: while ($p = $recentPosts->fetch_assoc()): ?>
        <div class="list-item">
          <div>
            <div class="title"><?= h($p['title']) ?></div>
            <div class="meta"><?= h($p['subject']) ?> &bull; <?= h($p['location']) ?></div>
          </div>
          <span class="badge badge-<?= h($p['status']) ?>"><?= h(ucfirst($p['status'])) ?></span>
        </div>
      <?php endwhile; endif; ?>
      <p style="margin-top:14px;"><a href="tuitions.php">View all posts &rarr;</a></p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
