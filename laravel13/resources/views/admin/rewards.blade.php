@php
    $config = getSiteConfig();
    $langCode = currentLanguage();
@endphp
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(str_replace('_', '-', $langCode)) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('reward_records') ?> - <?= htmlspecialchars($config['site_name'] ?? t('site_name')) ?></title>
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
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .summary-item {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #41888A;
        }
        .summary-item label {
            display: block;
            font-size: 0.85rem;
            color: #718096;
            margin-bottom: 8px;
        }
        .summary-item .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #4a5568;
        }
        td {
            color: #2d3748;
        }
        .address {
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 0.85rem;
            word-break: break-all;
        }
        .level-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .level-1 { background: #dbeafe; color: #1e40af; }
        .level-2 { background: #fef3c7; color: #92400e; }
        .level-3 { background: #d1fae5; color: #065f46; }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #718096;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #41888A 0%, #2a6b6d 100%);
            color: #fff;
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
            <h1>💰 <?= t('reward_records') ?></h1>
            <p><?= t('reward_records_desc') ?></p>
        </div>

        <div class="card">
            <h2><?= t('statistics') ?></h2>
            <div class="summary-grid">
                <div class="summary-item">
                    <label><?= t('total_rewards') ?></label>
                    <div class="value" id="totalCount"><?= t('loading') ?></div>
                </div>
                <div class="summary-item">
                    <label><?= t('total_reward_amount') ?></label>
                    <div class="value" id="totalAmount"><?= t('loading') ?></div>
                </div>
                <div class="summary-item">
                    <label><?= t('level1_rewards') ?></label>
                    <div class="value" id="level1Amount"><?= t('loading') ?></div>
                </div>
                <div class="summary-item">
                    <label><?= t('level2_rewards') ?></label>
                    <div class="value" id="level2Amount"><?= t('loading') ?></div>
                </div>
                <div class="summary-item">
                    <label><?= t('level3_rewards') ?></label>
                    <div class="value" id="level3Amount"><?= t('loading') ?></div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2><?= t('reward_details') ?></h2>
            <div id="tableContainer">
                <p style="text-align: center; color: #718096; padding: 40px;"><?= t('loading') ?></p>
            </div>
        </div>
    </main>

    <script>
        const noRecordsText = '<?= t('no_records') ?>';
        const loadingFailedText = '<?= t('loading_failed') ?>';
        const recordsCountSuffix = '<?= t('records_count_suffix') ?>';
        const timeBlockText = '<?= t('time_block') ?>';
        const inviterText = '<?= t('inviter') ?>';
        const rewardLevelText = '<?= t('reward_level') ?>';
        const rewardAmountText = '<?= t('reward_amount') ?>';
        const txHashText = '<?= t('tx_hash') ?>';
        const levelBadgeText = '<?= t('level_badge') ?>';

        async function loadRewards() {
            try {
                const res = await fetch('/api/rewards');
                const data = await res.json();

                if (data.success) {
                    renderRewards(data.data);
                } else {
                    document.getElementById('tableContainer').innerHTML = '<p class="empty-state">' + (data.message || loadingFailedText) + '</p>';
                }
            } catch (err) {
                document.getElementById('tableContainer').innerHTML = '<p class="empty-state">' + loadingFailedText + ': ' + err.message + '</p>';
            }
        }

        function renderRewards(rewards) {
            const totalCount = rewards.length;
            const totalAmount = rewards.reduce((sum, r) => sum + r.amount, 0);
            const level1Amount = rewards.filter(r => r.level === 1).reduce((sum, r) => sum + r.amount, 0);
            const level2Amount = rewards.filter(r => r.level === 2).reduce((sum, r) => sum + r.amount, 0);
            const level3Amount = rewards.filter(r => r.level === 3).reduce((sum, r) => sum + r.amount, 0);

            document.getElementById('totalCount').textContent = totalCount + ' ' + recordsCountSuffix;
            document.getElementById('totalAmount').textContent = totalAmount.toFixed(2) + ' IRT';
            document.getElementById('level1Amount').textContent = level1Amount.toFixed(2) + ' IRT';
            document.getElementById('level2Amount').textContent = level2Amount.toFixed(2) + ' IRT';
            document.getElementById('level3Amount').textContent = level3Amount.toFixed(2) + ' IRT';

            if (rewards.length === 0) {
                document.getElementById('tableContainer').innerHTML = '<p class="empty-state">' + noRecordsText + '</p>';
                return;
            }

            let html = '<table><thead><tr><th>' + timeBlockText + '</th><th>' + inviterText + '</th><th>' + rewardLevelText + '</th><th>' + rewardAmountText + '</th><th>' + txHashText + '</th></tr></thead><tbody>';
            rewards.forEach(r => {
                const levelClass = 'level-' + r.level;
                const levelText = levelBadgeText.replace('{level}', r.level);
                html += '<tr>';
                html += '<td>#' + r.block_number + '</td>';
                html += '<td class="address">' + r.inviter + '</td>';
                html += '<td><span class="level-badge ' + levelClass + '">' + levelText + '</span></td>';
                html += '<td><strong>' + r.amount.toFixed(2) + ' IRT</strong></td>';
                html += '<td class="address">' + r.tx_hash + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';

            document.getElementById('tableContainer').innerHTML = html;
        }

        loadRewards();
    </script>
</body>
</html>
