<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новий пароль — Content Planner</title>
    <link rel="stylesheet" href="/style.css">
    <style>
        body { display:flex;justify-content:center;align-items:center;min-height:100vh;background:linear-gradient(135deg,#5a6c7d 0%,#455562 100%);font-family:'Inter',sans-serif; }
        .form-box { max-width:440px;width:100%;background:#fff;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.08);padding:40px; }
        .form-box h2 { text-align:center;color:#2c3e50;margin-bottom:6px;font-size:22px; }
        .subtitle { text-align:center;color:#6b7280;font-size:13px;margin-bottom:24px; }
        .field { margin-bottom:16px; }
        .field label { display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px; }
        .input-wrap { position:relative;display:flex;align-items:center; }
        .input-wrap input { flex:1;padding:10px 40px 10px 12px;border:1px solid #e0e0e0;border-radius:6px;font-size:14px;font-family:monospace; }
        .input-wrap input:focus { outline:none;border-color:#5a6c7d;box-shadow:0 0 0 3px rgba(90,108,125,.08); }
        .eye-btn { position:absolute;right:8px;background:none;border:none;cursor:pointer;font-size:16px;padding:4px;line-height:1; }
        .strength-bar { height:4px;border-radius:2px;background:#e5e7eb;margin-top:6px;overflow:hidden; }
        .strength-fill { height:100%;border-radius:2px;transition:width .25s,background .25s;width:0% }
        .strength-label { font-size:11px;color:#9ca3af;margin-top:3px; }
        .gen-btn { display:flex;align-items:center;gap:6px;margin-top:12px;margin-bottom:4px;background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;border-radius:6px;padding:8px 14px;cursor:pointer;font-size:13px;font-weight:600;transition:all .2s;width:100%;justify-content:center; }
        .gen-btn:hover { background:#dcfce7; }
        .gen-hint { font-size:11px;color:#9ca3af;text-align:center;margin-bottom:14px; }
        .copy-notice { font-size:12px;color:#16a34a;text-align:center;min-height:16px;margin-top:2px; }
        .btn { width:100%;background:#5a6c7d;color:#fff;padding:10px;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:14px;transition:all .2s;margin-top:4px; }
        .btn:hover { background:#455562;transform:translateY(-1px); }
        .btn.secondary { background:#f5f5f7;color:#2c3e50;border:1px solid #e0e0e0;margin-top:8px; }
        .btn.secondary:hover { background:#e8e8f0; }
        .error { color:#991b1b;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;padding:12px;margin-bottom:16px;font-size:14px; }
        .success { color:#065f46;background:#d1fae5;border:1px solid #6ee7b7;border-radius:6px;padding:14px;margin-bottom:16px;font-size:14px;text-align:center; }
        .divider { border:none;border-top:1px solid #f3f4f6;margin:20px 0; }
    </style>
</head>
<body>
<div class="form-box">
    <h2>🔒 Новий пароль</h2>
    <p class="subtitle">Мінімум 8 символів. Або згенеруй надійний автоматично.</p>

    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="success">✅ <?= htmlspecialchars($success) ?></div>
        <a href="/login"><button class="btn">Увійти →</button></a>
    <?php elseif (!empty($token)): ?>

        <!-- Password generator -->
        <button type="button" class="gen-btn" onclick="generatePassword()">
            🎲 Згенерувати надійний пароль
        </button>
        <p class="gen-hint">Буде автоматично вставлений у поля нижче</p>
        <div class="copy-notice" id="copy-notice"></div>

        <hr class="divider">

        <form method="post" action="/reset-password" id="resetForm">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

            <div class="field">
                <label>Новий пароль</label>
                <div class="input-wrap">
                    <input type="password" name="password" id="pwd" required oninput="checkStrength(this.value)" autocomplete="new-password">
                    <button type="button" class="eye-btn" onclick="toggleVis('pwd',this)">👁</button>
                </div>
                <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
                <div class="strength-label" id="strength-label"></div>
            </div>

            <div class="field">
                <label>Повтори пароль</label>
                <div class="input-wrap">
                    <input type="password" name="confirm" id="confirm" required autocomplete="new-password">
                    <button type="button" class="eye-btn" onclick="toggleVis('confirm',this)">👁</button>
                </div>
            </div>

            <button type="submit" class="btn">Зберегти пароль</button>
        </form>
        <a href="/forgot"><button class="btn secondary">← Запросити нове посилання</button></a>

    <?php else: ?>
        <div class="error">Посилання недійсне або вже використане.</div>
        <a href="/forgot"><button class="btn">Запросити нове →</button></a>
    <?php endif; ?>
</div>

<script>
function generatePassword() {
    const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    const lower = 'abcdefghjkmnpqrstuvwxyz';
    const digits = '23456789';
    const special = '!@#$%-+';
    const all = upper + lower + digits + special;
    let pwd = [
        upper[Math.floor(Math.random()*upper.length)],
        lower[Math.floor(Math.random()*lower.length)],
        digits[Math.floor(Math.random()*digits.length)],
        special[Math.floor(Math.random()*special.length)],
    ];
    for (let i = 4; i < 14; i++) pwd.push(all[Math.floor(Math.random()*all.length)]);
    pwd = pwd.sort(()=>Math.random()-.5).join('');

    const p = document.getElementById('pwd');
    const c = document.getElementById('confirm');
    if (p) { p.value = pwd; p.type = 'text'; checkStrength(pwd); }
    if (c) { c.value = pwd; c.type = 'text'; }

    // Copy to clipboard
    navigator.clipboard?.writeText(pwd).then(() => {
        const n = document.getElementById('copy-notice');
        if (n) { n.textContent = '📋 Пароль скопійовано: ' + pwd; }
    }).catch(() => {
        const n = document.getElementById('copy-notice');
        if (n) { n.textContent = '🔑 Пароль: ' + pwd + ' (скопіюй вручну)'; }
    });
}

function toggleVis(id, btn) {
    const el = document.getElementById(id);
    if (!el) return;
    el.type = el.type === 'password' ? 'text' : 'password';
    btn.textContent = el.type === 'password' ? '👁' : '🙈';
}

function checkStrength(val) {
    const fill  = document.getElementById('strength-fill');
    const label = document.getElementById('strength-label');
    if (!fill || !label) return;
    let score = 0;
    if (val.length >= 8)  score++;
    if (val.length >= 12) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const pcts  = [0, 20, 40, 60, 80, 100];
    const colors = ['#e5e7eb','#ef4444','#f97316','#eab308','#22c55e','#16a34a'];
    const labels = ['','Дуже слабкий','Слабкий','Середній','Надійний','Відмінний'];
    fill.style.width   = pcts[score] + '%';
    fill.style.background = colors[score];
    label.textContent  = labels[score] || '';
    label.style.color  = colors[score];
}
</script>
</body>
</html>
