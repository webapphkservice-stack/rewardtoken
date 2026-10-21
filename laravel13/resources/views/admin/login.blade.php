@php
    $config = getSiteConfig();
    $langCode = currentLanguage();
@endphp
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(str_replace('_', '-', $langCode)) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('admin_login') ?> - <?= htmlspecialchars($config['site_name'] ?? t('site_name')) ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        .login-card {
            width: 400px;
            max-width: calc(100vw - 32px);
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            padding: 32px;
        }
        .login-brand {
            text-align: center;
            margin-bottom: 24px;
        }
        .login-brand h1 {
            margin: 0 0 6px;
            font-size: 1.35rem;
            color: #2d3748;
        }
        .login-brand p {
            margin: 0;
            font-size: 0.85rem;
            color: #718096;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 6px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #2d3748;
            background: #fff;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus {
            border-color: #41888A;
            box-shadow: 0 0 0 3px rgba(65,136,138,0.15);
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: #41888A;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-login:hover {
            background: #367274;
        }
        .login-error {
            background: #fff5f5;
            border: 1px solid #feb2b2;
            color: #c53030;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.85rem;
            margin-bottom: 16px;
        }
        .login-lang {
            margin-top: 20px;
            text-align: center;
        }
        .login-lang select {
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
            font-size: 0.8rem;
            color: #4a5568;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-brand">
            <h1>⚙️ <?= t('admin_login') ?></h1>
            <p><?= htmlspecialchars($config['site_name'] ?? t('site_name')) ?></p>
        </div>
        <?php if ($errors->has('login')): ?>
            <div class="login-error"><?= htmlspecialchars($errors->first('login')) ?></div>
        <?php endif; ?>
        <form method="POST" action="/admin/login">
            @csrf
            <div class="form-group">
                <label for="username"><?= t('username') ?></label>
                <input type="text" id="username" name="username" value="<?= old('username') ?>" required autofocus autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password"><?= t('password') ?></label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-login"><?= t('do_login') ?></button>
        </form>
        <div class="login-lang">
            <select onchange="changeLanguage(this.value)">
                <?php foreach (languageOptions() as $code => $label): ?>
                    <option value="<?= $code ?>" <?= $code === $langCode ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <script>
        function changeLanguage(lang) {
            const url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        }
    </script>
</body>
</html>
