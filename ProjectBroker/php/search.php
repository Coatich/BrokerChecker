<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'dbconnect.php';

$apiKey = "8a2d477205ec4f40ae0ca85d26c99a47";

$mc = isset($_GET['mc']) ? trim($_GET['mc']) : null;
$dot = isset($_GET['dot']) ? trim($_GET['dot']) : null;
$ime = isset($_GET['ime']) ? trim($_GET['ime']) : null;

function normalize($str) {
    $str = strtolower($str);
    $str = preg_replace('/[^a-z0-9 ]/', '', $str);
    $str = preg_replace('/\s+/', ' ', $str);
    $str = str_replace(['llc', 'l.l.c', 'inc', 'ltd', 'co', 'corporation', 'corp'], '', $str);
    return trim($str);
}

function extractMcNumber($input) {
    if (preg_match('/MC-(\d+)/', $input, $matches)) {
        return $matches[1];
    }
    return '';
}

function getApprovedStatus($our, $their) {
    return ($our && $their) ? 'Approved' : 'Not approved';
}

// === API poziv ===
if (!$mc && !$dot && !$ime) {
    echo json_encode(['results' => [], 'message' => 'Niste uneli nijedan kriterijum.', 'status' => 'error']);
    exit;
}

$endpoint = '';
if ($mc) {
    $endpoint = "https://saferwebapi.com/v2/mcmx/snapshot/" . ltrim($mc, '0');
} elseif ($dot) {
    $endpoint = "https://saferwebapi.com/v2/usdot/snapshot/" . ltrim($dot, '0');
} elseif ($ime) {
    $imeKratko = explode(' ', $ime)[0]; 
    $endpoint = "https://saferwebapi.com/v2/name/" . urlencode($imeKratko);
}

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $endpoint,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => [
        "x-api-key: $apiKey"
    ]
]);

$response = curl_exec($curl);
curl_close($curl);
$data = json_decode($response, true);

if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['results' => [], 'message' => 'Greška u čitanju API odgovora.', 'status' => 'error']);
    exit;
}

// === Ako tražimo preko MC ili DOT ===
if ($mc || $dot) {
    $ime = $data['legal_name'] ?? '';
    $dot = $data['usdot'] ?? '';
    $mcFromApi = extractMcNumber($data['mc_mx_ff_numbers'] ?? '') ?: ltrim($data['mc'] ?? '', '0');
    $mcToUse = $mcFromApi ?: $mc;
    $mcClean = (int) ltrim($mcToUse, '0');

    $stmt = $pdo->prepare("SELECT setup_status, approved_our_status, approved_their_status, unapproved_reason_our, unapproved_reason_their, updated_at FROM broker WHERE mc = ?");
    $stmt->execute([$mcToUse]);
    $broker = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt2 = $pdo->prepare("SELECT comment_text FROM comment WHERE mc = ? AND is_general = 1 ORDER BY created_at DESC LIMIT 1");
    $stmt2->execute([$mcToUse]);
    $commentRow = $stmt2->fetch(PDO::FETCH_ASSOC);
    $generalComment = $commentRow ? $commentRow['comment_text'] : '';

    $setupStatus = $broker ? ($broker['setup_status'] ? 'Setup completed' : 'Setup not completed') : 'No info';
    $approvedStatus = $broker ? getApprovedStatus($broker['approved_our_status'], $broker['approved_their_status']) : 'No info';
    $updatedAt = $broker['updated_at'] ?? 'No info';

    // === Ako je poziv iz "Edit broker" dugmeta (nema DOT i Ime) ===

    $editMode = isset($_GET['mode']) && $_GET['mode'] === 'edit';
    
    if ($mcToUse && $editMode) {
        echo json_encode([
            'success' => true,
            'broker' => [
                'mc' => $mcClean,
                'setup_status' => $broker['setup_status'] ?? '',
                'approved_our_status' => $broker['approved_our_status'] ?? '',
                'approved_their_status' => $broker['approved_their_status'] ?? '',
                'unapproved_reason_our' => $broker['unapproved_reason_our'] ?? '',
                'unapproved_reason_their' => $broker['unapproved_reason_their'] ?? '',
                'general_comment' => $generalComment,
                'updated_at' => $updatedAt,
                'exists_in_db' => (bool) $broker
            ]
        ]);
        exit;
    }

    // === Inače, pretraga za tabelu ===
    echo json_encode([
        'results' => [[
            'ime' => $ime,
            'mc' => $mcClean,
            'dot' => $dot,
            'setup_status' => $setupStatus,
            'approved_status' => $approvedStatus,
            'updated_at' => $updatedAt,
            'general_comment' => $generalComment,
            'exists_in_db' => (bool) $broker
        ]]
    ]);
    exit;
}

// === Ako je pretraga po imenu ===
if ($ime) {
    $imeTrazeno = normalize($ime);
    $rezultati = [];

    if (isset($data['name'])) {
        $data = [$data]; // API vratio samo jedan objekat
    }

    foreach ($data as $firma) {
        $legalName = $firma['name'] ?? '';
        $normalized = normalize($legalName);
        similar_text($imeTrazeno, $normalized, $percent);

        $firma['match_score'] = $percent;
        $firma['legal_name'] = $legalName;
        $rezultati[] = $firma;
    }

    usort($rezultati, fn($a, $b) => $b['match_score'] <=> $a['match_score']);
    $prikazi = array_filter($rezultati, fn($f) => $f['match_score'] >= 99.99) ?: [$rezultati[0]];

    $responseArray = [];
    foreach ($prikazi as $firma) {
        $ime = $firma['legal_name'];
        $dot = $firma['usdot'] ?? '';
        $mc = extractMcNumber($firma['mc_mx_ff_numbers'] ?? '') ?: ltrim($firma['mc'] ?? '', '0');

        $stmt = $pdo->prepare("SELECT setup_status, approved_our_status, approved_their_status, unapproved_reason_our, unapproved_reason_their, updated_at FROM broker WHERE mc = ?");
        $stmt->execute([$mc]);
        $broker = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt2 = $pdo->prepare("SELECT comment_text FROM comment WHERE mc = ? AND is_general = 1 ORDER BY created_at DESC LIMIT 1");
        $stmt2->execute([$mc]);
        $commentRow = $stmt2->fetch(PDO::FETCH_ASSOC);
        $generalComment = $commentRow ? $commentRow['comment_text'] : '';

        $setupStatus = $broker ? ($broker['setup_status'] ? 'Setup completed' : 'Setup not completed') : 'No info';
        $approvedStatus = $broker ? getApprovedStatus($broker['approved_our_status'], $broker['approved_their_status']) : 'No info';
        $updatedAt = $broker['updated_at'] ?? 'No info';

        $responseArray[] = [
            'ime' => $ime,
            'mc' => $mc,
            'dot' => $dot,
            'setup_status' => $setupStatus,
            'approved_status' => $approvedStatus,
            'updated_at' => $updatedAt,
            'general_comment' => $generalComment,
            'exists_in_db' => (bool) $broker
        ];
    }

    echo json_encode(['results' => $responseArray]);
    exit;
}
