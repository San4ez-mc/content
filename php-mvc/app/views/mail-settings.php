<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Налаштування пошти — Content Planner</title>
    <link rel="stylesheet" href="/style.css">
    <style>
        body { background:#f3f4f6;font-family:'Inter',sans-serif;padding:32px 16px; }
        .card { max-width:580px;margin:0 auto;background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.07);padding:36px; }
        h1 { font-size:20px;color:#111827;margin:0 0 4px; }
        .sub { color:#6b7280;font-size:13px;margin-bottom:28px; }
        .field { margin-bottom:18px; }
        label { display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px; }
        input[type=email],input[type=text],input[type=password] {
            width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:7px;font-size:14px;box-sizing:border-box;
        }
        input:focus { outline:none;border-color:#5a6c7d;box-shadow:0 0 0 3px rgba(90,108,125,.1); }
        .input-wrap { position:relative;display:flex;align-items:center; }
        .input-wrap input { padding-right:44px; }
        .eye-btn { position:absolute;right:10px;background:none;border:none;cursor:pointer;font-size:16px;line-height:1; }
        .hint { font-size:12px;color:#9ca3af;margin-top:4px;line-height:1.5; }
        .hint a { color:#5a6c7d; }
        .btn-row { display:flex;gap:10px;margin-top:24px; }
        .btn { flex:1;padding:10px;border:none;border-radius:7px;cursor:pointer;font-weight:600;font-size:14px;transition:all .2s; }
        .btn-primary { background:#5a6c7d;color:#fff; }
        .btn-primary:hover { background:#455562; }
        .btn-test { background:#f0fdf4;color:#166534;border:1px solid #bbf7d0; }
        .btn-test:hover { background:#dcfce7; }
        .btn-back { background:#f5f5f7;color:#374151;border:1px solid #e0e0e0; }
        .btn-back:hover { background:#e8e8f0; }
        .alert { padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:20px;display:flex;align-items:center;gap:8px; }
        .alert-success { background:#d1fae5;border:1px solid #6ee7b7;color:#065f46; }
        .alert-error   { background:#fef2f2;border:1px solid #fecaca;color:#991b1b; }
        .status-badge { display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:100px;font-size:12px;font-weight:600;margin-left:10px; }
        .status-ok  { background:#d1fae5;color:#065f46; }
        .status-no  { background:#fef3c7;color:#92400e; }
        .steps { background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px 20px;margin-bottom:24px; }
        .steps h3 { font-size:13px;font-weight:700;color:#374151;margin:0 0 10px; }
        .steps ol { margin:0;padding-left:18px;color:#4b5563;font-size:13px;line-height:1.9; }
        .steps a { color:#5a6c7d; }
        code { background:#f1f5f9;padding:2px 6px;border-radius:4px;font-family:monospace;font-size:12px; }
        #test-result { font-size:13px;padding:8px 12px;border-radius:6px;display:none;margin-top:10px; }
    </style>
</head>
<body>
<div class="card">
    <h1>✉️ Налаштування пошти
        <span class="status-badge <?= $configured ? 'status-ok' : 'status-no' ?>">
            <?= $configured ? '✅ Налаштовано' : '⚠️ Не налаштовано' ?>
        </span>
    </h1>
    <p class="sub">Потрібно для відновлення паролю через email</p>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $message['type'] ?>"><?= htmlspecialchars($message['text']) ?></div>
    <?php endif; ?>

    <!-- Instructions -->
    <div class="steps">
        <h3>📋 Як отримати Gmail App Password (1 хвилина)</h3>
        <ol>
            <li>Відкрий <a href="https://myaccount.google.com/security" target="_blank">myaccount.google.com/security</a></li>
            <li>Переконайся що увімкнена <strong>Двоетапна перевірка</strong></li>
            <li>Знайди розділ <strong>Паролі застосунків</strong> (внизу сторінки)</li>
            <li>Назва: <code>Content Planner</code> → кнопка <strong>Створити</strong></li>
            <li>Скопіюй 16-символьний пароль → вставити нижче</li>
        </ol>
    </div>

    <form method="post" action="/settings/mail-save">
        <div class="field">
            <label>Gmail адреса (від якої надсилати)</label>
            <input type="email" name="from_email"
                   value="<?= htmlspecialchars($cfg['from'] ?? '') ?>"
                   placeholder="olexandrmasuk@gmail.com" required>
        </div>
        <div class="field">
            <label>Gmail App Password <span style="color:#ef4444">*</span></label>
            <div class="input-wrap">
                <input type="password" name="app_password" id="app-pwd"
                       placeholder="xxxx xxxx xxxx xxxx"
                       autocomplete="new-password">
                <button type="button" class="eye-btn" onclick="toggleVis('app-pwd',this)">👁</button>
            </div>
            <p class="hint">
                16 символів без пробілів. Зберігається тільки на сервері, я не маю до нього доступу.<br>
                Якщо поле залишити порожнім — поточний пароль не зміниться.
            </p>
        </div>
        <div class="field">
            <label>Ім'я відправника</label>
            <input type="text" name="from_name"
                   value="<?= htmlspecialchars($cfg['from_name'] ?? 'Content Planner') ?>"
                   placeholder="Content Planner">
        </div>
        <div class="btn-row">
            <button type="submit" class="btn btn-primary">💾 Зберегти</button>
            <?php if ($configured): ?>
            <button type="button" class="btn btn-test" onclick="sendTest()">📨 Надіслати тест</button>
            <?php endif; ?>
            <a href="/"><button type="button" class="btn btn-back">← Назад</button></a>
        </div>
        <div id="test-result"></div>
    </form>
</div>

<script>
function toggleVis(id, btn) {
    const el = document.getElementById(id);
    if (!el) return;
    el.type = el.type === 'password' ? 'text' : 'password';
    btn.textContent = el.type === 'password' ? '👁' : '🙈';
}

async function sendTest() {
    const r = document.getElementById('test-result');
    r.style.display = 'block';
    r.style.background = '#f1f5f9';
    r.style.color = '#374151';
    r.textContent = '⏳ Надсилаю...';
    try {
        const resp = await fetch('/settings/mail-test', { method: 'POST' });
        const json = await resp.json();
        if (json.ok) {
            r.style.background = '#d1fae5';
            r.style.color = '#065f46';
            r.textContent = '✅ Тестовий лист надіслано на ' + document.querySelector('[name=from_email]').value;
        } else {
            r.style.background = '#fef2f2';
            r.style.color = '#991b1b';
            r.textContent = '❌ Помилка: ' + (json.error || 'невідома');
        }
    } catch(e) {
        r.style.background = '#fef2f2';
        r.style.color = '#991b1b';
        r.textContent = '❌ Помилка з\'єднання';
    }
}
</script>
</body>
</html>
