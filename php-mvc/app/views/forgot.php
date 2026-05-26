<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Відновлення паролю — Content Planner</title>
    <link rel="stylesheet" href="/style.css">
    <style>
        body { display:flex;justify-content:center;align-items:center;min-height:100vh;background:linear-gradient(135deg,#5a6c7d 0%,#455562 100%);font-family:'Inter',sans-serif; }
        .form-box { max-width:420px;width:100%;background:#fff;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.08);padding:40px; }
        .form-box h2 { text-align:center;color:#2c3e50;margin-bottom:6px;font-size:22px; }
        .subtitle { text-align:center;color:#6b7280;font-size:13px;margin-bottom:24px;line-height:1.5; }
        input[type=email] { width:100%;padding:10px 12px;margin-bottom:16px;border:1px solid #e0e0e0;border-radius:6px;font-size:14px;box-sizing:border-box; }
        input[type=email]:focus { outline:none;border-color:#5a6c7d;box-shadow:0 0 0 3px rgba(90,108,125,.08); }
        .btn { width:100%;background:#5a6c7d;color:#fff;padding:10px;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:14px;transition:all .2s; }
        .btn:hover { background:#455562;transform:translateY(-1px); }
        .btn.secondary { background:#f5f5f7;color:#2c3e50;border:1px solid #e0e0e0;margin-top:8px; }
        .btn.secondary:hover { background:#e8e8f0; }
        .error { color:#991b1b;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;padding:12px;margin-bottom:16px;font-size:14px; }
        .success { color:#065f46;background:#d1fae5;border:1px solid #6ee7b7;border-radius:6px;padding:14px;margin-bottom:16px;font-size:14px;line-height:1.5; }
    </style>
</head>
<body>
<div class="form-box">
    <h2>🔑 Відновлення паролю</h2>
    <p class="subtitle">Введи свою email-адресу — надішлемо посилання для зміни паролю</p>

    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="success">✉️ <?= htmlspecialchars($success) ?></div>
        <a href="/login"><button class="btn secondary">← Назад до входу</button></a>
    <?php else: ?>
        <form method="post" action="/forgot">
            <input type="email" name="email" placeholder="your@email.com" required autofocus>
            <button type="submit" class="btn">Надіслати посилання</button>
        </form>
        <a href="/login"><button class="btn secondary">← Назад до входу</button></a>
    <?php endif; ?>
</div>
</body>
</html>
