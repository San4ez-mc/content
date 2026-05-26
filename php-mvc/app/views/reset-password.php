<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новий пароль — Content Planner</title>
    <link rel="stylesheet" href="/style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #5a6c7d 0%, #455562 100%);
            font-family: 'Inter', sans-serif;
        }
        .form-box {
            max-width: 420px;
            width: 100%;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            padding: 40px;
        }
        .form-box h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 22px;
        }
        .form-box .subtitle {
            text-align: center;
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 24px;
        }
        .form-box input {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 16px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-box input:focus {
            outline: none;
            border-color: #5a6c7d;
            box-shadow: 0 0 0 3px rgba(90,108,125,0.08);
        }
        .form-box button {
            width: 100%;
            background: #5a6c7d;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-box button:hover { background: #455562; transform: translateY(-1px); }
        .form-box button.secondary {
            background: #f5f5f7;
            color: #2c3e50;
            border: 1px solid #e0e0e0;
            margin-top: 8px;
        }
        .form-box button.secondary:hover { background: #e8e8f0; }
        .error {
            color: #991b1b;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .success {
            color: #065f46;
            background: #d1fae5;
            border: 1px solid #6ee7b7;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 16px;
            font-size: 14px;
            text-align: center;
        }
        .strength-bar {
            height: 4px;
            border-radius: 2px;
            background: #e5e7eb;
            margin-top: -12px;
            margin-bottom: 16px;
            overflow: hidden;
        }
        .strength-bar-fill {
            height: 100%;
            border-radius: 2px;
            transition: width 0.3s, background 0.3s;
            width: 0%;
        }
    </style>
</head>
<body>
    <div class="form-box">
        <h2>🔒 Новий пароль</h2>
        <p class="subtitle">Придумай надійний пароль (мінімум 6 символів)</p>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success">✅ <?php echo htmlspecialchars($success); ?></div>
            <form method="get" action="/login">
                <button type="submit">Увійти →</button>
            </form>
        <?php else: ?>
        <form method="post" action="/reset-password" id="resetForm">
            <input type="password" name="password" id="pwd" placeholder="Новий пароль" required autofocus oninput="checkStrength(this.value)">
            <div class="strength-bar"><div class="strength-bar-fill" id="strength-fill"></div></div>
            <input type="password" name="confirm" placeholder="Повтори пароль" required>
            <button type="submit">Зберегти пароль</button>
        </form>
        <form method="get" action="/forgot">
            <button type="submit" class="secondary">← Назад</button>
        </form>
        <?php endif; ?>
    </div>

    <script>
    function checkStrength(val) {
        const fill = document.getElementById('strength-fill');
        let score = 0;
        if (val.length >= 6) score++;
        if (val.length >= 10) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;
        const pct = (score / 5) * 100;
        const colors = ['#ef4444','#f97316','#eab308','#22c55e','#16a34a'];
        fill.style.width = pct + '%';
        fill.style.background = colors[score - 1] || '#e5e7eb';
    }
    </script>
</body>
</html>
