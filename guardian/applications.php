<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['guardian'], '../login.php', '../index.php');
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_id'], $_POST['decision'])) {
    $appId = (int)$_POST['app_id'];
    $decision = $_POST['decision'] === 'accepted' ? 'accepted' : 'rejected';

    // Ensure this application belongs to one of this guardian's tuition posts
    $stmt = $conn->prepare("UPDATE applications a
        JOIN tuition_posts tp ON tp.id = a.tuition_id
        SET a.status = ?
        WHERE a.id = ? AND tp.guardian_id = ?");
    $stmt->bind_param('sii', $decision, $appId, $user['id']);
    $stmt->execute();
    $stmt->close();
    set_flash('success', 'Application ' . $decision . '.');
    redirect('applications.php');
}

$stmt = $conn->prepare("SELECT a.*, tp.title, tp.subject, tp.class_level, u.name AS tutor_name, u.phone AS tutor_phone, u.id AS tutor_id
                         FROM applications a
                         JOIN tuition_posts tp ON tp.id = a.tuition_id
                         JOIN users u ON u.id = a.tutor_id
                         WHERE tp.guardian_id = ?
                         ORDER BY a.created_at DESC");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$apps = $stmt->get_result();

$page_title = 'Tuition Applications';
$root = '../';
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="page-head">
    <h1>Tuition Applications</h1>
    <p>Review and manage applications from qualified tutors.</p>
  </div>

  <?php if ($apps->num_rows === 0): ?>
    <div class="empty-state">No applications yet. Once tutors apply to your posts, they'll show up here.</div>
  <?php endif; ?>

  <?php while ($a = $apps->fetch_assoc()): ?>
    <div class="result-card">
      <div class="result-top">
        <div>
          <div class="result-title"><?= h($a['tutor_name']) ?></div>
          <div class="result-meta">Applied for: <?= h($a['title']) ?> (<?= h($a['class_level']) ?> &bull; <?= h($a['subject']) ?>)</div>
        </div>
        <span class="badge badge-<?= h($a['status']) ?>"><?= h(ucfirst($a['status'])) ?></span>
      </div>
      <div class="result-meta"><span>Applied on <?= date('M j, Y', strtotime($a['created_at'])) ?></span></div>
      <div class="result-actions">
        <a class="btn btn-outline" href="../messages/inbox.php?with=<?= (int)$a['tutor_id'] ?>&tuition=<?= (int)$a['tuition_id'] ?>">Message Tutor</a>
        <?php if ($a['status'] === 'pending'): ?>
          <form method="post"><input type="hidden" name="app_id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="decision" value="accepted"><button class="btn btn-success" type="submit">Accept</button></form>
          <form method="post"><input type="hidden" name="app_id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="decision" value="rejected"><button class="btn btn-danger" type="submit">Reject</button></form>
        <?php endif; ?>
      </div>
    </div>
  <?php endwhile; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
