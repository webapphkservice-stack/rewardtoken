@php
    $config = getSiteConfig();
    $langCode = currentLanguage();
@endphp
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(str_replace('_', '-', $langCode)) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('reward_info_title') ?> - <?= htmlspecialchars(t('site_name_full')) ?></title>
    <link rel="stylesheet" href="/css/app.css?v=20261024logo">
    <script src="/js/app.js?v=6" defer></script>
</head>
<body>
    <div class="container">
        @include('partials.topbar')
        @include('partials.dock', ['active' => 'rewards'])

        <div class="header">
            <h1>💰 <?= t('reward_info_title') ?></h1>
            <p><?= t('reward_info_desc') ?></p>
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
    </div>

    <script>
        const langCode = '<?= $langCode ?>';
        const loginAddress = '<?= htmlspecialchars((string) ($loginAddress ?? '')) ?>';
        const levelText = '<?= t('level_badge') ?>';

        function changeLanguage(lang) {
            const url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        }

        async function loadRewards() {
            try {
                const res = await fetch('/api/rewards?address=' + encodeURIComponent(loginAddress || ''));
                const data = await res.json();

                if (data.success) {
                    renderRewards(data.data);
                } else {
                    document.getElementById('tableContainer').innerHTML = '<p class="empty-state">' + (data.message || '<?= t('loading') ?>') + '</p>';
                }
            } catch (err) {
                document.getElementById('tableContainer').innerHTML = '<p class="empty-state"><?= t('loading') ?>: ' + err.message + '</p>';
            }
        }

        function renderRewards(rewards) {
            const totalCount = rewards.length;
            const totalAmount = rewards.reduce((sum, r) => sum + r.amount, 0);
            const level1Amount = rewards.filter(r => r.level === 1).reduce((sum, r) => sum + r.amount, 0);
            const level2Amount = rewards.filter(r => r.level === 2).reduce((sum, r) => sum + r.amount, 0);
            const level3Amount = rewards.filter(r => r.level === 3).reduce((sum, r) => sum + r.amount, 0);

            document.getElementById('totalCount').textContent = totalCount;
            document.getElementById('totalAmount').textContent = totalAmount.toFixed(2) + ' IRT';
            document.getElementById('level1Amount').textContent = level1Amount.toFixed(2) + ' IRT';
            document.getElementById('level2Amount').textContent = level2Amount.toFixed(2) + ' IRT';
            document.getElementById('level3Amount').textContent = level3Amount.toFixed(2) + ' IRT';

            if (rewards.length === 0) {
                document.getElementById('tableContainer').innerHTML = '<p class="empty-state"><?= t('no_records') ?></p>';
                return;
            }

            let html = '<div class="table-wrapper"><table><thead><tr><th><?= t('block') ?></th><th><?= t('inviter') ?></th><th><?= t('reward_level') ?></th><th><?= t('reward_amount') ?></th><th><?= t('tx_hash') ?></th></tr></thead><tbody>';
            rewards.forEach(r => {
                const levelClass = 'level-' + r.level;
                const levelLabel = levelText.replace('{level}', r.level);
                html += '<tr>';
                html += '<td>#' + r.block_number + '</td>';
                html += '<td class="address">' + r.inviter + '</td>';
                html += '<td><span class="level-badge ' + levelClass + '">' + levelLabel + '</span></td>';
                html += '<td><strong>' + r.amount.toFixed(2) + ' IRT</strong></td>';
                html += '<td class="address">' + r.tx_hash + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table></div>';

            document.getElementById('tableContainer').innerHTML = html;
        }

        loadRewards();
    </script>
</body>
</html>
