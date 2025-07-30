<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require 'dbconnect.php';

$mc = $_POST['mc'] ?? '';
$setup = $_POST['setup_status'] ?? '';
$our = $_POST['approved_our_status'] ?? '';
$their = $_POST['approved_their_status'] ?? '';

// Convert string values to tinyint for DB
if ($setup === 'Setup completed' || $setup === '1' || $setup === 1) {
    $setup = 1;
} else {
    $setup = 0;
}
if ($our === 'Approved' || $our === '1' || $our === 1) {
    $our = 1;
} else {
    $our = 0;
}
if ($their === 'Approved' || $their === '1' || $their === 1) {
    $their = 1;
} else {
    $their = 0;
}
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
