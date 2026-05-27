<?php
// app/controllers/ForgotController.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

class ForgotController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    // GET /forgot
    public function forgotForm()
    {
        $error = $success = null;
        require __DIR__ . '/../views/forgot.php';
    }

    // POST /forgot — send reset email
    public function forgot()
    {
        $email = trim(strtolower($_POST['email'] ?? ''));
        $error = $success = null;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Введи коректну email-адресу';
            require __DIR__ . '/../views/forgot.php';
            return;
        }

        // Find admin by email (case-insensitive)
        $stmt = $this->db->query('SELECT * FROM admin WHERE LOWER(email) = ?', [$email]);
        $admin = $stmt->fetch();

        // Always show success (don't reveal if email exists)
        if ($admin) {
            $token   = bin2hex(random_bytes(32));
            $expires = time() + 3600; // 1 hour

            $this->db->query(
                'UPDATE admin SET reset_token = ?, reset_token_expires = ? WHERE id = ?',
                [$token, $expires, $admin['id']]
            );

            $resetLink = 'https://content.fineko.space/reset-password?token=' . $token;
            $this->sendResetEmail($admin['email'], $resetLink);
        }

        $success = 'Якщо такий email зареєстровано — на нього надійшов лист з інструкцією. Перевір також папку "Спам".';
        require __DIR__ . '/../views/forgot.php';
    }

    // GET /reset-password?token=...
    public function resetForm()
    {
        $token = trim($_GET['token'] ?? '');
        $error = $success = null;
        $admin = $this->findByToken($token);

        if (!$admin) {
            $error = 'Посилання недійсне або вже використане. Запроси нове.';
            $token = '';
        }

        require __DIR__ . '/../views/reset-password.php';
    }

    // POST /reset-password
    public function reset()
    {
        $token    = trim($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm']  ?? '';
        $error    = $success = null;

        $admin = $this->findByToken($token);
        if (!$admin) {
            $error = 'Посилання недійсне або вже використане. Запроси нове.';
            require __DIR__ . '/../views/reset-password.php';
            return;
        }

        if (strlen($password) < 8) {
            $error = 'Пароль має бути не менше 8 символів';
            require __DIR__ . '/../views/reset-password.php';
            return;
        }
        if ($password !== $confirm) {
            $error = 'Паролі не збігаються';
            require __DIR__ . '/../views/reset-password.php';
            return;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->db->query(
            'UPDATE admin SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?',
            [$hash, $admin['id']]
        );

        $token   = '';
        $success = 'Пароль успішно змінено! Тепер можеш увійти.';
        require __DIR__ . '/../views/reset-password.php';
    }

    // ── Helpers ──────────────────────────────────────────────

    private function findByToken(string $token): ?array
    {
        if (strlen($token) !== 64) return null;
        $stmt = $this->db->query(
            'SELECT * FROM admin WHERE reset_token = ? AND reset_token_expires > ?',
            [$token, time()]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function sendResetEmail(string $toEmail, string $resetLink): void
    {
        require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
        require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
        require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';

        $cfg = require __DIR__ . '/../../config/mail.php';

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $cfg['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $cfg['username'];
            $mail->Password   = $cfg['password'];
            $mail->SMTPSecure = ($cfg['encryption'] ?? 'tls') === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $cfg['port'];
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($cfg['from'], $cfg['from_name']);
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = 'Відновлення паролю — Content Planner';
            $mail->Body    = $this->buildEmailHtml($resetLink);
            $mail->AltBody = "Відновлення паролю\n\nПосилання дійсне 1 годину:\n$resetLink\n\nЯкщо ти не запитував — просто ігноруй цей лист.";
            $mail->send();
        } catch (MailException $e) {
            // Log silently — don't expose SMTP errors to user
            error_log('ForgotController mail error: ' . $mail->ErrorInfo);
        }
    }

    private function buildEmailHtml(string $link): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="uk">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:'Segoe UI',Arial,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0">
    <tr><td align="center" style="padding:40px 16px">
      <table width="480" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08);overflow:hidden">
        <tr><td style="background:#5a6c7d;padding:28px 36px;text-align:center">
          <span style="font-size:28px">🔐</span>
          <h1 style="color:#fff;margin:8px 0 0;font-size:20px;font-weight:600">Відновлення паролю</h1>
        </td></tr>
        <tr><td style="padding:32px 36px">
          <p style="color:#374151;font-size:15px;line-height:1.6;margin:0 0 24px">
            Хтось (мабуть ти 😊) запросив відновлення паролю для <strong>Content Planner</strong>.<br>
            Натисни кнопку нижче — посилання дійсне <strong>1 годину</strong>.
          </p>
          <div style="text-align:center;margin:28px 0">
            <a href="{$link}" style="display:inline-block;background:#5a6c7d;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:600;font-size:15px">
              Змінити пароль →
            </a>
          </div>
          <p style="color:#9ca3af;font-size:13px;line-height:1.5;margin:24px 0 0">
            Якщо кнопка не працює, скопіюй це посилання у браузер:<br>
            <a href="{$link}" style="color:#5a6c7d;word-break:break-all">{$link}</a>
          </p>
          <hr style="border:none;border-top:1px solid #f3f4f6;margin:24px 0">
          <p style="color:#d1d5db;font-size:12px;margin:0">
            Якщо ти не запитував зміну паролю — просто ігноруй цей лист. Нічого не зміниться.
          </p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }
}
