<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin'], '../login.php', '../index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['post_id'])) {
    $id = (int)$_POST['post_id'];
    if ($_POST['action'] === 'delete') {
        $conn->query("DELETE FROM tuition_posts WHERE id=$id");
        set_flash('success', 'Tuition post deleted.');
    } elseif ($_POST['action'] === 'close') {
        $conn->query("UPDATE tuition_posts SET status='closed' WHERE id=$id");
        set_flash('success', 'Tuition post closed.');
    } elseif ($_POST['action'] === 'activate') {
        $conn->query("UPDATE tuition_posts SET status='active' WHERE id=$id");
        set_flash('success', 'Tuition post activated.');
    }
    redirect('manage-tuitions.php');
}

$posts = $conn->query("
  SELECT tp.*, u.name AS guardian_name, u.phone AS guardian_phone,
         (SELECT COUNT(*) FROM applications a WHERE a.tuition_id = tp.id) AS applicant_count
  FROM tuition_posts tp
  JOIN users u ON u.id = tp.guardian_id
  ORDER BY tp.created_at DESC
");

$page_title = 'Manage Tuition Posts';
$root = '../';
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="page-head">
    <h1>Manage Tuition Posts</h1>
    <p>Moderate all tuition requirements posted on the platform.</p>
  </div>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Title</th><th>Guardian</th><th>Location</th><th>Budget</th><th>Applicants</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if ($posts->num_rows === 0): ?>
          <tr><td colspan="7"><div class="empty-state">No tuition posts yet.</div></td></tr>
        <?php endif; ?>
        <?php while ($p = $posts->fetch_assoc()): ?>
          <tr>
            <td><?= h($p['title']) ?></td>
            <td><?= h($p['guardian_name']) ?><br><small style="color:var(--muted);"><?= h($p['guardian_phone']) ?></small></td>
            <td><?= h($p['location']) ?></td>
            <td>&#2547;<?= (int)$p['budget'] ?></td>
            <td><?= (int)$p['applicant_count'] ?></td>
            <td><span class="badge badge-<?= h($p['status']) ?>"><?= h(ucfirst($p['status'])) ?></span></td>
            <td class="actions-inline">
              <?php if ($p['status'] !== 'active'): ?>
                <form method="post"><input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="action" value="activate"><button class="btn btn-success btn-sm" type="submit">Activate</button></form>
              <?php else: ?>
                <form method="post"><input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="action" value="close"><button class="btn btn-outline btn-sm" type="submit">Close</button></form>
              <?php endif; ?>
              <form method="post" onsubmit="return confirm('Permanently delete this tuition post?');">
                <input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="action" value="delete">
                <button class="btn btn-danger btn-sm" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
