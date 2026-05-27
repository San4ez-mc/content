<?php
// app/controllers/MailSettingsController.php

class MailSettingsController
{
    private $db;
    private $configPath;

    public function __construct($db)
    {
        $this->db = $db;
        $this->configPath = __DIR__ . '/../../config/mail.php';
        if (session_status() === PHP_SESSION_NONE) session_start();
        require_once __DIR__ . '/AuthController.php';
        AuthController::check();
    }

    public function form()
    {
        $cfg = $this->loadConfig();
        $configured = !empty($cfg['password']);
        $message = null;
        require __DIR__ . '/../views/mail-settings.php';
    }

    public function save()
    {
        $appPassword = trim($_POST['app_password'] ?? '');
        $fromEmail   = trim($_POST['from_email']   ?? '');
        $fromName    = trim($_POST['from_name']    ?? 'Content Planner');

        if (empty($appPassword) || empty($fromEmail)) {
            $cfg = $this->loadConfig();
            $configured = !empty($cfg['password']);
            $message = ['type' => 'error', 'text' => 'Заповни email і App Password'];
            require __DIR__ . '/../views/mail-settings.php';
            return;
        }

        // Sanitize app password (remove spaces — Google sometimes shows them in groups)
        $appPassword = preg_replace('/\s+/', '', $appPassword);

        $newConfig = <<<PHP
<?php
// config/mail.php — Gmail SMTP
return [
    'host'      => 'smtp.gmail.com',
    'port'      => 587,
    'username'  => '$fromEmail',
    'password'  => '$appPassword',
    'from'      => '$fromEmail',
    'from_name' => '$fromName',
];
PHP;

        file_put_contents($this->configPath, $newConfig);

        $cfg = $this->loadConfig();
        $configured = true;
        $message = ['type' => 'success', 'text' => '✅ Налаштування збережено! Натисни "Надіслати тест" для перевірки.'];
        require __DIR__ . '/../views/mail-settings.php';
    }

    public function test()
    {
        header('Content-Type: application/json');
        $cfg = $this->loadConfig();

        if (empty($cfg['password'])) {
            echo json_encode(['ok' => false, 'error' => 'App Password не налаштований']);
            return;
        }

        require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
        require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
        require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $cfg['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $cfg['username'];
            $mail->Password   = $cfg['password'];
            $mail->SMTPSecure = ($cfg['encryption'] ?? 'tls') === 'ssl' ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $cfg['port'];
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom($cfg['from'], $cfg['from_name']);
            $mail->addAddress($cfg['from']); // send to self
            $mail->isHTML(false);
            $mail->Subject = 'Тест — Content Planner пошта працює ✅';
            $mail->Body    = "Вітаю!\n\nЦе тестовий лист від Content Planner.\nЯкщо ти його отримав — відновлення паролю через email налаштовано правильно.\n\nContent Planner";
            $mail->send();
            echo json_encode(['ok' => true]);
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            echo json_encode(['ok' => false, 'error' => $mail->ErrorInfo]);
        }
    }

    private function loadConfig(): array
    {
        if (!file_exists($this->configPath)) {
            return ['host' => 'smtp.gmail.com', 'port' => 587, 'username' => '', 'password' => '', 'from' => '', 'from_name' => 'Content Planner'];
        }
        return require $this->configPath;
    }
}
