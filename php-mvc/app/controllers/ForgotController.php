<?php
// app/controllers/ForgotController.php

class ForgotController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    // GET /forgot — show recovery code form
    public function forgotForm()
    {
        $error = null;
        require __DIR__ . '/../views/forgot.php';
    }

    // POST /forgot — validate recovery code
    public function forgot()
    {
        $recoveryCode = trim($_POST['recovery_code'] ?? '');
        $config = require __DIR__ . '/../../config/recovery.php';

        if (!$recoveryCode || !password_verify($recoveryCode, $config['recovery_code_hash'])) {
            $error = 'Невірний код відновлення';
            require __DIR__ . '/../views/forgot.php';
            return;
        }

        // Set session flag — allow reset for 15 min
        $_SESSION['password_reset_allowed'] = time() + 900;
        header('Location: /reset-password');
        exit;
    }

    // GET /reset-password — show new password form
    public function resetForm()
    {
        if (empty($_SESSION['password_reset_allowed']) || $_SESSION['password_reset_allowed'] < time()) {
            header('Location: /forgot');
            exit;
        }
        $error = null;
        $success = null;
        require __DIR__ . '/../views/reset-password.php';
    }

    // POST /reset-password — update password
    public function reset()
    {
        if (empty($_SESSION['password_reset_allowed']) || $_SESSION['password_reset_allowed'] < time()) {
            header('Location: /forgot');
            exit;
        }

        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm']  ?? '';
        $error    = null;
        $success  = null;

        if (strlen($password) < 6) {
            $error = 'Пароль має бути не менше 6 символів';
        } elseif ($password !== $confirm) {
            $error = 'Паролі не збігаються';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            // Update the first (only) admin record
            $this->db->query('UPDATE admin SET password_hash = ? LIMIT 1', [$hash]);
            unset($_SESSION['password_reset_allowed']);
            $success = 'Пароль успішно змінено! Тепер можеш увійти.';
        }

        require __DIR__ . '/../views/reset-password.php';
    }
}
