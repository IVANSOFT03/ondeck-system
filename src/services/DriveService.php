<?php

declare(strict_types=1);

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Exception as GoogleServiceException;

class DriveService
{
  private Drive $drive;

  private array $requestTimestamps = [];
  private int $maxRequestsPerMinute = 10;

  private ?string $procesadosFolderId = null;

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
   * ID de la subcarpeta "procesados" bajo GOOGLE_DRIVE_FOLDER_ID (la crea si no existe).
   */
  private function getProcesadosFolderId(): string
  {
    if ($this->procesadosFolderId !== null) {
      return $this->procesadosFolderId;
    }

    $rootId = GOOGLE_DRIVE_FOLDER_ID;
    $q = sprintf(
      "name = '%s' and '%s' in parents and trashed = false and mimeType = 'application/vnd.google-apps.folder'",
      str_replace("'", "\\'", 'procesados'),
      str_replace("'", "\\'", $rootId)
    );

    $this->rateLimit();
    $res = $this->drive->files->listFiles([
      'q' => $q,
      'spaces' => 'drive',
      'pageSize' => 10,
      'fields' => 'files(id, name)',
      'supportsAllDrives' => true,
      'includeItemsFromAllDrives' => true,
    ]);

    foreach ($res->getFiles() as $folder) {
      $this->procesadosFolderId = (string)$folder->getId();
      return $this->procesadosFolderId;
    }

    $this->rateLimit();
    $meta = new DriveFile([
      'name' => 'procesados',
      'mimeType' => 'application/vnd.google-apps.folder',
      'parents' => [$rootId],
    ]);
    $created = $this->drive->files->create($meta, [
      'fields' => 'id',
      'supportsAllDrives' => true,
    ]);
    $this->procesadosFolderId = (string)$created->getId();
    return $this->procesadosFolderId;
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
      $response = $this->drive->files->get($fileId, ['alt' => 'media']);

      if (is_string($response)) {
        $content = $response;
      } elseif (is_object($response) && method_exists($response, 'getBody')) {
        // google/apiclient + Guzzle: cuerpo de la respuesta HTTP (media).
        $content = (string) $response->getBody();
      } else {
        error_log('DriveService downloadFile: tipo de respuesta no soportado para fileId=' . $fileId);
        return false;
      }

      if ($content === '') {
        return false;
      }

      file_put_contents($destPath, $content);
      return file_exists($destPath) && filesize($destPath) > 0;
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
   * Mueve el archivo a la subcarpeta "procesados" bajo GOOGLE_DRIVE_FOLDER_ID (no lo borra).
   * Llamar solo despues de confirmar publicacion exitosa en TikTok.
   */
  public function deleteFile(string $fileId): void
  {
    try {
      $this->rateLimit();
      $file = $this->drive->files->get($fileId, [
        'fields' => 'id,parents',
        'supportsAllDrives' => true,
      ]);

      $parents = $file->getParents() ? array_map('strval', $file->getParents()) : [];
      if ($parents === []) {
        error_log('DriveService deleteFile (mover): sin parents para fileId=' . $fileId);
        return;
      }

      $procesadosId = $this->getProcesadosFolderId();
      if (count($parents) === 1 && $parents[0] === $procesadosId) {
        return;
      }

      $removeParents = implode(',', $parents);

      $this->rateLimit();
      $this->drive->files->update($fileId, new DriveFile(), [
        'addParents' => $procesadosId,
        'removeParents' => $removeParents,
        'fields' => 'id',
        'supportsAllDrives' => true,
      ]);
    } catch (GoogleServiceException $e) {
      if ((string)$e->getCode() === '404' || stripos($e->getMessage(), '404') !== false) {
        error_log('DriveService deleteFile (mover) 404: ' . $fileId);
        return;
      }

      error_log('DriveService deleteFile (mover) error: ' . $e->getMessage());
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

