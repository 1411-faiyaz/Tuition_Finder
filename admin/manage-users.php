<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin'], '../login.php', '../index.php');

// Handle approve / reject / delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
    $id = (int)$_POST['user_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $conn->query("UPDATE users SET status='approved' WHERE id=$id");
        set_flash('success', 'User approved.');
    } elseif ($action === 'reject') {
        $conn->query("UPDATE users SET status='rejected' WHERE id=$id");
        set_flash('success', 'User rejected.');
    } elseif ($action === 'delete') {
        // Don't allow deleting the admin account itself
        $conn->query("DELETE FROM users WHERE id=$id AND role != 'admin'");
        set_flash('success', 'User deleted.');
    }
    redirect('manage-users.php?type=' . urlencode($_GET['type'] ?? 'tutor'));
}

$type = ($_GET['type'] ?? 'tutor') === 'guardian' ? 'guardian' : 'tutor';
$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT * FROM users WHERE role = ?";
$params = [$type];
$types = 's';
if (in_array($statusFilter, ['pending', 'approved', 'rejected'], true)) {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}
$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result();

$page_title = 'Manage Users';
$root = '../';
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="page-head">
    <h1>Manage Users</h1>
    <p>Approve tutor profiles and manage all platform accounts.</p>
  </div>

  <div class="tabs">
    <a href="?type=tutor" class="<?= $type === 'tutor' ? 'active' : '' ?>">Tutors</a>
    <a href="?type=guardian" class="<?= $type === 'guardian' ? 'active' : '' ?>">Guardians</a>
  </div>

  <?php if ($type === 'tutor'): ?>
    <div class="tabs" style="margin-top:-10px;">
      <a href="?type=tutor" class="<?= $statusFilter === '' ? 'active' : '' ?>">All</a>
      <a href="?type=tutor&status=pending" class="<?= $statusFilter === 'pending' ? 'active' : '' ?>">Pending</a>
      <a href="?type=tutor&status=approved" class="<?= $statusFilter === 'approved' ? 'active' : '' ?>">Approved</a>
      <a href="?type=tutor&status=rejected" class="<?= $statusFilter === 'rejected' ? 'active' : '' ?>">Rejected</a>
    </div>
  <?php endif; ?>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
      <tbody>
        <?php if ($users->num_rows === 0): ?>
          <tr><td colspan="6"><div class="empty-state">No users found.</div></td></tr>
        <?php endif; ?>
        <?php while ($u = $users->fetch_assoc()): ?>
          <tr>
            <td><?= h($u['name']) ?></td>
            <td><?= h($u['phone']) ?></td>
            <td><?= h($u['email'] ?: '—') ?></td>
            <td><span class="badge badge-<?= h($u['status']) ?>"><?= h(ucfirst($u['status'])) ?></span></td>
            <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
            <td class="actions-inline">
              <?php if ($type === 'tutor' && $u['status'] !== 'approved'): ?>
                <form method="post"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="action" value="approve"><button class="btn btn-success btn-sm" type="submit">Approve</button></form>
              <?php endif; ?>
              <?php if ($type === 'tutor' && $u['status'] !== 'rejected'): ?>
                <form method="post"><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="action" value="reject"><button class="btn btn-outline btn-sm" type="submit">Reject</button></form>
              <?php endif; ?>
              <form method="post" onsubmit="return confirm('Permanently delete this user and all related data?');">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="action" value="delete">
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
