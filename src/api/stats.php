<?php

declare(strict_types=1);

/**
 * Estadísticas públicas (JSON) para el sitio ondeck-site.
 */

$allowedOrigin = 'https://ondeck.nodo-digital.com';
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($requestOrigin === $allowedOrigin) {
  header('Access-Control-Allow-Origin: ' . $allowedOrigin);
  header('Vary: Origin');
}

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  http_response_code(405);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error' => true, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
  exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
  require_once dirname(__DIR__, 2) . '/src/db/Database.php';

  $db = Database::getInstance()->pdo();

  $sql = <<<'SQL'
SELECT
  COUNT(DISTINCT uploader_email) AS participantes,
  COUNT(CASE WHEN status = 'done' THEN 1 END) AS publicados,
  COUNT(DISTINCT country) AS paises,
  COUNT(*) AS archivos_total
FROM queue
SQL;

  $stmt = $db->query($sql);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($row === false) {
    throw new RuntimeException('No se pudieron obtener estadísticas.');
  }

  $payload = [
    'participantes' => (int) $row['participantes'],
    'publicados' => (int) $row['publicados'],
    'paises' => (int) $row['paises'],
    'archivos_total' => (int) $row['archivos_total'],
  ];

  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(
    [
      'error' => true,
      'message' => 'No se pudieron cargar las estadísticas.',
    ],
    JSON_UNESCAPED_UNICODE
  );
}
