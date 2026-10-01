<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['tutor'], '../login.php', '../index.php');
$user = current_user();

// Withdraw application (Delete in CRUD)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw_id'])) {
    $id = (int)$_POST['withdraw_id'];
    $stmt = $conn->prepare("DELETE FROM applications WHERE id = ? AND tutor_id = ? AND status = 'pending'");
    $stmt->bind_param('ii', $id, $user['id']);
    $stmt->execute();
    $stmt->close();
    set_flash('success', 'Application withdrawn.');
    redirect('my-applications.php');
}

$tab = $_GET['tab'] ?? 'applied';
$statusFilter = ['applied' => null, 'confirmed' => 'accepted', 'rejected' => 'rejected'];

$sql = "SELECT a.*, tp.title, tp.subject, tp.class_level, tp.location, tp.budget, tp.status AS post_status, u.name AS guardian_name
        FROM applications a
        JOIN tuition_posts tp ON tp.id = a.tuition_id
        JOIN users u ON u.id = tp.guardian_id
        WHERE a.tutor_id = ?";
if ($statusFilter[$tab] ?? null) {
    $sql .= " AND a.status = '" . $conn->real_escape_string($statusFilter[$tab]) . "'";
}
$sql .= " ORDER BY a.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$apps = $stmt->get_result();

$page_title = 'My Activity';
$root = '../';
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="page-head">
    <h1>My Tuitions</h1>
    <p>Track your tuition applications and their current status.</p>
  </div>

  <div class="tabs">
    <a href="?tab=applied" class="<?= $tab === 'applied' ? 'active' : '' ?>">All Applied</a>
    <a href="?tab=confirmed" class="<?= $tab === 'confirmed' ? 'active' : '' ?>">Confirmed</a>
    <a href="?tab=rejected" class="<?= $tab === 'rejected' ? 'active' : '' ?>">Rejected</a>
  </div>

  <?php if ($apps->num_rows === 0): ?>
    <div class="empty-state">No applications found in this tab.</div>
  <?php endif; ?>

  <?php while ($a = $apps->fetch_assoc()): ?>
    <div class="result-card">
      <div class="result-top">
        <div>
          <div class="result-title"><?= h($a['title']) ?></div>
          <div class="result-meta">Guardian: <?= h($a['guardian_name']) ?></div>
        </div>
        <span class="badge badge-<?= h($a['status']) ?>"><?= h(ucfirst($a['status'])) ?></span>
      </div>
      <div class="result-tags">
        <span class="tag"><?= h($a['class_level']) ?></span>
        <span class="tag"><?= h($a['subject']) ?></span>
      </div>
      <div class="result-meta">
        <span>📍 <?= h($a['location']) ?></span>
        <span class="price">&#2547;<?= (int)$a['budget'] ?>/month</span>
        <span>Applied on <?= date('M j, Y', strtotime($a['created_at'])) ?></span>
      </div>
      <div class="result-actions">
        <a class="btn btn-outline" href="../messages/inbox.php">Message Guardian</a>
        <?php if ($a['status'] === 'pending'): ?>
          <form method="post" onsubmit="return confirm('Withdraw this application?');">
            <input type="hidden" name="withdraw_id" value="<?= (int)$a['id'] ?>">
            <button type="submit" class="btn btn-danger">Withdraw</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endwhile; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
