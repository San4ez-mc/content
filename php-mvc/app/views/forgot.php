<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Відновлення паролю — Content Planner</title>
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
            line-height: 1.5;
        }
        .form-box input {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 16px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            letter-spacing: 0.05em;
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
        .hint {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 16px;
            font-size: 13px;
            color: #0369a1;
            line-height: 1.5;
        }
        .hint code {
            font-family: monospace;
            background: #e0f2fe;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="form-box">
        <h2>🔑 Відновлення паролю</h2>
        <p class="subtitle">Введи код відновлення, щоб встановити новий пароль</p>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="hint">
            Код відновлення: <code>FINEKO-RESET-2026</code><br>
            Після зміни паролю збережи новий код у надійному місці.
        </div>

        <form method="post" action="/forgot">
            <input type="text" name="recovery_code" placeholder="Код відновлення" required autofocus autocomplete="off">
            <button type="submit">Продовжити →</button>
        </form>
        <form method="get" action="/login">
            <button type="submit" class="secondary">← Назад до входу</button>
        </form>
    </div>
</body>
</html>
