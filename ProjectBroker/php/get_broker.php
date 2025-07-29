<?php
header('Content-Type: application/json');

if (!isset($_GET['mc'])) {
  echo json_encode(['success' => false, 'error' => 'MC not provided']);
  exit;
}

$mc = $_GET['mc'];

include_once("dbconnect.php");

$stmt = $pdo->prepare('SELECT * FROM broker WHERE mc = ?');
$stmt->execute([$mc]);
$broker = $stmt->fetch(PDO::FETCH_ASSOC);

if ($broker) {
  echo json_encode(['success' => true, 'broker' => $broker]);
} else {
  echo json_encode(['success' => false, 'error' => 'Broker not found']);
}
