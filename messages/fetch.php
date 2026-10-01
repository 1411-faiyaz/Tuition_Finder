<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['tutor', 'guardian'], '../login.php', '../index.php');
header('Content-Type: application/json');
$user = current_user();

$withId  = (int)($_GET['with'] ?? 0);
$afterId = (int)($_GET['after'] ?? 0);

if ($withId <= 0) {
    echo json_encode(['messages' => []]);
    exit;
}

$stmt = $conn->prepare("SELECT id, sender_id, body, created_at FROM messages
  WHERE ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)) AND id > ?
  ORDER BY created_at ASC");
$stmt->bind_param('iiiii', $user['id'], $withId, $withId, $user['id'], $afterId);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($m = $result->fetch_assoc()) {
    $messages[] = [
        'id'   => (int)$m['id'],
        'mine' => (int)$m['sender_id'] === (int)$user['id'],
        'body' => $m['body'],
        'time' => date('M j, g:i A', strtotime($m['created_at'])),
    ];
}
$stmt->close();

$conn->query("UPDATE messages SET is_read=1 WHERE sender_id=$withId AND receiver_id={$user['id']}");

echo json_encode(['messages' => $messages]);