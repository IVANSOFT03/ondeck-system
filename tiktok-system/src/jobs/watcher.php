<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';

require_once dirname(__DIR__, 2) . '/src/db/Database.php';
require_once dirname(__DIR__, 2) . '/src/services/DriveService.php';

$db = Database::getInstance()->pdo();
$drive = new DriveService();

try {
  $files = $drive->listNewFiles();
} catch (Throwable $e) {
  error_log('watcher listNewFiles error: ' . $e->getMessage());
  exit(1);
}

foreach ($files as $file) {
  $driveFileId = (string)($file['id'] ?? '');
  $driveFileName = (string)($file['name'] ?? '');
  $mimeType = (string)($file['mimeType'] ?? '');
  $parents = is_array($file['parents'] ?? null) ? $file['parents'] : [];
  $uploaderFolderId = $parents[0] ?? '';

  if ($driveFileId === '') {
    continue;
  }

  // 5. Verificar si ya existe drive_file_id (cualquier status).
  $stmt = $db->prepare('SELECT id FROM queue WHERE drive_file_id = ? LIMIT 1');
  $stmt->execute([$driveFileId]);
  if ($stmt->fetch()) {
    continue;
  }

  // 6. Encolar si es nuevo.
  $uploaderEmail = $drive->getUploaderEmail($uploaderFolderId);
  if (trim($uploaderEmail) === '') {
    error_log("watcher skip: no se pudo resolver uploader_email para drive_file_id={$driveFileId}");
    continue;
  }

  $uploaderName = explode('@', $uploaderEmail)[0] ?: 'Participante';

  $insert = $db->prepare(
    'INSERT INTO queue (drive_file_id, drive_file_name, uploader_email, uploader_name, mime_type, status)
     VALUES (?, ?, ?, ?, ?, \'pending\')'
  );

  $insert->execute([
    $driveFileId,
    $driveFileName,
    $uploaderEmail,
    $uploaderName,
    $mimeType,
  ]);

  error_log("watcher enqueued: drive_file_id={$driveFileId} name={$driveFileName} mime={$mimeType}");
}

exit(0);

