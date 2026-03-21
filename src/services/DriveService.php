<?php

declare(strict_types=1);

use Google\Client;
use Google\Service\Drive;
use Google\Service\Exception as GoogleServiceException;

class DriveService
{
  private Drive $drive;

  private array $requestTimestamps = [];
  private int $maxRequestsPerMinute = 10;

  public function __construct()
  {
    require_once dirname(__DIR__, 2) . '/config/config.php';

    $client = new Client();
    $client->setApplicationName('tiktok-system');
    $client->setAuthConfig(GOOGLE_SERVICE_ACCOUNT_PATH);
    $client->setScopes(['https://www.googleapis.com/auth/drive']);

    // Importante: para acceder a carpetas/archivos compartidos dentro del Drive del proyecto,
    // la Service Account debe tener permisos en la carpeta raiz especificada en `GOOGLE_DRIVE_FOLDER_ID`.
    $this->drive = new Drive($client);
  }

  private function rateLimit(): void
  {
    $now = microtime(true);

    // Mantener un rolling window de 60s
    $this->requestTimestamps = array_values(array_filter(
      $this->requestTimestamps,
      static fn ($t) => ($now - $t) < 60
    ));

    while (count($this->requestTimestamps) >= $this->maxRequestsPerMinute) {
      $oldest = min($this->requestTimestamps);
      $sleepSeconds = (60 - ($now - $oldest)) + 0.1;
      if ($sleepSeconds > 0) {
        usleep((int)($sleepSeconds * 1e6));
      }

      $now = microtime(true);
      $this->requestTimestamps = array_values(array_filter(
        $this->requestTimestamps,
        static fn ($t) => ($now - $t) < 60
      ));
    }

    $this->requestTimestamps[] = microtime(true);
  }

  private function isAllowedMimeType(string $mimeType): bool
  {
    return in_array($mimeType, DRIVE_ALLOWED_MIME_TYPES, true);
  }

  /**
   * Lista archivos nuevos bajo GOOGLE_DRIVE_FOLDER_ID: primero las subcarpetas
   * (una por participante), luego los archivos directos en cada una.
   * Solo incluye tipos MIME permitidos (DRIVE_ALLOWED_MIME_TYPES).
   *
   * Devuelve array con id, name, mimeType, createdTime, parents.
   */
  public function listNewFiles(): array
  {
    $rootId = GOOGLE_DRIVE_FOLDER_ID;
    $allowed = [];

    $folderPageToken = null;
    do {
      $this->rateLimit();
      $folderParams = [
        'q' => sprintf(
          "'%s' in parents and trashed = false and mimeType = 'application/vnd.google-apps.folder'",
          addslashes($rootId)
        ),
        'spaces' => 'drive',
        'pageSize' => 1000,
        'fields' => 'nextPageToken, files(id)',
      ];
      if ($folderPageToken !== null) {
        $folderParams['pageToken'] = $folderPageToken;
      }

      $folderRes = $this->drive->files->listFiles($folderParams);

      foreach ($folderRes->getFiles() as $participantFolder) {
        $participantFolderId = (string)$participantFolder->getId();

        $filePageToken = null;
        do {
          $this->rateLimit();
          $fileParams = [
            'q' => sprintf("'%s' in parents and trashed = false", addslashes($participantFolderId)),
            'spaces' => 'drive',
            'pageSize' => 1000,
            'fields' => 'nextPageToken, files(id, name, mimeType, createdTime, parents)',
          ];
          if ($filePageToken !== null) {
            $fileParams['pageToken'] = $filePageToken;
          }

          $fileRes = $this->drive->files->listFiles($fileParams);

          foreach ($fileRes->getFiles() as $file) {
            $mimeType = (string)$file->getMimeType();
            if (!$this->isAllowedMimeType($mimeType)) {
              continue;
            }

            $parents = $file->getParents() ? array_map('strval', $file->getParents()) : [];

            $allowed[] = [
              'id' => (string)$file->getId(),
              'name' => (string)$file->getName(),
              'mimeType' => $mimeType,
              'createdTime' => (string)$file->getCreatedTime(),
              'parents' => $parents,
            ];
          }

          $filePageToken = $fileRes->getNextPageToken();
        } while ($filePageToken !== null);
      }

      $folderPageToken = $folderRes->getNextPageToken();
    } while ($folderPageToken !== null);

    return $allowed;
  }

  /**
   * Descarga el archivo a disco temporal en /tmp/.
   * Devuelve bool.
   */
  public function downloadFile(string $fileId, string $destPath): bool
  {
    $dir = dirname($destPath);
    if (!is_dir($dir)) {
      mkdir($dir, 0775, true);
    }

    $this->rateLimit();
    try {
      $request = $this->drive->files->get($fileId, ['alt' => 'media']);
      $content = $request->execute();

      if (is_string($content)) {
        file_put_contents($destPath, $content);
        return file_exists($destPath) && filesize($destPath) > 0;
      }

      // Manejo común: StreamInterface (o similar) en vez de string.
      if (is_object($content) && method_exists($content, 'getContents')) {
        $bytes = $content->getContents();
        if (!is_string($bytes) || $bytes === '') {
          return false;
        }
        file_put_contents($destPath, $bytes);
        return file_exists($destPath) && filesize($destPath) > 0;
      }

      // Si no es string ni stream conocido, fallamos.
      return false;
    } catch (GoogleServiceException $e) {
      // 404: archivo ya eliminado => continuar silenciosamente.
      if ((string)$e->getCode() === '404' || stripos($e->getMessage(), '404') !== false) {
        error_log('DriveService downloadFile 404: ' . $fileId);
        return false;
      }

      error_log('DriveService downloadFile error: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Elimina el archivo de Drive permanentemente.
   * Llamar solo despues de confirmar publicacion exitosa en TikTok.
   */
  public function deleteFile(string $fileId): void
  {
    $this->rateLimit();
    try {
      $this->drive->files->delete($fileId);
    } catch (GoogleServiceException $e) {
      // 404 => archivo ya eliminado => continuar.
      if ((string)$e->getCode() === '404' || stripos($e->getMessage(), '404') !== false) {
        error_log('DriveService deleteFile 404: ' . $fileId);
        return;
      }

      error_log('DriveService deleteFile error: ' . $e->getMessage());
      throw $e;
    }
  }

  /**
   * Busca el email del owner de la subcarpeta para saber a quien notificar.
   */
  public function getUploaderEmail(string $folderId): string
  {
    if (trim($folderId) === '') {
      return '';
    }

    $this->rateLimit();
    try {
      $res = $this->drive->permissions->listPermissions($folderId, [
        'fields' => 'permissions(emailAddress,role,type,deleted),nextPageToken',
        'supportsAllDrives' => true,
      ]);
    } catch (GoogleServiceException $e) {
      error_log('DriveService getUploaderEmail error: ' . $e->getMessage());
      return '';
    }

    $permissions = $res->getPermissions() ?: [];
    foreach ($permissions as $perm) {
      if ((string)$perm->getDeleted() === 'true') {
        continue;
      }

      $role = (string)$perm->getRole();
      $email = (string)$perm->getEmailAddress();

      if ($role === 'owner' && trim($email) !== '') {
        return $email;
      }
    }

    // Fallback: tomar cualquier email encontrado
    foreach ($permissions as $perm) {
      $email = (string)$perm->getEmailAddress();
      if (trim($email) !== '') {
        return $email;
      }
    }

    return '';
  }
}

