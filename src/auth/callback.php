<?php

declare(strict_types=1);

session_start();

const TIKTOK_OAUTH_REDIRECT_URI = 'https://ondeck.nodo-digital.com/auth/tiktok/callback';

/**
 * @param array<string, mixed> $payload
 */
function tiktok_save_oauth_state(array $payload): void
{
  $path = TIKTOK_STATE_PATH;
  $dir = dirname($path);
  if (!is_dir($dir)) {
    if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
      throw new RuntimeException('No se pudo crear el directorio de configuración.');
    }
  }

  $existing = [];
  if (file_exists($path)) {
    $raw = (string)file_get_contents($path);
    if (trim($raw) !== '') {
      $decoded = json_decode($raw, true);
      if (is_array($decoded)) {
        $existing = $decoded;
      }
    }
  }

  $merged = array_merge($existing, $payload);
  $merged['updated_at'] = time();

  $json = json_encode($merged, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
  if ($json === false) {
    throw new RuntimeException('No se pudo serializar el estado de TikTok.');
  }

  if (file_put_contents($path, $json, LOCK_EX) === false) {
    throw new RuntimeException('No se pudo escribir config/.tiktok_state.json.');
  }
}

function tiktok_render_html(string $title, string $bodyHtml, int $status = 200): void
{
  http_response_code($status);
  header('Content-Type: text/html; charset=utf-8');
  echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
  echo '<title>' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</title>';
  echo '<style>body{font-family:system-ui,sans-serif;max-width:42rem;margin:2rem auto;padding:0 1rem;line-height:1.5}</style>';
  echo '</head><body>';
  echo $bodyHtml;
  echo '</body></html>';
}

try {
  require_once dirname(__DIR__, 2) . '/config/config.php';

  if (isset($_GET['error'])) {
    $err = (string)$_GET['error'];
    $desc = isset($_GET['error_description']) ? (string)$_GET['error_description'] : '';
    $msg = '<h1>Error de autorización</h1><p>TikTok devolvió: <code>'
      . htmlspecialchars($err, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></p>';
    if ($desc !== '') {
      $msg .= '<p>' . htmlspecialchars($desc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
    }
    tiktok_render_html('Error TikTok OAuth', $msg, 400);
    exit;
  }

  $sessionState = $_SESSION['state'] ?? null;
  $getState = isset($_GET['state']) ? (string)$_GET['state'] : '';

  if ($sessionState === null || $sessionState === '' || !hash_equals((string)$sessionState, $getState)) {
    tiktok_render_html(
      'Acceso denegado',
      '<h1>403 — Estado inválido</h1><p>La verificación de seguridad (state) no coincide. Vuelve a iniciar el flujo desde login.</p>',
      403
    );
    exit;
  }

  unset($_SESSION['state']);

  if (!isset($_GET['code']) || (string)$_GET['code'] === '') {
    tiktok_render_html(
      'Falta el código',
      '<h1>Solicitud incompleta</h1><p>No se recibió el parámetro <code>code</code>.</p>',
      400
    );
    exit;
  }

  $code = (string)$_GET['code'];

  $postFields = http_build_query([
    'client_key' => TIKTOK_CLIENT_KEY,
    'client_secret' => TIKTOK_CLIENT_SECRET,
    'code' => $code,
    'grant_type' => 'authorization_code',
    'redirect_uri' => TIKTOK_OAUTH_REDIRECT_URI,
  ], '', '&', PHP_QUERY_RFC3986);

  $ch = curl_init('https://open.tiktokapis.com/v2/oauth/token/');
  if ($ch === false) {
    throw new RuntimeException('No se pudo inicializar cURL.');
  }

  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postFields,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_TIMEOUT => 60,
  ]);

  $responseBody = curl_exec($ch);
  $curlErr = curl_error($ch);
  $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($responseBody === false) {
    throw new RuntimeException('Error de red al contactar TikTok: ' . $curlErr);
  }

  $decoded = json_decode((string)$responseBody, true);

  if (!is_array($decoded)) {
    tiktok_render_html(
      'Respuesta inválida',
      '<h1>Respuesta no JSON</h1><pre style="white-space:pre-wrap">'
      . htmlspecialchars((string)$responseBody, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
      . '</pre>',
      502
    );
    exit;
  }

  if (isset($decoded['error'])) {
    $detail = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    tiktok_render_html(
      'Error de TikTok',
      '<h1>Error al obtener tokens</h1><p>TikTok devolvió un error en el cuerpo de la respuesta.</p>'
      . '<pre style="white-space:pre-wrap;word-break:break-word;background:#f5f5f5;padding:1rem;border-radius:6px">'
      . htmlspecialchars($detail !== false ? $detail : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
      . '</pre>',
      502
    );
    exit;
  }

  if ($httpCode < 200 || $httpCode >= 300) {
    $detail = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    tiktok_render_html(
      'Error al obtener tokens',
      '<h1>Error HTTP ' . (int)$httpCode . '</h1><p>TikTok no devolvió un token correcto.</p>'
      . '<pre style="white-space:pre-wrap;word-break:break-word;background:#f5f5f5;padding:1rem;border-radius:6px">'
      . htmlspecialchars($detail !== false ? $detail : (string)$responseBody, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
      . '</pre>',
      502
    );
    exit;
  }

  // Respuesta exitosa: campos en la raíz del JSON (documentación TikTok v2 OAuth).
  $accessToken = isset($decoded['access_token']) && is_string($decoded['access_token']) ? $decoded['access_token'] : null;
  $refreshToken = isset($decoded['refresh_token']) && is_string($decoded['refresh_token']) ? $decoded['refresh_token'] : null;

  if ($accessToken === null || $accessToken === '' || $refreshToken === null || $refreshToken === '') {
    tiktok_render_html(
      'Tokens faltantes',
      '<h1>Respuesta sin tokens</h1><pre style="white-space:pre-wrap">'
      . htmlspecialchars(json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
      . '</pre>',
      502
    );
    exit;
  }

  $now = time();
  $expiresAt = $now + 86400;
  $refreshExpiresAt = $now + 31536000;

  if (isset($decoded['expires_in']) && is_numeric($decoded['expires_in'])) {
    $expiresAt = $now + (int)$decoded['expires_in'];
  }
  if (isset($decoded['refresh_expires_in']) && is_numeric($decoded['refresh_expires_in'])) {
    $refreshExpiresAt = $now + (int)$decoded['refresh_expires_in'];
  }

  tiktok_save_oauth_state([
    'access_token' => $accessToken,
    'refresh_token' => $refreshToken,
    'expires_at' => $expiresAt,
    'refresh_expires_at' => $refreshExpiresAt,
  ]);

  $preview = mb_substr($accessToken, 0, 10, 'UTF-8');
  $previewSafe = htmlspecialchars($preview, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

  tiktok_render_html(
    'TikTok conectado',
    '<h1>Autorización correcta</h1>'
    . '<p>Los tokens se guardaron en <code>config/.tiktok_state.json</code>.</p>'
    . '<p>Prefijo del access token (10 caracteres): <code>' . $previewSafe . '…</code></p>'
  );
} catch (Throwable $e) {
  tiktok_render_html(
    'Error',
    '<h1>Error</h1><p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>',
    500
  );
}
