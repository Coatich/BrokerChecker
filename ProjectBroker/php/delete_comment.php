<?php
header('Content-Type: application/json');
require_once 'dbconnect.php';

$id = $_POST['id'] ?? null;
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Missing comment ID.']);
    exit;
}
try {
    $stmt = $pdo->prepare('DELETE FROM comment WHERE id = ?');
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
}
