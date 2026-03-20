CREATE TABLE IF NOT EXISTS queue (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  drive_file_id   VARCHAR(255) NOT NULL,
  drive_file_name VARCHAR(500) NOT NULL,
  uploader_email  VARCHAR(255) NOT NULL,
  uploader_name   VARCHAR(255) NOT NULL,
  mime_type       VARCHAR(100) NOT NULL,
  status          ENUM('pending','processing','done','failed') NOT NULL DEFAULT 'pending',
  tiktok_video_id VARCHAR(255) NULL,
  tiktok_url      VARCHAR(500) NULL,
  error_message   TEXT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  published_at    DATETIME NULL,
  INDEX idx_status_created (status, created_at)
);
