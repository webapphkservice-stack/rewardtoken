@php
    $config = getSiteConfig();
    $langCode = currentLanguage();
@endphp
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(str_replace('_', '-', $langCode)) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('basic_settings') ?> - <?= htmlspecialchars($config['site_name'] ?? t('site_name')) ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
            color: #333;
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 260px;
            background: #fff;
            box-shadow: 2px 0 6px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 100;
        }
        .sidebar-brand {
            padding: 24px;
            border-bottom: 1px solid #e2e8f0;
        }
        .sidebar-brand h2 {
            margin: 0 0 6px;
            font-size: 1.25rem;
            color: #2d3748;
        }
        .sidebar-brand p {
            margin: 0;
            font-size: 0.85rem;
            color: #718096;
        }
        .sidebar-nav {
            display: flex;
            flex-direction: column;
            padding: 16px 12px;
            gap: 4px;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: #4a5568;
            font-weight: 600;
            transition: all 0.2s;
        }
        .sidebar-nav a:hover {
            background: #f0f7f7;
            color: #41888A;
        }
        .sidebar-nav a.active {
            background: #41888A;
            color: #fff;
        }
        .sidebar-nav a .icon {
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }
        .main {
            flex: 1;
            margin-left: 260px;
            padding: 40px;
        }
        .header {
            margin-bottom: 32px;
        }
        .header h1 {
            margin: 0 0 8px;
            color: #2d3748;
            font-size: 1.8rem;
        }
        .header p {
            color: #718096;
            margin: 0;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 24px;
            margin-bottom: 24px;
        }
        .card h2 {
            margin: 0 0 20px;
            font-size: 1.2rem;
            color: #2d3748;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #4a5568;
        }
        .form-group input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        .hint {
            font-size: 0.85rem;
            color: #718096;
            margin-top: 4px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #41888A 0%, #2a6b6d 100%);
            color: #fff;
        }
        .btn-secondary {
            background: #edf2f7;
            color: #4a5568;
            margin-left: 12px;
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .current-config {
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        .current-config p {
            margin: 6px 0;
        }
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #fff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-brand h2, .sidebar-brand p, .sidebar-nav a span:not(.icon) { display: none; }
            .main { margin-left: 70px; padding: 20px; }
        }
    </style>
</head>
<body>
    @include('admin.partials.sidebar')

    <main class="main">
        <div class="header">
            <h1>⚙️ <?= t('basic_settings') ?></h1>
            <p><?= t('basic_settings_desc') ?></p>
        </div>

        <div class="card">
            <h2><?= t('site_info') ?></h2>
            <div id="siteAlert" class="alert"></div>
            <form id="siteForm">
                <div class="form-group">
                    <label><?= t('site_full_name') ?></label>
                    <input type="text" name="site_name_full" value="<?= htmlspecialchars($config['site_name_full'] ?? t('site_name_full')) ?>" required>
                    <p class="hint"><?= t('site_full_name_hint') ?></p>
                </div>
                <div class="form-group">
                    <label><?= t('site_short_name') ?></label>
                    <input type="text" name="site_name" value="<?= htmlspecialchars($config['site_name'] ?? t('site_name')) ?>" required>
                    <p class="hint"><?= t('site_short_name_hint') ?></p>
                </div>
                <div class="form-group">
                    <label><?= t('token_symbol_label') ?></label>
                    <input type="text" name="symbol" value="<?= htmlspecialchars($config['symbol'] ?? 'IRT') ?>" required>
                    <p class="hint"><?= t('token_symbol_label') ?></p>
                </div>
                <div class="form-group">
                    <label><?= t('site_description') ?></label>
                    <input type="text" name="site_description" value="<?= htmlspecialchars($config['site_description'] ?? t('site_description')) ?>">
                    <p class="hint"><?= t('site_description_hint') ?></p>
                </div>
                <div class="form-group">
                    <label><?= t('transfer_fee_label') ?></label>
                    <input type="number" name="transfer_fee" value="<?= htmlspecialchars(is_numeric($config['transfer_fee'] ?? null) ? $config['transfer_fee'] : 0) ?>" min="0" max="100" step="0.01">
                    <p class="hint"><?= t('transfer_fee_hint') ?></p>
                </div>
                <button type="submit" class="btn btn-primary" id="saveSiteBtn">
                    <?= t('save_site_info') ?>
                </button>
            </form>
        </div>

        <div class="card">
            <h2><?= t('current_reward_setting') ?></h2>
            <div class="current-config" id="currentConfig">
                <p><strong><?= t('invite_reward') ?>：</strong><span id="currentInviteReward"><?= t('loading') ?></span></p>
                <p><strong><?= t('level1_ratio') ?>：</strong><span id="currentLevel1"><?= t('loading') ?></span></p>
                <p><strong><?= t('level2_ratio') ?>：</strong><span id="currentLevel2"><?= t('loading') ?></span></p>
                <p><strong><?= t('level3_ratio') ?>：</strong><span id="currentLevel3"><?= t('loading') ?></span></p>
            </div>
        </div>

        <div class="card">
            <h2><?= t('token_purchase_settings') ?></h2>
            <div id="purchaseAlert" class="alert"></div>
            <form id="purchaseForm">
                <div class="form-row">
                    <div class="form-group">
                        <label><?= t('token_purchase_price_label') ?> (USDT)</label>
                        <input type="number" name="token_purchase_price_usdt" value="<?= htmlspecialchars($config['token_purchase_price_usdt'] ?? 10) ?>" min="1" step="0.01" required>
                        <p class="hint"><?= t('token_purchase_price_hint') ?></p>
                    </div>
                    <div class="form-group">
                        <label><?= t('token_purchase_amount_label') ?> (<?= htmlspecialchars($config['symbol'] ?? 'IRT') ?>)</label>
                        <input type="number" name="token_purchase_amount" value="<?= htmlspecialchars($config['token_purchase_amount'] ?? 1000) ?>" min="1" required>
                        <p class="hint"><?= t('token_purchase_amount_hint', ['price' => htmlspecialchars($config['token_purchase_price_usdt'] ?? 10)]) ?></p>
                    </div>
                    <div class="form-group">
                        <label><?= t('token_purchase_bonus_label') ?> (<?= htmlspecialchars($config['symbol'] ?? 'IRT') ?>)</label>
                        <input type="number" name="token_purchase_bonus" value="<?= htmlspecialchars($config['token_purchase_bonus'] ?? 0) ?>" min="0" required>
                        <p class="hint"><?= t('token_purchase_bonus_hint') ?></p>
                    </div>
                </div>
                <div class="current-config" style="margin-top:12px;margin-bottom:16px;">
                    <p><strong><?= t('token_purchase_summary') ?>：</strong>
                    <span id="purchaseSummary">
                        <?= htmlspecialchars($config['token_purchase_price_usdt'] ?? 10) ?> USDT =
                        <?= htmlspecialchars($config['token_purchase_amount'] ?? 1000) ?> <?= htmlspecialchars($config['symbol'] ?? 'IRT') ?>
                        <?php if (!empty($config['token_purchase_bonus'])): ?>
                            + <?= t('bonus_gift', ['amount' => $config['token_purchase_bonus'], 'symbol' => $config['symbol'] ?? 'IRT']) ?>
                        <?php endif; ?>
                    </span>
                    </p>
                </div>
                <button type="submit" class="btn btn-primary" id="savePurchaseBtn">
                    <?= t('save_purchase_settings') ?>
                </button>
            </form>
        </div>

        <div class="card">
            <h2><?= t('modify_config') ?></h2>
            <div id="alert" class="alert"></div>
            <form id="settingsForm">
                <div class="form-group">
                    <label><?= t('reward_per_invite', ['symbol' => htmlspecialchars($config['symbol'] ?? 'IRT')]) ?></label>
                    <input type="number" name="invite_reward" value="100" min="1" required>
                    <p class="hint"><?= t('total_invite_reward') ?></p>
                </div>
                <div class="form-group">
                    <label><?= t('reward_ratio_hint') ?></label>
                    <div class="form-row">
                        <div>
                            <input type="number" name="level1" value="50" min="0" max="100" required>
                            <p class="hint"><?= t('level1_percent') ?></p>
                        </div>
                        <div>
                            <input type="number" name="level2" value="30" min="0" max="100" required>
                            <p class="hint"><?= t('level2_percent') ?></p>
                        </div>
                        <div>
                            <input type="number" name="level3" value="20" min="0" max="100" required>
                            <p class="hint"><?= t('level3_percent') ?></p>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" id="saveBtn">
                    <?= t('save_settings') ?>
                </button>
                <a href="/admin/" class="btn btn-secondary"><?= t('back_to_admin') ?></a>
            </form>
        </div>
    </main>

    <script>
        const ratioSumError = '<?= t('ratio_sum_error') ?>';
        const savingText = '<?= t('saving') ?>';
        const saveSettingsText = '<?= t('save_settings') ?>';
        const savePurchaseText = '<?= t('save_purchase_settings') ?>';

        document.getElementById('purchaseForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const alert = document.getElementById('purchaseAlert');
            const btn = document.getElementById('savePurchaseBtn');
            const summary = document.getElementById('purchaseSummary');

            btn.disabled = true;
            btn.innerHTML = '<span class="loading"></span> ' + savingText;
            alert.style.display = 'none';

            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            try {
                const res = await fetch('/api/site-settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();

                if (result.success) {
                    alert.className = 'alert alert-success';
                    alert.textContent = result.message || saveSuccessText;
                    // update summary
                    const price = data.token_purchase_price_usdt || '10';
                    const amt = data.token_purchase_amount || '1000';
                    const bonus = parseInt(data.token_purchase_bonus) || 0;
                    let summaryText = price + ' USDT = ' + amt + ' IRT';
                    if (bonus > 0) summaryText += ' + 赠送 ' + bonus + ' IRT';
                    summary.textContent = summaryText;
                } else {
                    alert.className = 'alert alert-error';
                    alert.textContent = result.message || saveFailedText;
                }
            } catch (err) {
                alert.className = 'alert alert-error';
                alert.textContent = networkErrorText + ': ' + err.message;
            } finally {
                btn.disabled = false;
                btn.innerHTML = savePurchaseText;
                alert.style.display = 'block';
            }
        });

            saveSiteInfoText = '<?= t('save_site_info') ?>';
        const saveSuccessText = '<?= t('save_success') ?>';
        const saveFailedText = '<?= t('save_failed') ?>';
        const networkErrorText = '<?= t('network_error') ?>';

        async function loadCurrentConfig() {
            try {
                const res = await fetch('/api/status');
                const data = await res.json();
                if (data.success) {
                    document.getElementById('currentInviteReward').textContent = data.invite_reward + ' ' + data.symbol;
                    document.getElementById('currentLevel1').textContent = data.referral_levels[0] + '%';
                    document.getElementById('currentLevel2').textContent = data.referral_levels[1] + '%';
                    document.getElementById('currentLevel3').textContent = data.referral_levels[2] + '%';
                    
                    document.querySelector('input[name="invite_reward"]').value = data.invite_reward;
                    document.querySelector('input[name="level1"]').value = data.referral_levels[0];
                    document.querySelector('input[name="level2"]').value = data.referral_levels[1];
                    document.querySelector('input[name="level3"]').value = data.referral_levels[2];
                }
            } catch (err) {
                console.error(networkErrorText + ':', err);
            }
        }

        document.getElementById('settingsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const alert = document.getElementById('alert');
            const btn = document.getElementById('saveBtn');
            
            const level1 = parseInt(form.level1.value);
            const level2 = parseInt(form.level2.value);
            const level3 = parseInt(form.level3.value);
            
            if (level1 + level2 + level3 > 100) {
                alert.className = 'alert alert-error';
                alert.textContent = ratioSumError;
                alert.style.display = 'block';
                return;
            }
            
            btn.disabled = true;
            btn.innerHTML = '<span class="loading"></span> ' + savingText;
            alert.style.display = 'none';

            const formData = new FormData(form);

            try {
                const res = await fetch('/api/settings', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    alert.className = 'alert alert-success';
                    alert.textContent = data.message || saveSuccessText;
                    loadCurrentConfig();
                } else {
                    alert.className = 'alert alert-error';
                    alert.textContent = data.message || saveFailedText;
                }
            } catch (err) {
                alert.className = 'alert alert-error';
                alert.textContent = networkErrorText + ': ' + err.message;
            } finally {
                btn.disabled = false;
                btn.innerHTML = saveSettingsText;
                alert.style.display = 'block';
            }
        });

        document.getElementById('siteForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const alert = document.getElementById('siteAlert');
            const btn = document.getElementById('saveSiteBtn');

            btn.disabled = true;
            btn.innerHTML = '<span class="loading"></span> ' + savingText;
            alert.style.display = 'none';

            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            try {
                const res = await fetch('/api/site-settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();

                if (result.success) {
                    alert.className = 'alert alert-success';
                    alert.textContent = result.message || saveSuccessText;
                } else {
                    alert.className = 'alert alert-error';
                    alert.textContent = result.message || saveFailedText;
                }
            } catch (err) {
                alert.className = 'alert alert-error';
                alert.textContent = networkErrorText + ': ' + err.message;
            } finally {
                btn.disabled = false;
                btn.innerHTML = saveSiteInfoText;
                alert.style.display = 'block';
            }
        });

        loadCurrentConfig();
    </script>
</body>
</html>
