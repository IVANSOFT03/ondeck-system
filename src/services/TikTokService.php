<?php
declare(strict_types=1);

/**
 * Publicación vía Content Posting API: subida a inbox (borrador) con scope OAuth
 * `user.info.basic,video.upload` (ver src/auth/login.php). No usa Direct Post (video.publish).
 */
class TikTokService
{
  private string $accessToken;
  private string $refreshToken;
  private ?int $expiresAt = null;

  private ?string $creatorUsername = null;

  public function __construct()
  {
    require_once dirname(__DIR__, 2) . '/config/config.php';

    $this->accessToken = TIKTOK_ACCESS_TOKEN;
    $this->refreshToken = TIKTOK_REFRESH_TOKEN;

    $this->loadState();
  }

  private function loadState(): void
  {
    $path = TIKTOK_STATE_PATH;
    if (!file_exists($path)) {
      return;
    }

    $raw = (string)file_get_contents($path);
    if (trim($raw) === '') {
      return;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
      return;
    }

    if (!empty($data['access_token']) && is_string($data['access_token'])) {
      $this->accessToken = $data['access_token'];
    }
    if (!empty($data['refresh_token']) && is_string($data['refresh_token'])) {
      $this->refreshToken = $data['refresh_token'];
    }
    if (isset($data['expires_at']) && is_numeric($data['expires_at'])) {
      $this->expiresAt = (int)$data['expires_at'];
    }
    if (!empty($data['creator_username']) && is_string($data['creator_username'])) {
      $this->creatorUsername = $data['creator_username'];
    }
  }

  private function saveState(): void
  {
    $dir = dirname(TIKTOK_STATE_PATH);
    if (!is_dir($dir)) {
      mkdir($dir, 0775, true);
    }

    $data = [
      'access_token' => $this->accessToken,
      'refresh_token' => $this->refreshToken,
      'expires_at' => $this->expiresAt,
      'creator_username' => $this->creatorUsername,
      'updated_at' => time(),
    ];

    file_put_contents(TIKTOK_STATE_PATH, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
  }

  public function isTokenValid(): bool
  {
    if ($this->accessToken === '' || $this->refreshToken === '') {
      return false;
    }

    if ($this->expiresAt === null) {
      return false;
    }

    // Buffer para evitar llamadas justo al expirar.
    return time() < ($this->expiresAt - 60);
  }

  public function refreshAccessToken(): void
  {
    $url = TIKTOK_API_BASE_URL . '/v2/oauth/token/';

    $postFields = http_build_query([
      'client_key' => TIKTOK_CLIENT_KEY,
      'client_secret' => TIKTOK_CLIENT_SECRET,
      'grant_type' => 'refresh_token',
      'refresh_token' => $this->refreshToken,
    ], '', '&', PHP_QUERY_RFC3986);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/x-www-form-urlencoded',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $resp = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($resp === false || $resp === null) {
      throw new RuntimeException('refreshAccessToken curl error: ' . $curlErr);
    }

    $data = json_decode($resp, true);
    if (!is_array($data)) {
      throw new RuntimeException('refreshAccessToken invalid JSON. HTTP: ' . $httpCode);
    }

    if (empty($data['access_token']) || empty($data['expires_in'])) {
      throw new RuntimeException('refreshAccessToken missing access_token/expires_in. HTTP: ' . $httpCode);
    }

    $this->accessToken = (string)$data['access_token'];
    $this->refreshToken = !empty($data['refresh_token']) ? (string)$data['refresh_token'] : $this->refreshToken;
    $this->expiresAt = time() + (int)$data['expires_in'];

    $this->saveState();
  }

  private function ensureToken(): void
  {
    if (!$this->isTokenValid()) {
      $this->refreshAccessToken();
    }
  }

  private function decodeJson(string $raw): ?array
  {
    $options = 0;
    if (defined('JSON_BIGINT_AS_STRING')) {
      $options |= JSON_BIGINT_AS_STRING;
    }
    $data = json_decode($raw, true, 512, $options);
    return is_array($data) ? $data : null;
  }

  private function requestJson(string $method, string $path, array $body, bool $retryOnceOnInvalidToken = true): array
  {
    $this->ensureToken();

    $url = TIKTOK_API_BASE_URL . $path;

    $send = function (string $token) use ($method, $url, $body) {
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json; charset=UTF-8',
      ]);
      curl_setopt($ch, CURLOPT_TIMEOUT, 120);

      $resp = curl_exec($ch);
      $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $curlErr = curl_error($ch);
      curl_close($ch);

      if ($resp === false || $resp === null) {
        throw new RuntimeException('requestJson curl error: ' . $curlErr);
      }

      $decoded = $this->decodeJson((string)$resp);
      if ($decoded === null) {
        throw new RuntimeException('requestJson invalid JSON. HTTP=' . $httpCode);
      }

      $decoded['_http_code'] = $httpCode;
      $decoded['_raw'] = (string)$resp;
      return $decoded;
    };

    $resp = $send($this->accessToken);

    $errCode = $resp['error']['code'] ?? null;
    if ($retryOnceOnInvalidToken && $errCode === 'access_token_invalid') {
      // Si el token expiró, refrescamos una sola vez y reintentamos.
      $this->refreshAccessToken();
      $resp = $send($this->accessToken);
    }

    return $resp;
  }

  private function getCreatorUsername(): string
  {
    if ($this->creatorUsername !== null && $this->creatorUsername !== '') {
      return $this->creatorUsername;
    }

    $resp = $this->requestJson('POST', '/v2/post/publish/creator_info/query/', [], false);

    $username = $resp['data']['creator_username'] ?? '';
    if (is_string($username) && trim($username) !== '') {
      $this->creatorUsername = $username;
      $this->saveState();
      return $username;
    }

    return '';
  }

  private function buildTiktokUrl(string $videoId): string
  {
    $username = $this->getCreatorUsername();
    if ($username !== '') {
      return 'https://www.tiktok.com/@' . $username . '/video/' . $videoId;
    }

    return 'https://www.tiktok.com/video/' . $videoId;
  }

  /** Enlace al perfil del creador (p. ej. tras subida a inbox sin post_id público aún). */
  private function buildCreatorProfileUrl(): string
  {
    $username = $this->getCreatorUsername();
    if ($username !== '') {
      return 'https://www.tiktok.com/@' . $username;
    }

    return 'https://www.tiktok.com';
  }

  /**
   * Sube el video al inbox de TikTok (borrador; el creador lo termina en la app).
   * Flujo: inbox init → PUT a upload_url → polling /status/fetch/ hasta SEND_TO_USER_INBOX o PUBLISH_COMPLETE.
   *
   * Devuelve ['success'=>true, 'video_id'=>'...', 'url'=>'...'] (video_id puede ser vacío si solo hay borrador en inbox)
   * o ['success'=>false, 'error'=>'...'].
   */
  public function publishVideo(string $filePath, string $mimeType): array
  {
    if (!file_exists($filePath)) {
      return ['success' => false, 'error' => 'Archivo temporal no encontrado.'];
    }

    $videoSize = filesize($filePath);
    if ($videoSize === false || $videoSize <= 0) {
      return ['success' => false, 'error' => 'No se pudo obtener tamaño del archivo.'];
    }

    // Seguridad: rechazar > 500MB antes de intentar subir.
    if ($videoSize > (500 * 1024 * 1024)) {
      return ['success' => false, 'error' => 'El video supera 500MB y fue rechazado.'];
    }

    // 1) Inicializar subida a inbox (scope video.upload; no requiere aprobación Direct Post / video.publish)
    $initPayload = [
      'source_info' => [
        'source' => 'FILE_UPLOAD',
        'video_size' => (int)$videoSize,
        'chunk_size' => (int)$videoSize,
        'total_chunk_count' => 1,
      ],
    ];

    $initResp = $this->requestJson('POST', '/v2/post/publish/inbox/video/init/', $initPayload, true);

    $initErrCode = $initResp['error']['code'] ?? null;
    if ($initErrCode !== 'ok') {
      $msg = $initResp['error']['message'] ?? 'Error desconocido en init.';
      return ['success' => false, 'error' => 'TikTok init falló: ' . $msg];
    }

    $data = $initResp['data'] ?? [];
    $publishId = $data['publish_id'] ?? null;
    $uploadUrl = $data['upload_url'] ?? null;
    if (!is_string($publishId) || !is_string($uploadUrl) || $publishId === '' || $uploadUrl === '') {
      return ['success' => false, 'error' => 'TikTok init no devolvió publish_id/upload_url.'];
    }

    // 2) PUT del archivo al upload_url
    $uploadOk = $this->uploadBinaryToUrl($uploadUrl, $filePath, $mimeType, (int)$videoSize);
    if (!$uploadOk) {
      return ['success' => false, 'error' => 'Error al subir el archivo a TikTok.'];
    }

    // 3) Polling: upload a inbox → SEND_TO_USER_INBOX; si el usuario publica luego desde la app → PUBLISH_COMPLETE
    $maxAttempts = 20;
    for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
      if ($attempt > 0) {
        sleep(5);
      }

      $statusResp = $this->requestJson('POST', '/v2/post/publish/status/fetch/', [
        'publish_id' => $publishId,
      ], true);

      $statusData = $statusResp['data'] ?? [];
      $status = $statusData['status'] ?? null;

      if (!is_string($status)) {
        continue;
      }

      // Contenido subido a borradores: notificación en inbox del creador (documentación Upload Content).
      if ($status === 'SEND_TO_USER_INBOX') {
        return [
          'success' => true,
          'video_id' => $publishId,
          'url' => $this->buildCreatorProfileUrl(),
        ];
      }

      if ($status === 'PUBLISH_COMPLETE') {
        $postIds = $statusData['publicaly_available_post_id'] ?? [];
        $videoId = '';
        if (is_array($postIds) && isset($postIds[0])) {
          $videoId = is_string($postIds[0]) ? $postIds[0] : (string)$postIds[0];
        } elseif (is_string($postIds)) {
          $videoId = $postIds;
        }

        if ($videoId === '') {
          return ['success' => false, 'error' => 'PUBLISH_COMPLETE sin video_id/publicaly_available_post_id.'];
        }

        return [
          'success' => true,
          'video_id' => $videoId,
          'url' => $this->buildTiktokUrl($videoId),
        ];
      }

      if ($status === 'FAILED') {
        $reason = $statusData['fail_reason'] ?? 'FAILED sin fail_reason.';
        return ['success' => false, 'error' => 'TikTok publish FAILED: ' . $reason];
      }

      // PROCESSING_UPLOAD / PROCESSING_DOWNLOAD: seguir esperando
    }

    return ['success' => false, 'error' => 'Polling TikTok agotó intentos (sin SEND_TO_USER_INBOX ni PUBLISH_COMPLETE).'];
  }

  private function uploadBinaryToUrl(string $uploadUrl, string $filePath, string $mimeType, int $size): bool
  {
    $firstByte = 0;
    $lastByte = $size - 1;

    $ch = curl_init($uploadUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_UPLOAD, true);
    curl_setopt($ch, CURLOPT_INFILE, fopen($filePath, 'rb'));
    curl_setopt($ch, CURLOPT_INFILESIZE, $size);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: ' . $mimeType,
      'Content-Length: ' . $size,
      'Content-Range: bytes ' . $firstByte . '-' . $lastByte . '/' . $size,
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);

    $resp = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);

    curl_close($ch);

    if ($resp === false) {
      error_log('TikTok uploadBinaryToUrl curl error: ' . $curlErr);
      return false;
    }

    return $httpCode >= 200 && $httpCode < 300;
  }
}

