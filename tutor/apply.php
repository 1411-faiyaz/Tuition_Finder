<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['tutor'], '../login.php', '../index.php');
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('browse-tuitions.php');
}

$tuition_id = (int)($_POST['tuition_id'] ?? 0);

if ($user['status'] !== 'approved') {
    set_flash('error', 'Your profile must be approved by admin before you can apply.');
    redirect('browse-tuitions.php');
}

// Confirm the tuition post exists and is active
$stmt = $conn->prepare("SELECT id FROM tuition_posts WHERE id = ? AND status = 'active'");
$stmt->bind_param('i', $tuition_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    set_flash('error', 'This tuition post is no longer available.');
    redirect('browse-tuitions.php');
}
$stmt->close();

$stmt = $conn->prepare('INSERT INTO applications (tuition_id, tutor_id) VALUES (?, ?)');
$stmt->bind_param('ii', $tuition_id, $user['id']);

if ($stmt->execute()) {
    set_flash('success', 'Application submitted successfully!');
} else {
    // duplicate key = already applied
    set_flash('error', 'You have already applied to this tuition.');
}
$stmt->close();
redirect('browse-tuitions.php');
