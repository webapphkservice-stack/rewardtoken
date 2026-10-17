@php
    $config = getSiteConfig();
    $langCode = currentLanguage();
@endphp
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(str_replace('_', '-', $langCode)) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('control_panel') ?> - <?= htmlspecialchars($config['site_name'] ?? t('site_name')) ?></title>
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
            margin: 0 0 16px;
            font-size: 1.2rem;
            color: #2d3748;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }
        .info-item {
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            border-left: 4px solid #41888A;
        }
        .info-item label {
            display: block;
            font-size: 0.85rem;
            color: #718096;
            margin-bottom: 4px;
        }
        .info-item code, .info-item span {
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 0.9rem;
            word-break: break-all;
            color: #2d3748;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-ok { background: #d1fae5; color: #065f46; }
        .status-error { background: #fee2e2; color: #991b1b; }
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
        .btn-primary:hover { opacity: 0.9; }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .chain {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            margin: 20px 0;
            padding: 20px;
            background: #edf2f7;
            border-radius: 8px;
        }
        .chain-node {
            padding: 10px 16px;
            border-radius: 20px;
            background: #41888A;
            color: #fff;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .arrow {
            color: #a0aec0;
            font-weight: 700;
        }
        .steps {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .step {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 16px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .step-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #41888A;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }
        .step-content {
            flex: 1;
        }
        .step-title {
            font-weight: 600;
            margin-bottom: 4px;
            color: #2d3748;
        }
        .step-detail {
            font-size: 0.9rem;
            color: #4a5568;
            line-height: 1.5;
        }
        .success { color: #059669; }
        .error { color: #dc2626; }
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #41888A;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .reward-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }
        .reward-card {
            background: linear-gradient(135deg, #41888A 0%, #2a6b6d 100%);
            color: #fff;
            padding: 16px;
            border-radius: 8px;
        }
        .reward-card h4 {
            margin: 0 0 8px;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .reward-card .amount {
            font-size: 1.5rem;
            font-weight: 700;
        }
        pre {
            background: #2d3748;
            color: #e2e8f0;
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 0.85rem;
            line-height: 1.5;
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
            <h1>📊 <?= t('control_panel') ?></h1>
            <p><?= t('contract_status') ?> &amp; <?= t('current_reward_config') ?></p>
        </div>

        <div class="card">
            <h2><?= t('contract_status') ?></h2>
            <div class="info-grid">
                <div class="info-item">
                    <label><?= t('contract_address') ?></label>
                    <code id="contractAddress"><?= t('loading') ?></code>
                </div>
                <div class="info-item">
                    <label><?= t('token_name') ?></label>
                    <span id="tokenName"><?= t('loading') ?></span>
                </div>
                <div class="info-item">
                    <label><?= t('token_symbol') ?></label>
                    <span id="tokenSymbol"><?= t('loading') ?></span>
                </div>
                <div class="info-item">
                    <label>Geth <?= t('connecting') ?></label>
                    <span id="gethStatus"><span class="status-badge status-error"><?= t('connecting') ?></span></span>
                </div>
                <div class="info-item">
                    <label><?= t('registered_wallets') ?></label>
                    <span id="walletCount"><?= t('loading') ?></span>
                </div>
            </div>
        </div>

        <div class="card">
            <h2><?= t('current_reward_config') ?></h2>
            <div class="info-grid">
                <div class="info-item">
                    <label><?= t('invite_reward') ?></label>
                    <span id="inviteReward"><?= t('loading') ?></span>
                </div>
                <div class="info-item">
                    <label><?= t('level1_ratio') ?></label>
                    <span id="level1"><?= t('loading') ?></span>
                </div>
                <div class="info-item">
                    <label><?= t('level2_ratio') ?></label>
                    <span id="level2"><?= t('loading') ?></span>
                </div>
                <div class="info-item">
                    <label><?= t('level3_ratio') ?></label>
                    <span id="level3"><?= t('loading') ?></span>
                </div>
            </div>
        </div>

        <div class="card">
            <h2><?= t('invite_chain_demo') ?></h2>
            <p><?= t('invite_chain_desc') ?></p>
            <div class="chain">
                <div class="chain-node">Admin</div>
                <span class="arrow">→</span>
                <div class="chain-node">User1</div>
                <span class="arrow">→</span>
                <div class="chain-node">User2</div>
                <span class="arrow">→</span>
                <div class="chain-node">User3</div>
            </div>
            <button class="btn btn-primary" id="runBtn" onclick="runTest()">
                <?= t('run_invite_test') ?>
            </button>
        </div>

        <div class="card" id="resultCard" style="display: none;">
            <h2><?= t('test_result') ?></h2>
            <div id="resultContent"></div>
        </div>
    </main>

    <script>
        const notConnected = '<?= t('not_connected') ?>';
        const connectedText = '<?= t('connected') ?>';
        const loadingText = '<?= t('loading') ?>';
        const runningText = '<?= t('running') ?>';
        const rerunText = '<?= t('rerun_test') ?>';
        const testFailedText = '<?= t('test_failed') ?>';
        const runFailedText = '<?= t('run_failed') ?>';
        const rewardSummaryText = '<?= t('reward_summary') ?>';
        const rawLogsText = '<?= t('raw_logs') ?>';

        async function loadStatus() {
            try {
                const res = await fetch('/api/status');
                const data = await res.json();
                
                if (data.success) {
                    document.getElementById('contractAddress').textContent = data.contract_address || notConnected;
                    document.getElementById('tokenName').textContent = data.name;
                    document.getElementById('tokenSymbol').textContent = data.symbol;
                    document.getElementById('gethStatus').innerHTML = '<span class="status-badge status-ok">' + connectedText + '</span>';
                    document.getElementById('inviteReward').textContent = data.invite_reward + ' ' + data.symbol;
                    document.getElementById('level1').textContent = data.referral_levels[0] + '%';
                    document.getElementById('level2').textContent = data.referral_levels[1] + '%';
                    document.getElementById('level3').textContent = data.referral_levels[2] + '%';
                    document.getElementById('walletCount').textContent = (data.wallet_count ?? 0) + ' <?= t('registered_wallets') ?>';
                } else {
                    document.getElementById('gethStatus').innerHTML = '<span class="status-badge status-error">' + notConnected + '</span>';
                }
            } catch (err) {
                document.getElementById('gethStatus').innerHTML = '<span class="status-badge status-error">' + notConnected + '</span>';
            }
        }

        async function runTest() {
            const btn = document.getElementById('runBtn');
            const resultCard = document.getElementById('resultCard');
            const content = document.getElementById('resultContent');
            
            btn.disabled = true;
            btn.innerHTML = '<span class="loading"></span> ' + runningText;
            resultCard.style.display = 'block';
            content.innerHTML = '<p>' + runningText + '</p>';

            try {
                const res = await fetch('/api/run-test', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                renderResult(data);
                loadStatus();
            } catch (err) {
                content.innerHTML = `<p class="error">${runFailedText}: ${escapeHtml(err.message)}</p>`;
            } finally {
                btn.disabled = false;
                btn.innerHTML = rerunText;
            }
        }

        function renderResult(data) {
            const content = document.getElementById('resultContent');
            if (!data.success) {
                content.innerHTML = `<p class="error">${testFailedText}: ${escapeHtml(data.message || 'Unknown error')}</p>`;
                return;
            }

            let html = '<div class="steps">';
            
            data.steps.forEach((step, index) => {
                html += `
                    <div class="step">
                        <div class="step-icon">${index + 1}</div>
                        <div class="step-content">
                            <div class="step-title">${escapeHtml(step.title)}</div>
                            <div class="step-detail">${escapeHtml(step.detail).replace(/\n/g, '<br>')}</div>
                        </div>
                    </div>
                `;
            });

            html += '</div>';

            if (data.rewards) {
                html += '<h3 style="margin-top: 24px; margin-bottom: 16px;">' + rewardSummaryText + '</h3>';
                html += '<div class="reward-summary">';
                data.rewards.forEach(r => {
                    html += `
                        <div class="reward-card">
                            <h4>${escapeHtml(r.user)}</h4>
                            <div class="amount">${escapeHtml(r.amount)} IRT</div>
                        </div>
                    `;
                });
                html += '</div>';
            }

            if (data.raw) {
                html += '<h3 style="margin-top: 24px; margin-bottom: 16px;">' + rawLogsText + '</h3>';
                html += `<pre>${escapeHtml(data.raw)}</pre>`;
            }

            content.innerHTML = html;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        loadStatus();
    </script>
</body>
</html>
