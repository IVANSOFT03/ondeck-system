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

  private ?string $pendientesFolderId = null;
  private ?string $rechazadosFolderId = null;

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

  private function getChildFolderIdByName(string $childName): ?string
  {
    $rootId = GOOGLE_DRIVE_FOLDER_ID;

    $q = sprintf(
      "name = '%s' and '%s' in parents and trashed = false and mimeType = 'application/vnd.google-apps.folder'",
      str_replace("'", "\\'", $childName),
      str_replace("'", "\\'", $rootId)
    );

    $this->rateLimit();
    $res = $this->drive->files->listFiles([
      'q' => $q,
      'spaces' => 'drive',
      'pageSize' => 10,
      'fields' => 'files(id, name)',
    ]);

    foreach ($res->getFiles() as $folder) {
      return (string)$folder->getId();
    }

    return null;
  }

  private function getPendientesFolderId(): string
  {
    if ($this->pendientesFolderId !== null) {
      return $this->pendientesFolderId;
    }

    $this->pendientesFolderId = $this->getChildFolderIdByName('pendientes');
    if ($this->pendientesFolderId === null) {
      throw new RuntimeException("No se encontró la subcarpeta 'pendientes' bajo GOOGLE_DRIVE_FOLDER_ID.");
    }

    return $this->pendientesFolderId;
  }

  private function getRechazadosFolderId(): ?string
  {
    if ($this->rechazadosFolderId !== null) {
      return $this->rechazadosFolderId;
    }

    $this->rechazadosFolderId = $this->getChildFolderIdByName('rechazados');
    return $this->rechazadosFolderId;
  }

  private function isAllowedMimeType(string $mimeType): bool
  {
    return in_array($mimeType, DRIVE_ALLOWED_MIME_TYPES, true);
  }

  /**
   * Lista todos los archivos en /pendientes/ con status 'activo'.
   * Nota: Google Drive no tiene un campo "status" nativo; este sistema asume que
   * esos archivos están en la subcarpeta 'pendientes' y se filtra por tipo MIME.
   *
   * Devuelve array con id, name, mimeType, createdTime, parents.
   */
  public function listNewFiles(): array
  {
    $pendientesFolderId = $this->getPendientesFolderId();
    $rechazadosFolderId = $this->getRechazadosFolderId();

    $allowed = [];
    $pageToken = null;

    // Traemos todos los archivos en la carpeta 'pendientes' y filtramos por MIME.
    do {
      $this->rateLimit();
      $params = [
        'q' => sprintf("'%s' in parents and trashed = false", addslashes($pendientesFolderId)),
        'spaces' => 'drive',
        'pageSize' => 1000,
        'fields' => 'nextPageToken, files(id, name, mimeType, createdTime, parents)',
      ];
      if ($pageToken !== null) {
        $params['pageToken'] = $pageToken;
      }

      $res = $this->drive->files->listFiles($params);

      foreach ($res->getFiles() as $file) {
        $fileId = (string)$file->getId();
        $mimeType = (string)$file->getMimeType();
        $name = (string)$file->getName();
        $createdTime = (string)$file->getCreatedTime();

        $parents = $file->getParents() ? array_map('strval', $file->getParents()) : [];
        $parentFolderId = $parents[0] ?? null;

        if ($this->isAllowedMimeType($mimeType)) {
          $allowed[] = [
            'id' => $fileId,
            'name' => $name,
            'mimeType' => $mimeType,
            'createdTime' => $createdTime,
            'parents' => $parents,
          ];
          continue;
        }

        // Rechazar/limpiar tipos no permitidos.
        if ($rechazadosFolderId !== null && $parentFolderId !== null) {
          $this->rateLimit();
          try {
            $this->drive->files->update($fileId, null, [
              'addParents' => $rechazadosFolderId,
              'removeParents' => $parentFolderId,
              'fields' => 'id',
            ]);
          } catch (GoogleServiceException $e) {
            // Silencioso: si falla la reasignación, al menos no encolamos el archivo.
            error_log('DriveService reject mime move error: ' . $e->getMessage());
          }
        }
      }

      $pageToken = $res->getNextPageToken();
    } while ($pageToken !== null);

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

