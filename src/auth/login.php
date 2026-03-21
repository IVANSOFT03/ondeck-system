<?php

declare(strict_types=1);

session_start();

try {
  require_once dirname(__DIR__, 2) . '/config/config.php';

  $state = bin2hex(random_bytes(16));
  $_SESSION['state'] = $state;

  $redirectUri = 'https://ondeck.nodo-digital.com/auth/tiktok/callback';

  $query = http_build_query([
    'client_key' => TIKTOK_CLIENT_KEY,
    'response_type' => 'code',
    'scope' => 'user.info.basic,video.upload',
    'redirect_uri' => $redirectUri,
    'state' => $state,
  ], '', '&', PHP_QUERY_RFC3986);

  $authorizeUrl = 'https://www.tiktok.com/v2/auth/authorize/?' . $query;

  header('Location: ' . $authorizeUrl, true, 302);
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  header('Content-Type: text/html; charset=utf-8');
  echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Error</title></head><body>';
  echo '<p>No se pudo iniciar el inicio de sesión con TikTok.</p>';
  echo '<p><small>' . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</small></p>';
  echo '</body></html>';
  exit;
}
