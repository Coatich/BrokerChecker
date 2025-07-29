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

// Odredi endpoint
if ($mc) {
    $endpoint = "https://saferwebapi.com/v2/mcmx/snapshot/" . ltrim($mc, '0');
} elseif ($dot) {
    $endpoint = "https://saferwebapi.com/v2/usdot/snapshot/" . ltrim($dot, '0');
} elseif ($ime) {
    // koristi prvu reč kao ključnu za API pretragu
    $imeKratko = explode(' ', $ime)[0]; 
    $endpoint = "https://saferwebapi.com/v2/name/" . urlencode($imeKratko);
} else {
    echo json_encode([
    'results' => [],
    'message' => 'Niste uneli nijedan kriterijum za pretragu. (mc, dot ili ime)',
    'status' => 'error'
]);
exit;
}

// Poziv API-ja
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
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    echo "❌ Greška pri povezivanju: $err";
    exit;
}


$data = json_decode($response, true);
// Debug: log will be written after combining API and DB response

if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
    'results' => [],
    'message' => 'Nema podataka ili nevalidan JSON od API-ja.',
    'status' => 'error'
]);
exit;
}

// === Obrada za pretragu po MC ili DOT ===
// If API response is a single company (has mc_mx_ff_numbers or mc), use MC for DB lookup

$mcFromApi = '';
if (!empty($data['mc_mx_ff_numbers'])) {
    $numbers = explode(',', $data['mc_mx_ff_numbers']);
    foreach ($numbers as $num) {
        $num = trim($num);
        if (preg_match('/^MC-(\d+)$/', $num, $m)) {
            $mcFromApi = $m[1];
            break;
        }
    }
}
if (!$mcFromApi && isset($data['mc'])) {
    $mcFromApi = $data['mc'];
}
$dotFromApi = $data['usdot'] ?? '';
$nameFromApi = $data['legal_name'] ?? '';

if ($mcFromApi) {
    // Debug: log MC type and value
    file_put_contents(__DIR__ . '/debug.log', "MC from API (raw): " . var_export($mcFromApi, true) . " type: " . gettype($mcFromApi) . "\n", FILE_APPEND);
    $mcClean = (int)$mcFromApi;
    file_put_contents(__DIR__ . '/debug.log', "MC used for DB lookup (int): " . var_export($mcClean, true) . " type: " . gettype($mcClean) . "\n", FILE_APPEND);
    $stmt = $pdo->prepare("SELECT setup_status, approved_our_status, unapproved_reason_our, approved_their_status, unapproved_reason_their, updated_at FROM broker WHERE mc = ?");
    $stmt->execute([$mcClean]);
    $broker = $stmt->fetch(PDO::FETCH_ASSOC);
    file_put_contents(__DIR__ . '/debug.log', "DB result: " . print_r($broker, true) . "\n", FILE_APPEND);
    if ($broker) {
        $stmt2 = $pdo->prepare("SELECT comment_text FROM comment WHERE mc = ? AND is_general = 1 ORDER BY created_at DESC LIMIT 1");
        $stmt2->execute([$mcClean]);
        $commentRow = $stmt2->fetch(PDO::FETCH_ASSOC);
        $generalComment = $commentRow ? $commentRow['comment_text'] : '';
        $setupStatus = $broker['setup_status'] ? 'Yes' : 'No';
        $approvedStatus = ($broker['approved_our_status'] && $broker['approved_their_status']) ? 'Approved' : 'Not approved';
        $result = [
            'mc' => $mcClean,
            'dot' => $dotFromApi,
            'ime' => $nameFromApi,
            'setup_status' => $setupStatus,
            'approved_status' => $approvedStatus,
            'updated_at' => $broker['updated_at'],
            'general_comment' => $generalComment,
            'exists_in_db' => true
        ];
    } else {
        $result = [
            'mc' => $mcClean,
            'dot' => $dotFromApi,
            'ime' => $nameFromApi,
            'setup_status' => 'no info',
            'approved_status' => 'no info',
            'updated_at' => '-',
            'general_comment' => 'no comment',
            'exists_in_db' => false
        ];
    }
    // Log combined API and DB response
    file_put_contents(__DIR__ . '/debug.log', "\n==== Combined API+DB response ===\n" . print_r($result, true), FILE_APPEND);
    // Log combined API and DB response
    file_put_contents(__DIR__ . '/debug.log', "\n==== Combined API+DB response ===\n" . print_r($result, true), FILE_APPEND);
    echo json_encode(['results' => [$result]]);
    exit;
}

// === Obrada za pretragu po imenu ===
$rezultati = [];
$imeTrazeno = normalize($ime);

if ($ime) {
    if (isset($data['name'])) {
        // Ako je $data jedan objekat, zamotaj u niz
        $data = [$data];
    } elseif (is_array($data) && !empty($data)) {
        // Ako je $data niz
        // Proveri da prvi element ima 'name'
        if (!isset($data[0]['name'])) {
            echo "⚠️ API nije vratio listu firmi za ime: $ime";
            exit;
        }
    } else {
    echo json_encode([
        'results' => [],
        'message' => "API nije vratio listu firmi za ime: $ime",
        'status' => 'not_found'
    ]);
    exit;
    }
}

// Izračunaj sličnost za svaku firmu
foreach ($data as $firma) {
    $legalName = $firma['name'] ?? '';
    $normalized = normalize($legalName);
    similar_text($imeTrazeno, $normalized, $percent);

    $firma['match_score'] = $percent;
    $firma['legal_name'] = $legalName; // za prikaz
    $rezultati[] = $firma;
}

// Sortiraj po sličnosti (najveće prvo)
usort($rezultati, fn($a, $b) => $b['match_score'] <=> $a['match_score']);

// Traži savršene poklapanja
$perfectMatches = array_filter($rezultati, fn($firma) => round($firma['match_score'], 2) >= 99.99);

if (!empty($perfectMatches)) {
    $prikazi = $perfectMatches;
} else {
    $najboljiProcenat = $rezultati[0]['match_score'];
    $prikazi = array_filter($rezultati, fn($firma) => $firma['match_score'] === $najboljiProcenat);

    // echo "⚠️ Nema savršenih poklapanja (100%). Prikazujemo najbolje podudaranje ({$najboljiProcenat}%):<br><br>";
}

// Build response array for each match
$responseArray = [];
foreach ($prikazi as $firma) {
    $ime = $firma['legal_name'];
    $dot = $firma['usdot'] ?? '';
    $mc = extractMcNumber($firma['mc_mx_ff_numbers'] ?? '');

    if (!$mc && !empty($dot)) {
        $detailEndpoint = "https://saferwebapi.com/v2/usdot/snapshot/" . ltrim($dot, '0');
        $detailCurl = curl_init();
        curl_setopt_array($detailCurl, [
            CURLOPT_URL => $detailEndpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                "x-api-key: $apiKey"
            ]
        ]);
        $detailResponse = curl_exec($detailCurl);
        curl_close($detailCurl);
        $detailData = json_decode($detailResponse, true);
        $mc = extractMcNumber($detailData['mc_mx_ff_numbers'] ?? '');
        if (!$mc && isset($detailData['mc'])) {
            $mc = ltrim($detailData['mc'], '0');
        }
    }

    if ($mc) {
        $stmt = $pdo->prepare("SELECT setup_status, approved_our_status, approved_their_status, updated_at FROM broker WHERE mc = ?");
        $stmt->execute([$mc]);
        $broker = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($broker) {
            $approvedStatus = getApprovedStatus($broker['approved_our_status'], $broker['approved_their_status']);
            $setupStatus = $broker['setup_status'] ? 'Setup completed' : 'Setup not completed';
            $updatedAt = $broker['updated_at'];
            $stmt2 = $pdo->prepare("SELECT comment_text FROM comment WHERE mc = ? AND is_general = 1 ORDER BY created_at DESC LIMIT 1");
            $stmt2->execute([$mc]);
            $commentRow = $stmt2->fetch(PDO::FETCH_ASSOC);
            $generalComment = $commentRow ? $commentRow['comment_text'] : '';
        } else {
            $approvedStatus = 'No info';
            $setupStatus = 'No info';
            $updatedAt = 'No info';
            $generalComment = '';
        }
    } else {
        $approvedStatus = 'No info';
        $setupStatus = 'No info';
        $updatedAt = 'No info';
        $generalComment = '';
        $broker = null;
    }

    $responseArray[] = [
        'ime' => $ime,
        'dot' => $dot,
        'mc' => $mc,
        'setup_status' => $setupStatus,
        'approved_status' => $approvedStatus,
        'updated_at' => $updatedAt,
        'general_comment' => $generalComment,
        'exists_in_db' => $broker ? true : false
    ];
}

// Debug
file_put_contents(__DIR__ . '/debug.log', "\n==== Combined API+DB response (name search) ===\n" . print_r($responseArray, true), FILE_APPEND);
echo json_encode(['results' => $responseArray]);
exit;