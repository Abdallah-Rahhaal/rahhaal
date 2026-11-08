<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input') ?: '{}', true);
$visitorId = is_array($input) ? (string)($input['visitor_id'] ?? '') : '';
if ($visitorId === '' || strlen($visitorId) > 160) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_visitor_id']);
    exit;
}

$config = require __DIR__ . '/db-config.php';
try {
    $dsn = 'mysql:host=' . $config['host'] . ';dbname=' . $config['database'] . ';charset=utf8mb4';
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // لا نخزن المعرّف الخام؛ نخزن بصمة SHA-256 فقط.
    $visitorHash = hash('sha256', $visitorId);
    $stmt = $pdo->prepare(
        'INSERT INTO rahhal_visitors (visitor_hash, first_seen, last_seen) VALUES (?, NOW(), NOW()) '
        . 'ON DUPLICATE KEY UPDATE last_seen = NOW()'
    );
    $stmt->execute([$visitorHash]);

    $count = (int)$pdo->query('SELECT COUNT(*) FROM rahhal_visitors')->fetchColumn();
    echo json_encode(['count' => $count], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'counter_unavailable']);
}
