<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
  public function __construct()
  {
    require_once dirname(__DIR__, 2) . '/config/config.php';
  }

  public function sendPublishedNotification(string $toEmail, string $toName, string $videoUrl): bool
  {
    $mail = new PHPMailer(true);

    try {
      $mail->isSMTP();
      $mail->Host = MAIL_HOST;
      $mail->SMTPAuth = true;
      $mail->Username = MAIL_USER;
      $mail->Password = MAIL_PASS;
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port = (int)MAIL_PORT;

      $mail->CharSet = 'UTF-8';

      $mail->setFrom(MAIL_USER, MAIL_FROM_NAME);
      $mail->addAddress($toEmail, $toName);

      $mail->Subject = 'Tu video ya fue publicado en TikTok';

      $videoUrlEscaped = htmlspecialchars($videoUrl, ENT_QUOTES, 'UTF-8');
      $toNameEscaped = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');

      $mail->isHTML(true);
      $mail->Body = <<<HTML
<p>Hola {$toNameEscaped},</p>
<p>Tu contenido fue publicado en TikTok correctamente.</p>
<p><a href="{$videoUrlEscaped}" target="_blank" rel="noopener noreferrer">Ver video</a></p>
<p>Gracias por participar.</p>
HTML;

      $mail->AltBody = "Hola {$toName}. Tu contenido fue publicado en TikTok correctamente. Ver video: {$videoUrl}";

      $mail->send();
      return true;
    } catch (Exception $e) {
      error_log('MailService error: ' . $e->getMessage());
      throw new \RuntimeException($e->getMessage(), 0, $e);
    }
  }
}

