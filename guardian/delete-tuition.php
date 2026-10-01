<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['guardian'], '../login.php', '../index.php');
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['post_id'] ?? 0);
    $stmt = $conn->prepare('DELETE FROM tuition_posts WHERE id = ? AND guardian_id = ?');
    $stmt->bind_param('ii', $id, $user['id']);
    $stmt->execute();
    $stmt->close();
    set_flash('success', 'Tuition post deleted.');
}
redirect('tuitions.php');
