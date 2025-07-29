<?php
header('Content-Type: application/json');
require_once 'dbconnect.php';
$mc = $_GET['mc'] ?? null;
if (!$mc) {
    echo json_encode(['success' => false, 'error' => 'MC missing']);
    exit;
}
$stmt = $pdo->prepare("SELECT id, comment_text, added_by_user, created_at FROM comment WHERE mc = ? AND is_general = 0 ORDER BY created_at DESC");
$stmt->execute([$mc]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success' => true, 'comments' => $comments]);
