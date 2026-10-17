@php
    $config = getSiteConfig();
    $langCode = currentLanguage();
@endphp
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(str_replace('_', '-', $langCode)) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('deploy') ?> - <?= htmlspecialchars($config['site_name'] ?? t('site_name')) ?></title>
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
        .form-group input, .form-group select {
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
        .result {
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            margin-top: 16px;
            display: none;
        }
        .result p { margin: 6px 0; }
        .result code {
            font-family: 'SF Mono', Monaco, monospace;
            word-break: break-all;
            font-size: 0.9rem;
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
            <h1>🚀 <?= t('deploy') ?></h1>
            <p><?= t('deploy_desc') ?></p>
        </div>

        <div class="card">
            <h2><?= t('deploy_config') ?></h2>
            <div id="deployWarning" class="alert" style="display: block; background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                <strong>⚠️ <?= t('deploy_warning') ?></strong>
                <p style="margin: 8px 0 0; font-size: 0.9rem;"><?= t('current_deployed') ?>：<code id="currentContract"><?= t('connecting') ?></code></p>
                <label style="display: flex; align-items: center; gap: 8px; margin-top: 12px; font-weight: normal; cursor: pointer;">
                    <input type="checkbox" id="confirmRedeploy">
                    <span><?= t('redeploy_confirm') ?></span>
                </label>
            </div>
            <div id="alert" class="alert"></div>
            <form id="deployForm">
                <div class="form-group">
                    <label><?= t('token_name') ?></label>
                    <input type="text" name="name" value="Invite Reward Token" required>
                    <p class="hint"><?= t('token_name') ?></p>
                </div>
                <div class="form-group">
                    <label><?= t('token_symbol') ?></label>
                    <input type="text" name="symbol" value="IRT" required>
                    <p class="hint"><?= t('token_symbol') ?></p>
                </div>
                <div class="form-group">
                    <label><?= t('issue_total') ?></label>
                    <input type="number" name="total_supply" value="1000000" min="1" required>
                    <p class="hint"><?= t('issue_total') ?></p>
                </div>
                <div class="form-group">
                    <label><?= t('invite_reward') ?></label>
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
                <div class="form-group">
                    <label><?= t('reward_pool_ratio') ?></label>
                    <input type="number" name="reward_pool_percent" value="30" min="1" max="100" required>
                    <p class="hint"><?= t('reward_pool_ratio') ?></p>
                </div>
                <button type="submit" class="btn btn-primary" id="deployBtn">
                    <?= t('deploy_now') ?>
                </button>
                <a href="/admin/" class="btn btn-secondary"><?= t('back_to_admin') ?></a>
            </form>

            <div id="result" class="result">
                <h3 style="margin-top: 0;"><?= t('deploy_result') ?></h3>
                <p><strong><?= t('status') ?>：</strong> <span id="status" class="success"></span></p>
                <p><strong><?= t('contract_address') ?>：</strong> <code id="contractAddress"></code></p>
                <p><strong><?= t('tx_hash') ?>：</strong> <code id="txHash"></code></p>
                <p><strong><?= t('gas_used') ?>：</strong> <span id="gasUsed"></span></p>
            </div>
        </div>
    </main>

    <script>
        const ratioSumError = '<?= t('ratio_sum_error') ?>';
        const pleaseConfirmRedeploy = '<?= t('please_confirm_redeploy') ?>';
        const confirmDeploy = '<?= t('confirm_deploy') ?>';
        const deployingText = '<?= t('deploying') ?>';
        const deployNowText = '<?= t('deploy_now') ?>';
        const successText = '<?= t('success') ?>';
        const saveSuccessText = '<?= t('save_success') ?>';
        const saveFailedText = '<?= t('save_failed') ?>';
        const networkErrorText = '<?= t('network_error') ?>';
        const notDeployedText = '<?= t('not_deployed') ?>';
        const checkFailedText = '<?= t('check_failed') ?>';

        let hasDeployedContract = false;

        async function loadDeployStatus() {
            try {
                const res = await fetch('/api/status');
                const data = await res.json();
                const warning = document.getElementById('deployWarning');
                const currentContract = document.getElementById('currentContract');

                if (data.success) {
                    hasDeployedContract = true;
                    currentContract.textContent = data.contract_address;
                    warning.style.background = '#fee2e2';
                    warning.style.color = '#991b1b';
                    warning.style.borderColor = '#fecaca';
                } else {
                    hasDeployedContract = false;
                    currentContract.textContent = notDeployedText;
                }
            } catch (err) {
                hasDeployedContract = false;
                document.getElementById('currentContract').textContent = checkFailedText;
            }
        }

        document.getElementById('deployForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const alert = document.getElementById('alert');
            const result = document.getElementById('result');
            const btn = document.getElementById('deployBtn');
            
            if (hasDeployedContract && !document.getElementById('confirmRedeploy').checked) {
                alert.className = 'alert alert-error';
                alert.textContent = pleaseConfirmRedeploy;
                alert.style.display = 'block';
                return;
            }

            if (!confirm(confirmDeploy)) {
                return;
            }
            
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
            btn.innerHTML = '<span class="loading"></span> ' + deployingText;
            alert.style.display = 'none';
            result.style.display = 'none';

            const formData = new FormData(form);

            try {
                const res = await fetch('/api/deploy', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    alert.className = 'alert alert-success';
                    alert.textContent = data.message || saveSuccessText;
                    document.getElementById('status').textContent = successText;
                    document.getElementById('contractAddress').textContent = data.contract_address;
                    document.getElementById('txHash').textContent = data.tx_hash;
                    document.getElementById('gasUsed').textContent = data.gas_used;
                    result.style.display = 'block';
                } else {
                    alert.className = 'alert alert-error';
                    alert.textContent = data.message || saveFailedText;
                    result.style.display = 'none';
                }
            } catch (err) {
                alert.className = 'alert alert-error';
                alert.textContent = networkErrorText + ': ' + err.message;
                result.style.display = 'none';
            } finally {
                btn.disabled = false;
                btn.innerHTML = deployNowText;
                alert.style.display = 'block';
            }
        });

        loadDeployStatus();
    </script>
</body>
</html>
