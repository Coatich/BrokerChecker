<?php
header('Content-Type: application/json');
require_once 'dbconnect.php';

$mc = $_POST['mc'] ?? null;
$comment_text = trim($_POST['comment_text'] ?? '');
$is_general = isset($_POST['is_general']) ? 1 : 0;
$added_by_user = trim($_POST['added_by_user'] ?? '');

if (!$mc || !$comment_text || !$added_by_user) {
    echo json_encode(['success' => false, 'error' => 'Svi podaci su obavezni.']);
    exit;
}

try {
    if ($is_general) {
        // Delete old general comment for this broker
        $del = $pdo->prepare("DELETE FROM comment WHERE mc = ? AND is_general = 1");
        $del->execute([$mc]);
    }
    $stmt = $pdo->prepare("INSERT INTO comment (mc, comment_text, is_general, added_by_user, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$mc, $comment_text, $is_general, $added_by_user]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
}