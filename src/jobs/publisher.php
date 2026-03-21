<?php

declare(strict_types=1);

set_time_limit(300);
ini_set('max_execution_time', '300');

require_once dirname(__DIR__, 2) . '/config/config.php';

require_once dirname(__DIR__, 2) . '/src/db/Database.php';
require_once dirname(__DIR__, 2) . '/src/services/DriveService.php';
require_once dirname(__DIR__, 2) . '/src/services/TikTokService.php';
require_once dirname(__DIR__, 2) . '/src/services/MailService.php';

function getDb(): PDO
{
  return Database::getInstance()->ensureFreshPdo();
}

$lockFile = PUBLISH_LOCK_PATH;

if (file_exists($lockFile)) {
  $ageSeconds = time() - (int)@filemtime($lockFile);
  if ($ageSeconds < 600) {
    exit(0);
  }
}

@file_put_contents($lockFile, (string)getmypid());

$tmpFilePath = null;

try {
  $db = Database::getInstance()->pdo();
  $drive = new DriveService();
  $tiktok = new TikTokService();
  $mail = new MailService();

  // 10. Buscar el registro mas antiguo con status='pending'
  $stmt = $db->query("SELECT * FROM queue WHERE status='pending' ORDER BY created_at ASC LIMIT 1");
  $job = $stmt ? $stmt->fetch() : false;

  if (!$job) {
    exit(0);
  }

  $jobId = (int)$job['id'];

  // 12. UPDATE status='processing' antes de llamar TikTok (evitar doble publicacion)
  $update = $db->prepare("UPDATE queue SET status='processing' WHERE id=? AND status='pending'");
  $update->execute([$jobId]);
  if ($update->rowCount() !== 1) {
    exit(0);
  }

  $driveFileId = (string)$job['drive_file_id'];
  $driveFileName = (string)$job['drive_file_name'];
  $mimeType = (string)$job['mime_type'];
  $uploaderEmail = (string)$job['uploader_email'];
  $uploaderName = (string)$job['uploader_name'];

  // 13. Descargar a /tmp/
  $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $driveFileName);
  $tmpFilePath = '/tmp/' . $jobId . '_' . ($safeName !== '' ? $safeName : 'upload');

  if (!$drive->downloadFile($driveFileId, $tmpFilePath)) {
    $err = 'Drive downloadFile falló.';
    $fail = getDb()->prepare("UPDATE queue SET status='failed', error_message=? WHERE id=?");
    $fail->execute([$err, $jobId]);
    exit(0);
  }

  // 14. Publicar en TikTok
  $publishResult = $tiktok->publishVideo($tmpFilePath, $mimeType);

  if (($publishResult['success'] ?? false) === true) {
    $videoId = (string)($publishResult['video_id'] ?? '');
    $url = (string)($publishResult['url'] ?? '');

    $done = getDb()->prepare(
      'UPDATE queue
       SET status=?,
           tiktok_video_id=?,
           tiktok_url=?,
           published_at=NOW()
       WHERE id=?'
    );
    $done->execute(['done', $videoId, $url, $jobId]);

    // 15. Borrar archivo en Drive solo despues de publicar exitosamente
    try {
      $drive->deleteFile($driveFileId);
    } catch (Throwable $e) {
      error_log('publisher deleteFile error: ' . $e->getMessage());
    }

    // 16. Enviar email de notificacion (si falla el email, no revertimos el done)
    $mail->sendPublishedNotification($uploaderEmail, $uploaderName, $url);
  } else {
    $err = (string)($publishResult['error'] ?? 'TikTok publish falló.');

    $fail = getDb()->prepare("UPDATE queue SET status='failed', error_message=? WHERE id=?");
    $fail->execute([$err, $jobId]);
  }

  exit(0);
} finally {
  // 17. Borrar archivo temporal independientemente del resultado
  if ($tmpFilePath !== null && file_exists($tmpFilePath)) {
    @unlink($tmpFilePath);
  }

  // 18. Borrar el lock
  if (file_exists($lockFile)) {
    @unlink($lockFile);
  }
}

