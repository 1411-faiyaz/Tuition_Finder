<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['guardian'], '../login.php', '../index.php');
$user = current_user();

// Quick status change (pause/activate/close) submitted from this page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    $id = (int)$_POST['post_id'];
    $newStatus = $_POST['change_status'];
    if (in_array($newStatus, ['active', 'paused', 'closed'], true)) {
        $stmt = $conn->prepare('UPDATE tuition_posts SET status = ? WHERE id = ? AND guardian_id = ?');
        $stmt->bind_param('sii', $newStatus, $id, $user['id']);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'Tuition post updated.');
    }
    redirect('tuitions.php');
}

$live = $conn->query("SELECT COUNT(*) c FROM tuition_posts WHERE guardian_id={$user['id']} AND status='active'")->fetch_assoc()['c'];
$paused = $conn->query("SELECT COUNT(*) c FROM tuition_posts WHERE guardian_id={$user['id']} AND status='paused'")->fetch_assoc()['c'];
$closed = $conn->query("SELECT COUNT(*) c FROM tuition_posts WHERE guardian_id={$user['id']} AND status='closed'")->fetch_assoc()['c'];
$totalApps = $conn->query("SELECT COUNT(*) c FROM applications a JOIN tuition_posts tp ON tp.id=a.tuition_id WHERE tp.guardian_id={$user['id']}")->fetch_assoc()['c'];

$stmt = $conn->prepare("SELECT tp.*, (SELECT COUNT(*) FROM applications a WHERE a.tuition_id=tp.id) AS applicant_count
                         FROM tuition_posts tp WHERE tp.guardian_id = ? ORDER BY tp.created_at DESC");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$posts = $stmt->get_result();

$page_title = 'My Tuition Posts';
$root = '../';
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="page-head" style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:14px;">
    <div>
      <h1>My Tuition Posts</h1>
      <p>Manage your tuition requirements and track applications.</p>
    </div>
    <a href="post-tuition.php" class="btn btn-primary">+ Post New Tuition</a>
  </div>

  <div class="stat-grid">
    <div class="stat-card"><div class="num"><?= (int)$live ?></div><div class="label">Active Posts</div></div>
    <div class="stat-card"><div class="num"><?= (int)$paused ?></div><div class="label">Paused Posts</div></div>
    <div class="stat-card"><div class="num"><?= (int)$closed ?></div><div class="label">Closed Posts</div></div>
    <div class="stat-card"><div class="num"><?= (int)$totalApps ?></div><div class="label">Total Applications</div></div>
  </div>

  <?php if ($posts->num_rows === 0): ?>
    <div class="empty-state">
      You haven't posted any tuition requirements yet.<br><br>
      <a href="post-tuition.php" class="btn btn-primary">+ Post New Tuition</a>
    </div>
  <?php endif; ?>

  <?php while ($p = $posts->fetch_assoc()): ?>
    <div class="result-card">
      <div class="result-top">
        <div>
          <div class="result-title"><?= h($p['title']) ?></div>
          <div class="result-meta"><?= (int)$p['applicant_count'] ?> tutor(s) applied &bull; Posted <?= date('M j, Y', strtotime($p['created_at'])) ?></div>
        </div>
        <span class="badge badge-<?= h($p['status']) ?>"><?= h(ucfirst($p['status'])) ?></span>
      </div>
      <div class="result-tags">
        <span class="tag"><?= h($p['class_level']) ?></span>
        <span class="tag"><?= h($p['subject']) ?></span>
        <span class="tag"><?= h($p['location']) ?></span>
      </div>
      <div class="result-meta"><span class="price">&#2547;<?= (int)$p['budget'] ?>/month</span></div>
      <div class="result-actions">
        <a class="btn btn-outline" href="edit-tuition.php?id=<?= (int)$p['id'] ?>">Edit</a>
        <?php if ($p['status'] === 'active'): ?>
          <form method="post"><input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="change_status" value="paused"><button class="btn btn-outline" type="submit">Pause</button></form>
        <?php elseif ($p['status'] === 'paused'): ?>
          <form method="post"><input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="change_status" value="active"><button class="btn btn-outline" type="submit">Activate</button></form>
        <?php endif; ?>
        <?php if ($p['status'] !== 'closed'): ?>
          <form method="post"><input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="change_status" value="closed"><button class="btn btn-outline" type="submit">Close</button></form>
        <?php endif; ?>
        <form method="post" action="delete-tuition.php" onsubmit="return confirm('Delete this tuition post permanently? This also removes related applications.');">
          <input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>">
          <button class="btn btn-danger" type="submit">Delete</button>
        </form>
      </div>
    </div>
  <?php endwhile; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
