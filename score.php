<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/recaptcha.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('error' => 'POST required'));
    exit;
}

if (RECAPTCHA_SECRET_KEY === '') {
    http_response_code(503);
    echo json_encode(array('error' => 'Service unavailable'));
    exit;
}

$token = isset($_POST['token']) ? trim((string) $_POST['token']) : '';
if ($token === '' || strlen($token) > 4096) {
    http_response_code(400);
    echo json_encode(array('error' => 'Invalid token'));
    exit;
}

$remoteIp = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
$result = verify_recaptcha_token($token, $remoteIp);

if ($result === null) {
    http_response_code(502);
    echo json_encode(array('error' => 'Verification failed'));
    exit;
}

$success   = isset($result['success']) ? (bool) $result['success'] : false;
$score     = isset($result['score']) ? (float) $result['score'] : 0;
$action    = isset($result['action']) ? (string) $result['action'] : '';
$threshold = (float) RECAPTCHA_SCORE_THRESHOLD;

if (APP_ENV === 'development') {
    echo json_encode(array(
        'success'   => $success,
        'score'     => $score,
        'action'    => $action,
        'threshold' => $threshold,
    ));
} else {
    $passed = $success && $score >= $threshold;
    echo json_encode(array(
        'success' => $success,
        'score'   => $score,
        'passed'  => $passed,
    ));
}
