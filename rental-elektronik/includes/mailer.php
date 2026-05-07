<?php
/**
 * ElektraRent - PHPMailer helpers
 */

require_once __DIR__ . '/../config/mail.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

function sendVerificationEmail(array $user, string $plainCode): bool
{
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoloadPath)) {
        error_log('PHPMailer autoload not found. Run: composer require phpmailer/phpmailer');
        return false;
    }

    require_once $autoloadPath;

    if (MAIL_HOST === '') {
        error_log('MAIL_HOST is empty. Verification email was not sent.');
        return false;
    }

    $recipientEmail = (string) ($user['email'] ?? '');
    $recipientName = (string) ($user['nama_lengkap'] ?? 'Pengguna ElektraRent');

    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        error_log('Invalid verification email recipient.');
        return false;
    }

    $safeName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
    $safeCode = htmlspecialchars($plainCode, ENT_QUOTES, 'UTF-8');
    $year = date('Y');

    $htmlBody = <<<HTML
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verifikasi Email ElektraRent</title>
</head>
<body style="margin:0;background:#e8dcc8;color:#1a2332;font-family:Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#e8dcc8;padding:28px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#f7f0df;border:3px solid #1a2332;border-radius:8px;overflow:hidden;box-shadow:8px 8px 0 #b85c38;">
          <tr>
            <td style="background:#1a2332;color:#f7f0df;padding:24px 28px;border-bottom:3px solid #b85c38;">
              <div style="font-size:30px;line-height:1;font-weight:900;letter-spacing:-1px;">ElektraRent</div>
              <div style="margin-top:8px;color:#d9a441;font-size:12px;font-weight:800;letter-spacing:2px;text-transform:uppercase;">Vintage modern electronic rental system</div>
            </td>
          </tr>
          <tr>
            <td style="padding:30px 28px 12px;">
              <div style="display:inline-block;background:#d9a441;color:#1a2332;border:2px solid #1a2332;border-radius:999px;padding:8px 12px;font-size:12px;font-weight:900;letter-spacing:1.5px;text-transform:uppercase;">Email Verification</div>
              <h1 style="margin:22px 0 10px;font-size:34px;line-height:1.05;color:#1a2332;font-weight:900;letter-spacing:-1.5px;">Verifikasi email akun ElektraRent</h1>
              <p style="margin:0;color:#516070;font-size:15px;line-height:1.7;font-weight:600;">Halo {$safeName}, masukkan kode arsip berikut untuk mengaktifkan akun rental elektronik Anda.</p>
            </td>
          </tr>
          <tr>
            <td style="padding:18px 28px;">
              <div style="background:#fffaf0;border:3px dashed #1a2332;border-radius:8px;text-align:center;padding:24px 16px;">
                <div style="color:#b85c38;font-size:12px;font-weight:900;letter-spacing:2px;text-transform:uppercase;">Kode Verifikasi</div>
                <div style="margin-top:12px;font-family:'Courier New',monospace;font-size:44px;line-height:1;font-weight:900;letter-spacing:10px;color:#1a2332;">{$safeCode}</div>
                <div style="margin-top:14px;color:#516070;font-size:13px;font-weight:700;">Kode berlaku selama 10 menit.</div>
              </div>
            </td>
          </tr>
          <tr>
            <td style="padding:8px 28px 30px;">
              <p style="margin:0;color:#516070;font-size:14px;line-height:1.7;font-weight:600;">Jika Anda tidak mendaftar akun ElektraRent, abaikan email ini. Demi keamanan, jangan bagikan kode ini kepada siapa pun.</p>
            </td>
          </tr>
          <tr>
            <td style="background:#1a2332;color:#f7f0df;padding:18px 28px;border-top:3px solid #1a2332;">
              <div style="font-size:12px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;color:#f7f0df;">&copy; {$year} ElektraRent</div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

    $plainBody = "Kode verifikasi ElektraRent Anda adalah: {$plainCode}. Kode berlaku 10 menit.";

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->Port = MAIL_PORT;
        $mail->SMTPAuth = MAIL_USERNAME !== '';
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;

        if (MAIL_ENCRYPTION === 'ssl' || MAIL_ENCRYPTION === 'smtps') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif (MAIL_ENCRYPTION === 'tls' || MAIL_ENCRYPTION === 'starttls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($recipientEmail, $recipientName);
        $mail->Subject = 'Kode verifikasi akun ElektraRent';
        $mail->isHTML(true);
        $mail->Body = $htmlBody;
        $mail->AltBody = $plainBody;

        return $mail->send();
    } catch (Exception $exception) {
        error_log('Verification email failed: ' . $exception->getMessage());
        return false;
    }
}
