<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin'], '../login.php', '../index.php');
$user = current_user();

$totalTutors    = $conn->query("SELECT COUNT(*) c FROM users WHERE role='tutor'")->fetch_assoc()['c'];
$pendingTutors  = $conn->query("SELECT COUNT(*) c FROM users WHERE role='tutor' AND status='pending'")->fetch_assoc()['c'];
$totalGuardians = $conn->query("SELECT COUNT(*) c FROM users WHERE role='guardian'")->fetch_assoc()['c'];
$totalPosts     = $conn->query("SELECT COUNT(*) c FROM tuition_posts")->fetch_assoc()['c'];
$activePosts    = $conn->query("SELECT COUNT(*) c FROM tuition_posts WHERE status='active'")->fetch_assoc()['c'];
$totalApps      = $conn->query("SELECT COUNT(*) c FROM applications")->fetch_assoc()['c'];
$acceptedApps   = $conn->query("SELECT COUNT(*) c FROM applications WHERE status='accepted'")->fetch_assoc()['c'];

$pendingList = $conn->query("SELECT id, name, phone, created_at FROM users WHERE role='tutor' AND status='pending' ORDER BY created_at DESC LIMIT 5");

$page_title = 'Admin Dashboard';
$root = '../';
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="page-head">
    <h1>Admin Dashboard</h1>
    <p>Platform overview and pending actions.</p>
  </div>

  <div class="stat-grid">
    <div class="stat-card"><div class="num"><?= (int)$totalTutors ?></div><div class="label">Total Tutors</div></div>
    <div class="stat-card"><div class="num"><?= (int)$totalGuardians ?></div><div class="label">Total Guardians</div></div>
    <div class="stat-card"><div class="num"><?= (int)$activePosts ?> / <?= (int)$totalPosts ?></div><div class="label">Active / Total Tuition Posts</div></div>
    <div class="stat-card"><div class="num"><?= (int)$acceptedApps ?> / <?= (int)$totalApps ?></div><div class="label">Accepted / Total Applications</div></div>
  </div>

  <div class="dash-grid">
    <div class="panel">
      <h3>Pending Tutor Approvals (<?= (int)$pendingTutors ?>)</h3>
      <?php if ($pendingList->num_rows === 0): ?>
        <div class="empty-state">No pending approvals. 🎉</div>
      <?php else: while ($p = $pendingList->fetch_assoc()): ?>
        <div class="list-item">
          <div>
            <div class="title"><?= h($p['name']) ?></div>
            <div class="meta"><?= h($p['phone']) ?> &bull; Joined <?= date('M j, Y', strtotime($p['created_at'])) ?></div>
          </div>
          <span class="badge badge-pending">Pending</span>
        </div>
      <?php endwhile; endif; ?>
      <p style="margin-top:14px;"><a href="manage-users.php?type=tutor&status=pending">Review all pending tutors &rarr;</a></p>
    </div>

    <div class="panel">
      <h3>Quick Actions</h3>
      <p><a href="manage-users.php?type=tutor" class="btn btn-outline btn-block" style="margin-bottom:10px;">Manage Tutors</a></p>
      <p><a href="manage-users.php?type=guardian" class="btn btn-outline btn-block" style="margin-bottom:10px;">Manage Guardians</a></p>
      <p><a href="manage-tuitions.php" class="btn btn-primary btn-block">Manage Tuition Posts</a></p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
