<?php
require 'db_connect.php';

$mc = $_POST['mc'] ?? '';
$setup = $_POST['setup_status'] ?? '';
$our = $_POST['approved_our_status'] ?? '';
$their = $_POST['approved_their_status'] ?? '';
$reason_our = $_POST['unapproved_reason_our'] ?? null;
$reason_their = $_POST['unapproved_reason_their'] ?? null;

if (!$mc) {
  echo json_encode(['success' => false, 'error' => 'MC missing']);
  exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM broker WHERE mc = ?");
$stmt->execute([$mc]);

if ($stmt->fetchColumn() > 0) {
  $update = $pdo->prepare("UPDATE broker SET setup_status=?, approved_our_status=?, unapproved_reason_our=?, approved_their_status=?, unapproved_reason_their=? WHERE mc=?");
  $success = $update->execute([$setup, $our, $reason_our, $their, $reason_their, $mc]);
} else {
  $insert = $pdo->prepare("INSERT INTO broker (mc, setup_status, approved_our_status, unapproved_reason_our, approved_their_status, unapproved_reason_their) VALUES (?, ?, ?, ?, ?, ?)");
  $success = $insert->execute([$mc, $setup, $our, $reason_our, $their, $reason_their]);
}

echo json_encode(['success' => $success]);
