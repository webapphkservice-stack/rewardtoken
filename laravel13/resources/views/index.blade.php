@php
    $config = getSiteConfig();
    $langCode = currentLanguage();

    $contractAddress = readContractAddress() ?? t('not_connected');

    $admin = '-';
    try {
        $rpc = \App\Services\RpcService::getInstance();
        $accounts = $rpc->call('eth_accounts');
        if (!empty($accounts[0])) {
            $admin = $accounts[0];
        }
    } catch (Exception $e) {
        $admin = t('not_connected');
    }
@endphp
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(str_replace('_', '-', $langCode)) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('site_name_full')) ?> - <?= t('demo') ?></title>
    <link rel="stylesheet" href="/css/app.css?v=20260918login">
    <script src="/js/app.js?v=5" defer></script>
</head>
<body>
    <div class="container">
        @include('partials.dock', ['active' => 'home'])

        <div class="header">
            <img class="site-logo" src="/images/logo.png" alt="<?= htmlspecialchars(t('site_name_full')) ?>">
            <h1><?= htmlspecialchars(t('site_name_full')) ?></h1>
        </div>

        <div class="card">
            <h2><?= t('contract_info') ?></h2>
            <div class="info-grid">
                <div class="info-item">
                    <label><?= t('contract_address') ?></label>
                    <code><?= htmlspecialchars($contractAddress) ?></code>
                </div>
                <div class="info-item">
                    <label><?= t('initial_account') ?></label>
                    <code><?= htmlspecialchars($admin) ?></code>
                </div>
                <div class="info-item">
                    <label><?= t('token_name') ?></label>
                    <code id="tokenName"><?= t('loading') ?></code>
                </div>
                <div class="info-item">
                    <label><?= t('total_supply') ?></label>
                    <code id="tokenSupply"><?= t('loading') ?></code>
                </div>
                <div class="info-item">
                    <label><?= t('registered_wallets') ?></label>
                    <code id="walletCount"><?= t('loading') ?></code>
                </div>
            </div>
        </div>

        <div class="card">
            <h2><?= t('referral_rewards') ?></h2>
            <div class="info-grid">
                <div class="info-item">
                    <label><?= t('level1_reward') ?></label>
                    <code id="rewardLevel1"><?= t('loading') ?></code>
                </div>
                <div class="info-item">
                    <label><?= t('level2_reward') ?></label>
                    <code id="rewardLevel2"><?= t('loading') ?></code>
                </div>
                <div class="info-item">
                    <label><?= t('level3_reward') ?></label>
                    <code id="rewardLevel3"><?= t('loading') ?></code>
                </div>
                <div class="info-item">
                    <label><?= t('total_invite_reward') ?></label>
                    <code id="rewardTotal"><?= t('loading') ?></code>
                </div>
            </div>
        </div>

        <div class="card">
            <h2><?= t('latest_block_info') ?></h2>
            <div class="info-grid">
                <div class="info-item">
                    <label><?= t('block_height') ?></label>
                    <code id="blockNumber"><?= t('loading') ?></code>
                </div>
                <div class="info-item">
                    <label><?= t('block_hash') ?></label>
                    <code id="blockHash"><?= t('loading') ?></code>
                </div>
                <div class="info-item">
                    <label><?= t('block_time') ?></label>
                    <code id="blockTime"><?= t('loading') ?></code>
                </div>
                <div class="info-item">
                    <label><?= t('transactions') ?></label>
                    <code id="blockTxs"><?= t('loading') ?></code>
                </div>
                <div class="info-item">
                    <label><?= t('gas_used_limit') ?></label>
                    <code id="blockGas"><?= t('loading') ?></code>
                </div>
                <div class="info-item">
                    <label><?= t('block_size') ?></label>
                    <code id="blockSize"><?= t('loading') ?></code>
                </div>
            </div>
        </div>

    </div>

    <script>
        const langCode = '<?= $langCode ?>';
        const localeMap = {
            'zh-cn': 'zh-CN',
            'zh-tw': 'zh-TW',
            'en': 'en-US',
            'ja': 'ja-JP',
            'ko': 'ko-KR',
            'th': 'th-TH',
            'ms': 'ms-MY',
            'vi': 'vi-VN'
        };
        const dateLocale = localeMap[langCode] || 'zh-CN';

        function changeLanguage(lang) {
            const url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        }

        async function loadContractInfo() {
            try {
                const res = await fetch('/api/status');
                const data = await res.json();
                if (data.success) {
                    document.getElementById('tokenName').textContent = '<?= addslashes(t('site_name')) ?> (' + data.symbol + ')';
                    document.getElementById('tokenSupply').textContent = data.total_supply.toLocaleString() + ' ' + data.symbol;
                    document.getElementById('walletCount').textContent = (data.wallet_count ?? 0) + ' <?= t('registered_wallets') ?>';

                    const inviteReward = data.invite_reward;
                    const levels = data.referral_levels;
                    document.getElementById('rewardTotal').textContent = inviteReward.toLocaleString() + ' ' + data.symbol;
                    document.getElementById('rewardLevel1').textContent = (inviteReward * levels[0] / 100).toLocaleString() + ' ' + data.symbol + ' (' + levels[0] + '%)';
                    document.getElementById('rewardLevel2').textContent = (inviteReward * levels[1] / 100).toLocaleString() + ' ' + data.symbol + ' (' + levels[1] + '%)';
                    document.getElementById('rewardLevel3').textContent = (inviteReward * levels[2] / 100).toLocaleString() + ' ' + data.symbol + ' (' + levels[2] + '%)';

                    if (data.block && data.block.number !== null) {
                        document.getElementById('blockNumber').textContent = '#' + data.block.number.toLocaleString();
                        document.getElementById('blockHash').textContent = data.block.hash;
                        document.getElementById('blockTime').textContent = new Date(data.block.timestamp * 1000).toLocaleString(dateLocale);
                        document.getElementById('blockTxs').textContent = data.block.transactions;
                        document.getElementById('blockGas').textContent = data.block.gas_used.toLocaleString() + ' / ' + data.block.gas_limit.toLocaleString();
                        document.getElementById('blockSize').textContent = data.block.size.toLocaleString() + ' bytes';
                    } else {
                        document.getElementById('blockNumber').textContent = '<?= t('loading') ?>';
                    }
                }
            } catch (err) {
                console.error('Load contract info failed:', err);
            }
        }

        loadContractInfo();
    </script>
</body>
</html>
