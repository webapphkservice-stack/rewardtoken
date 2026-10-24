@php
    $config = getSiteConfig();
    $langCode = currentLanguage();
    $transferFee = is_numeric($config['transfer_fee'] ?? null) ? (float) $config['transfer_fee'] : 0;
@endphp
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(str_replace('_', '-', $langCode)) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('send_receive_title') ?> - <?= htmlspecialchars(t('site_name')) ?></title>
    <link rel="stylesheet" href="/css/app.css?v=20261024topbar">
    <script src="/js/app.js?v=6" defer></script>
</head>
<body>
    <div class="container">
        @include('partials.topbar')
        @include('partials.dock', ['active' => 'transfer'])

        <div class="card">
            <div class="header">
                <h1><?= t('send_receive_title') ?></h1>
                <p><?= t('send_receive_desc') ?></p>
            </div>

            <div id="alert" class="alert"></div>

            <div id="walletInfo" class="wallet-info" style="display: none;">
                <div class="wallet-info-grid">
                    <div>
                        <label><?= t('current_wallet') ?></label>
                        <div class="value" id="walletAddress"><?= t('not_connected') ?></div>
                    </div>
                    <div>
                        <label><?= t('available_balance') ?></label>
                        <div class="value" id="walletBalance"><?= t('loading') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="tab-nav">
                <button class="tab-btn active" data-tab="send" onclick="switchTab('send')"><?= t('send') ?></button>
                <button class="tab-btn" data-tab="receive" onclick="switchTab('receive')"><?= t('receive') ?></button>
            </div>

            <div id="tab-send" class="tab-panel active">
                <h2><?= t('send') ?></h2>
                <form id="gethSendForm">
                    <div class="form-group">
                        <label><?= t('account_password') ?></label>
                        <input type="password" id="gethSendPassword" placeholder="<?= t('account_password') ?>" required>
                    </div>
                    <div class="form-group">
                        <label><?= t('recipient_address') ?></label>
                        <input type="text" id="gethRecipient" placeholder="0x..." required>
                    </div>
                    <div class="form-group">
                        <label><?= t('amount') ?></label>
                        <input type="number" id="gethAmount" step="0.000000000000000001" min="0" placeholder="0.00" required>
                        <p class="hint">IRT</p>
                    </div>
                    <div class="transfer-fee-tip">
                        <?php if ($transferFee > 0): ?>
                        <?= t('transfer_fee_display', ['fee' => $transferFee]) ?>
                        <?php else: ?>
                        <?= t('transfer_free') ?>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary" id="gethSendBtn"><?= t('send_tokens') ?></button>
                </form>
            </div>

            <div id="tab-receive" class="tab-panel">
                <h2><?= t('receive') ?></h2>
                <div class="wallet-info">
                    <label><?= t('your_address') ?></label>
                    <div class="value" id="gethReceiveAddress"><?= t('no_accounts') ?></div>
                    <button class="btn btn-secondary btn-sm" onclick="copyGethAddress()"><?= t('copy_address') ?></button>
                    <div id="gethCopyFeedback" class="copy-feedback"><?= t('address_copied') ?></div>
                </div>
                <div class="qr-wrap">
                    <canvas id="gethQrCanvas"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script>
        const langCode = '<?= $langCode ?>';

        function changeLanguage(lang) {
            const url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        }

        const loginAddress = '<?= $loginAddress ?? '' ?>';
        let currentAddress = loginAddress || '';

        function initAccount() {
            const address = currentAddress;
            if (!address) {
                document.getElementById('walletInfo').style.display = 'none';
                document.getElementById('gethReceiveAddress').textContent = '<?= t('no_accounts') ?>';
                renderGethQR('<?= t('no_accounts') ?>');
                return;
            }
            document.getElementById('walletAddress').textContent = address;
            document.getElementById('gethReceiveAddress').textContent = address;
            document.getElementById('walletInfo').style.display = 'block';
            renderGethQR(address);
            updateGethBalance(address);
        }

        async function updateGethBalance(address) {
            if (!address) {
                document.getElementById('walletBalance').textContent = '-';
                return;
            }
            document.getElementById('walletBalance').textContent = '<?= t('loading') ?>';
            try {
                const res = await fetch('/api/geth-wallet?action=balance&address=' + encodeURIComponent(address));
                const data = await res.json();
                if (data.success) {
                    const bal = parseFloat(data.balance || '0');
                    const formatted = bal.toLocaleString(langCode, { maximumFractionDigits: 6 }) + ' IRT';
                    document.getElementById('walletBalance').textContent = formatted;
                } else {
                    document.getElementById('walletBalance').textContent = '<?= t('loading_failed') ?>';
                }
            } catch (err) {
                console.error('Update Geth balance failed:', err);
                document.getElementById('walletBalance').textContent = '<?= t('loading_failed') ?>';
            }
        }

        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.tab === tab);
            });
            document.querySelectorAll('.tab-panel').forEach(panel => {
                panel.classList.toggle('active', panel.id === 'tab-' + tab);
            });
        }

        function copyGethAddress() {
            const address = document.getElementById('gethReceiveAddress').textContent;
            if (!address || address === '<?= t('no_accounts') ?>') return;
            navigator.clipboard.writeText(address).then(() => {
                const feedback = document.getElementById('gethCopyFeedback');
                feedback.style.display = 'block';
                setTimeout(() => { feedback.style.display = 'none'; }, 2000);
            });
        }

        function renderGethQR(address) {
            const canvas = document.getElementById('gethQrCanvas');
            QRCode.toCanvas(canvas, address, { width: 200, margin: 2 });
        }

        document.getElementById('gethSendForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('gethSendBtn');

            if (!currentAddress) {
                showAlert('<?= t('login_required') ?>', 'error');
                return;
            }

            const password = document.getElementById('gethSendPassword').value;
            const recipient = document.getElementById('gethRecipient').value.trim();
            const amount = document.getElementById('gethAmount').value;

            if (!password) {
                showAlert('<?= t('password_required') ?>', 'error');
                return;
            }
            if (!recipient) {
                showAlert('<?= t('enter_recipient') ?>', 'error');
                return;
            }
            if (!amount || parseFloat(amount) <= 0) {
                showAlert('<?= t('invalid_amount') ?>', 'error');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="loading"></span> <?= t('sending') ?>';

            try {
                const res = await fetch('/api/geth-wallet?action=send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'from=' + encodeURIComponent(currentAddress) +
                          '&password=' + encodeURIComponent(password) +
                          '&to=' + encodeURIComponent(recipient) +
                          '&amount=' + encodeURIComponent(amount)
                });
                const data = await res.json();
                if (data.success) {
                    showAlert('<?= t('send_success') ?> ' + (data.tx_hash || ''), 'success');
                    document.getElementById('gethRecipient').value = '';
                    document.getElementById('gethAmount').value = '';
                    document.getElementById('gethSendPassword').value = '';
                    await updateGethBalance(currentAddress);
                } else {
                    showAlert('<?= t('send_failed') ?>: ' + (data.message || ''), 'error');
                }
            } catch (err) {
                showAlert('<?= t('send_failed') ?>: ' + err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<?= t('send_tokens') ?>';
            }
        });

        function showAlert(message, type) {
            const alert = document.getElementById('alert');
            alert.className = 'alert alert-' + type;
            alert.textContent = message;
            alert.style.display = 'block';
        }

        initAccount();
    </script>
</body>
</html>
