<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['tutor', 'guardian'], '../login.php', '../index.php');
header('Content-Type: application/json');
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid method']);
    exit;
}

$receiverId = (int)($_POST['receiver_id'] ?? 0);
$body       = trim($_POST['body'] ?? '');
$tuitionId  = !empty($_POST['tuition_id']) ? (int)$_POST['tuition_id'] : null;

if ($body === '' || $receiverId <= 0 || $receiverId === (int)$user['id']) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid message']);
    exit;
}

$stmt = $conn->prepare('INSERT INTO messages (sender_id, receiver_id, tuition_id, body) VALUES (?,?,?,?)');
$stmt->bind_param('iiis', $user['id'], $receiverId, $tuitionId, $body);
$stmt->execute();
$newId = $stmt->insert_id;
$stmt->close();

echo json_encode([
    'id'   => $newId,
    'body' => $body,
    'time' => date('M j, g:i A'),
]);