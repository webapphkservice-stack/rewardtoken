@php
    $config = getSiteConfig();
    $langCode = currentLanguage();
@endphp
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(str_replace('_', '-', $langCode)) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('wallet_registration_title') ?> - <?= htmlspecialchars(t('site_name')) ?></title>
    <link rel="stylesheet" href="/css/app.css?v=20260918share">
    <script src="/js/app.js?v=5" defer></script>
</head>
<body>
    <div class="container">
        @include('partials.dock', ['active' => 'wallet'])

        <div class="card">
            <div class="header">
                <h1><?= t('wallet_registration_title') ?></h1>
                <p><?= t('wallet_registration_desc') ?></p>
            </div>

            <div id="alert" class="alert"></div>

            <div class="geth-section">
                <h3><?= t('current_wallet') ?></h3>
                <div class="wallet-key-box">
                    <div class="form-group">
                        <label><?= t('account_address') ?></label>
                        <code class="pk-value" id="walletAddress"><?= t('loading') ?></code>
                    </div>
                    <div class="form-group" id="pkRow">
                        <label><?= t('your_private_key') ?> <span class="pk-once"><?= t('private_key_once') ?></span></label>
                        <textarea id="pkValue" class="pk-textarea" rows="2" readonly></textarea>
                    </div>
                    <button class="btn btn-secondary btn-sm" id="copyPkBtn" style="display:none;" onclick="copyPrivateKey()"><?= t('copy_private_key') ?></button>
                    <p class="private-key-warning" id="pkHint"><?= t('save_private_key_hint') ?></p>
                </div>
            </div>

            <form id="registerForm">
                <div class="form-group">
                    <label><?= t('username') ?><span class="optional">*</span></label>
                    <input type="text" id="gethUsername" autocomplete="off" placeholder="<?= t('username') ?>" required>
                </div>
                <div class="form-group">
                    <label><?= t('account_password') ?><span class="optional">*</span></label>
                    <input type="password" id="gethRegisterPassword" placeholder="<?= t('account_password') ?>" required>
                    <p class="hint"><?= t('account_password_hint') ?></p>
                </div>
                <div class="form-group">
                    <label><?= t('inviter_address') ?> <span class="optional"><?= t('optional') ?></span></label>
                    <input type="text" name="inviter" id="inviter" placeholder="0x..." value="">
                    <p class="hint"><?= t('inviter_hint') ?></p>
                </div>
                <button type="submit" class="btn btn-primary" id="registerBtn">
                    <?= t('register_now') ?>
                </button>
            </form>
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

        let currentAddress = '';
        let registered = false;

        // 读取分享链接 ?ref= 自动填入邀请人地址
        (function fillInviterFromRef() {
            const params = new URLSearchParams(window.location.search);
            const ref = params.get('ref');
            if (ref && /^0x[a-fA-F0-9]{40}$/.test(ref)) {
                document.getElementById('inviter').value = ref;
                const hint = document.querySelector('#registerForm .form-group:last-child .hint');
                if (hint) hint.textContent = '<?= t('inviter_from_link') ?>';
            }
        })();

        async function initWallet() {
            let saved = localStorage.getItem('wallet_reg_addr');
            if (saved === 'undefined' || saved === 'null') saved = null;
            if (saved) {
                // 已有钱包：仅显示地址，私钥只显示一次、不再出现
                currentAddress = saved;
                document.getElementById('walletAddress').textContent = saved;
                document.getElementById('pkRow').style.display = 'none';
                document.getElementById('pkHint').style.display = 'none';
                return;
            }
            showAlert('<?= t('wallet_generating') ?>', 'info');
            const ctrl = new AbortController();
            const timer = setTimeout(() => ctrl.abort(), 20000);
            try {
                const res = await fetch('/api/geth-wallet?action=ensure_wallet', { method: 'POST', signal: ctrl.signal });
                clearTimeout(timer);
                const data = await res.json();
                if (data.success) {
                    const d = data.data || data;
                    currentAddress = d.address;
                    localStorage.setItem('wallet_reg_addr', d.address);
                    document.getElementById('walletAddress').textContent = d.address;
                    if (d.private_key) {
                        document.getElementById('pkValue').value = d.private_key;
                        document.getElementById('copyPkBtn').style.display = '';
                        showAlert('<?= t('save_private_key_title') ?>', 'success');
                    } else {
                        document.getElementById('pkRow').style.display = 'none';
                    }
                } else {
                    showAlert(data.message || '<?= t('action_failed') ?>', 'error');
                }
            } catch (err) {
                clearTimeout(timer);
                showAlert('<?= t('action_failed') ?>: ' + err.message, 'error');
            }
        }
        initWallet();

        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('registerBtn');
            const passwordInput = document.getElementById('gethRegisterPassword');
            const password = passwordInput.value;
            const username = document.getElementById('gethUsername').value.trim();

            if (!currentAddress) {
                showAlert('<?= t('wallet_generating') ?>', 'error');
                return;
            }

            if (!password) {
                showAlert('<?= t('password_required') ?>', 'error');
                return;
            }

            if (!username) {
                showAlert('<?= t('username_required') ?>', 'error');
                return;
            }

            const inviter = document.getElementById('inviter').value.trim() || '0x0000000000000000000000000000000000000000';

            btn.disabled = true;
            btn.innerHTML = '<span class="loading"></span> <?= t('registering') ?>';

            try {
                const res = await fetch('/api/geth-wallet?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'from=' + encodeURIComponent(currentAddress) +
                          '&password=' + encodeURIComponent(password) +
                          '&username=' + encodeURIComponent(username) +
                          '&inviter=' + encodeURIComponent(inviter)
                });
                const data = await res.json();
                if (data.success) {
                    // 注册成功后自动登录，便于复制私钥后进入“我的”
                    try {
                        await fetch('/auth/login', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'username=' + encodeURIComponent(username) + '&password=' + encodeURIComponent(password)
                        });
                    } catch (e) {}
                    registered = true;
                    showAlert('<?= t('register_success') ?> ' + '<?= t('saved_go_my') ?>', 'success');
                    passwordInput.value = '';
                } else {
                    showAlert(data.message || '<?= t('register_failed') ?>', 'error');
                }
            } catch (err) {
                showAlert('<?= t('register_failed') ?>: ' + err.message, 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<?= t('register_now') ?>';
            }
        });

        function showAlert(message, type) {
            const alert = document.getElementById('alert');
            alert.className = 'alert alert-' + type;
            alert.textContent = message;
            alert.style.display = 'block';
        }

        function copyPrivateKey() {
            const key = document.getElementById('pkValue').value;
            if (!key) return;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(key).then(() => {
                    showAlert('<?= t('private_key_copied') ?>', 'success');
                    if (registered) setTimeout(function () { window.location.href = '/my'; }, 600);
                });
            } else {
                document.getElementById('pkValue').select();
                document.execCommand('copy');
                showAlert('<?= t('private_key_copied') ?>', 'success');
                if (registered) setTimeout(function () { window.location.href = '/my'; }, 600);
            }
        }

    </script>
</body>
</html>
