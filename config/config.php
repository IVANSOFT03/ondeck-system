<?php

declare(strict_types=1);

// Punto de entrada para cargar .env y exponer constantes globales.
// Requiere que exista `vendor/autoload.php` (ejecutar `composer install` en `tiktok-system/`).

   $ROOT_DIR = dirname(__DIR__);
$ROOT_DIR = '/home/u617396989/domains/ondeck.nodo-digital.com/tiktok-system';

if (!file_exists($ROOT_DIR . '/vendor/autoload.php')) {
  throw new RuntimeException('Falta vendor/autoload.php. Ejecuta `composer install` en tiktok-system/.');
}

require_once $ROOT_DIR . '/vendor/autoload.php';

use Dotenv\Dotenv;

$envPath = $ROOT_DIR . '/.env';
if (!file_exists($envPath)) {
  throw new RuntimeException('Falta el archivo .env en tiktok-system/. Copia .env.example y completa valores reales.');
}

$dotenv = Dotenv::createImmutable($ROOT_DIR);
$dotenv->load();

function require_env(string $key): string {
$val = $_ENV[$key] ?? getenv($key);
if ($val === false || $val === null || trim((string)$val) === '') {
    throw new RuntimeException("Variable de entorno requerida no definida: {$key}");
  }
  return (string)$val;
}

date_default_timezone_set('UTC');

// MySQL
define('DB_HOST', require_env('DB_HOST'));
define('DB_NAME', require_env('DB_NAME'));
define('DB_USER', require_env('DB_USER'));
define('DB_PASS', require_env('DB_PASS'));

// Google Drive
define('GOOGLE_SERVICE_ACCOUNT_PATH', require_env('GOOGLE_SERVICE_ACCOUNT_PATH'));
define('GOOGLE_DRIVE_FOLDER_ID', require_env('GOOGLE_DRIVE_FOLDER_ID'));

// TikTok OAuth
define('TIKTOK_CLIENT_KEY', require_env('TIKTOK_CLIENT_KEY'));
define('TIKTOK_CLIENT_SECRET', require_env('TIKTOK_CLIENT_SECRET'));
define('TIKTOK_ACCESS_TOKEN', require_env('TIKTOK_ACCESS_TOKEN'));
define('TIKTOK_REFRESH_TOKEN', require_env('TIKTOK_REFRESH_TOKEN'));

// Email
define('MAIL_HOST', require_env('MAIL_HOST'));
define('MAIL_PORT', (string)require_env('MAIL_PORT'));
define('MAIL_USER', require_env('MAIL_USER'));
define('MAIL_PASS', require_env('MAIL_PASS'));
define('MAIL_FROM_NAME', require_env('MAIL_FROM_NAME'));

// Constantes varias
define('TIKTOK_STATE_PATH', $ROOT_DIR . '/config/.tiktok_state.json');

// Reservada por si en el futuro se usa Direct Post (video.publish) desde la app.
define('TIKTOK_PRIVACY_LEVEL', 'SELF_ONLY');

define('TIKTOK_API_BASE_URL', 'https://open.tiktokapis.com');

// MIME permitidos
define('DRIVE_ALLOWED_MIME_TYPES', [
  'video/mp4',
  'video/quicktime',
  'image/jpeg',
  'image/png',
]);

define('PUBLISH_LOCK_PATH', '/tmp/publisher.lock');
