<?php
/**
 * voting-api.php - JSON kecil buat angka partisipasi live
 * (dipakai halaman admin voting.php dan layar QR).
 * Tidak pernah mengirim perolehan suara.
 */
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/../config.php';
require __DIR__ . '/includes/voting-helpers.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$pdo = getDbConnection();
$state = votingState($pdo);
$p = votingParticipation($pdo);

echo json_encode([
    'status' => $state['status'],
    'label'  => votingStatusLabel($state['status']),
    'voted'  => $p['voted'],
    'total'  => $p['total'],
]);
