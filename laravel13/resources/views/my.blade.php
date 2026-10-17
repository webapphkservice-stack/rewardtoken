@php
    $config = getSiteConfig();
    $langCode = currentLanguage();
@endphp
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(str_replace('_', '-', $langCode)) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('my_title') ?> - <?= htmlspecialchars(t('site_name_full')) ?></title>
    <link rel="stylesheet" href="/css/app.css?v=20260918share">
    <script src="/js/app.js?v=5" defer></script>
</head>
<body>
    <div class="container">
        @include('partials.dock', ['active' => 'my'])

        <div class="header">
            <h1><?= t('my_title') ?></h1>
            <p><?= t('my_desc') ?></p>
        </div>

        <div class="card">
            <h2><?= t('my_account') ?></h2>
            <div class="form-group username-row" id="usernameRow" style="display: none;">
                <label><?= t('username') ?></label>
                <code id="accountUsername"><?= htmlspecialchars($loginUsername ?? '') ?></code>
            </div>
            <div class="form-group">
                <label><?= t('select_account') ?></label>
                <select id="gethAccountSelect" class="account-select" onchange="onAccountChange()"></select>
                <div id="gethNoAccounts" class="no-accounts" style="display: none;"><?= t('no_accounts') ?></div>
            </div>

            <div id="accountInfo" style="display: none;">
                <div class="info-grid">
                    <div class="info-item">
                        <label><?= t('current_wallet') ?></label>
                        <code id="walletAddress">-</code>
                    </div>
                    <div class="info-item">
                        <label><?= t('available_balance') ?></label>
                        <code id="walletBalance"><?= t('loading') ?></code>
                    </div>
                    <div class="info-item">
                        <label><?= t('register_status') ?></label>
                        <code id="registerStatus">-</code>
                    </div>
                    <div class="info-item">
                        <label><?= t('total_rewards_earned') ?></label>
                        <code id="totalRewards">-</code>
                    </div>
                    <div class="info-item">
                        <label><?= t('invite_count') ?></label>
                        <code id="inviteCount">-</code>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2><?= t('invite_share') ?></h2>
            <div class="share-section" id="shareSection" style="display:none;">
                <div class="form-group">
                    <label><?= t('share_link') ?></label>
                    <div class="share-link-box">
                        <code class="share-link-text" id="shareLinkText">-</code>
                        <button type="button" class="btn btn-secondary btn-sm" id="copyShareBtn" onclick="copyShareLink()"><?= t('copy_link') ?></button>
                    </div>
                    <p class="hint"><?= t('share_link_hint') ?></p>
                </div>
                <div class="info-grid share-chain">
                    <div class="info-item"><label><?= t('direct_inviter') ?></label><code id="shareDirectInviter">-</code></div>
                    <div class="info-item"><label><?= t('l1_inviter') ?></label><code id="shareL1Inviter">-</code></div>
                    <div class="info-item"><label><?= t('l2_inviter') ?></label><code id="shareL2Inviter">-</code></div>
                    <div class="info-item"><label><?= t('l3_inviter') ?></label><code id="shareL3Inviter">-</code></div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2><?= t('invite_relationship') ?></h2>
            <div id="inviteInfo">
                <p class="empty-state"><?= t('select_account_first') ?></p>
            </div>
        </div>

        <div class="card">
            <h2><?= t('my_orders') ?></h2>
            <?php if (count($orders ?? []) === 0): ?>
                <p class="empty-state"><?= t('no_records') ?></p>
            <?php else: ?>
                <div class="table-wrapper"><table>
                    <thead><tr>
                        <th><?= t('order_id') ?></th>
                        <th><?= t('order_qty') ?></th>
                        <th><?= t('order_amount') ?></th>
                        <th><?= t('order_status') ?></th>
                        <th><?= t('order_time') ?></th>
                    </tr></thead>
                    <tbody>
                    <?php $statusMap = [
                        'pending' => 'order_status_pending',
                        'completed' => 'order_status_completed',
                        'closed' => 'order_status_closed',
                        'transfer_failed' => 'order_status_transfer_failed',
                        'failed' => 'order_status_failed',
                    ]; ?>
                    <?php foreach ($orders as $o): ?>
                        <?php
                            $stKey = $statusMap[$o['status'] ?? ''] ?? 'order_status_failed';
                            $created = ($o['created_at'] ?? 0) ? date('Y-m-d H:i:s', (int) $o['created_at']) : '-';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($o['order_id'] ?? '-') ?></td>
                            <td><?= htmlspecialchars((string) ($o['qty'] ?? '-')) ?></td>
                            <td>
                                <?= number_format((float) ($o['total_usdt'] ?? 0), 2) ?> USDT
                                (<?= number_format((int) (($o['token_amount'] ?? 0) + ($o['bonus_amount'] ?? 0))) ?> IRT)
                            </td>
                            <td><span class="level-badge level-2"><?= t($stKey) ?></span></td>
                            <td><?= htmlspecialchars($created) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2><?= t('transaction_records') ?></h2>
            <div id="txContainer">
                <p class="empty-state"><?= t('select_account_first') ?></p>
            </div>
        </div>

        <div class="logout-section">
            <button type="button" class="btn-logout" id="logoutBtn">
                <img class="dock-icon" src="/images/logout.png" alt="<?= htmlspecialchars(t('logout')) ?>">
                <span><?= t('logout') ?></span>
            </button>
        </div>
    </div>

    <script>
        const langCode = '<?= $langCode ?>';
        const loginAddress = '<?= $loginAddress ?? '' ?>';
        const loginUsername = '<?= $loginUsername ?? '' ?>';
        // 未登录直接访问"我的"时，回到首页（登录需经底部"我的"图标弹层）
        if (!loginAddress) { window.location.href = '/'; }
        const L = {
            registered: '<?= t('registered') ?>',
            not_registered: '<?= t('not_registered') ?>',
            direct_inviter: '<?= t('direct_inviter') ?>',
            none: '<?= t('none') ?>',
            l1_inviter: '<?= t('l1_inviter') ?>',
            l2_inviter: '<?= t('l2_inviter') ?>',
            l3_inviter: '<?= t('l3_inviter') ?>',
            no_records: '<?= t('no_records') ?>',
            loading_failed: '<?= t('loading_failed') ?>',
            tx_transfer: '<?= t('tx_transfer') ?>',
            tx_receive: '<?= t('tx_receive') ?>',
            tx_register: '<?= t('tx_register') ?>',
            tx_reward: '<?= t('tx_reward') ?>',
            tx_time: '<?= t('tx_time') ?>',
            from: '<?= t('from') ?>',
            to: '<?= t('to') ?>',
            level: '<?= t('reward_level') ?>',
            block: '<?= t('block') ?>',
            inviter: '<?= t('inviter') ?>',
            amount: '<?= t('amount') ?>',
            tx_hash: '<?= t('tx_hash') ?>'
        };

        let accounts = [];
        let currentAddress = '';

        async function loadAccounts() {
            try {
                const res = await fetch('/api/geth-wallet?action=list');
                const data = await res.json();
                accounts = data.success ? (data.data || []) : [];
            } catch (err) {
                accounts = [];
                console.error('Load accounts failed:', err);
            }
            renderSelect();
        }

        function renderSelect() {
            const select = document.getElementById('gethAccountSelect');
            const noAccounts = document.getElementById('gethNoAccounts');
            select.innerHTML = '';
            if (accounts.length === 0) {
                noAccounts.style.display = 'block';
                document.getElementById('accountInfo').style.display = 'none';
                document.getElementById('inviteInfo').innerHTML = '<p class="empty-state">' + L.no_records + '</p>';
                document.getElementById('txContainer').innerHTML = '<p class="empty-state">' + L.no_records + '</p>';
                currentAddress = '';
                return;
            }
            noAccounts.style.display = 'none';
            // 已登录：仅展示登录账户，隐藏下拉（避免切换其它账户）
            if (loginAddress && accounts.indexOf(loginAddress) !== -1) {
                accounts = [loginAddress];
                select.style.display = 'none';
                if (loginUsername) {
                    document.getElementById('usernameRow').style.display = 'flex';
                    document.getElementById('accountUsername').textContent = loginUsername;
                }
                const emptyWrap = document.createElement('option');
                select.appendChild(emptyWrap);
                onAccountChange();
                return;
            }
            accounts.forEach(a => {
                const opt = document.createElement('option');
                opt.value = a;
                opt.textContent = a;
                select.appendChild(opt);
            });
            onAccountChange();
        }

        function onAccountChange() {
            const select = document.getElementById('gethAccountSelect');
            currentAddress = (loginAddress && accounts[0] === loginAddress) ? loginAddress : select.value;
            document.getElementById('accountInfo').style.display = 'block';
            document.getElementById('walletAddress').textContent = currentAddress;
            document.getElementById('walletBalance').textContent = '<?= t('loading') ?>';
            loadInfo();
            loadTxs();
        }

        async function loadInfo() {
            if (!currentAddress) return;
            try {
                const res = await fetch('/api/geth-wallet?action=info&address=' + encodeURIComponent(currentAddress));
                const data = await res.json();
                if (data.success) {
                    renderInfo(data.data);
                } else {
                    document.getElementById('walletBalance').textContent = data.message || L.loading_failed;
                }
            } catch (err) {
                document.getElementById('walletBalance').textContent = L.loading_failed;
                console.error('Load info failed:', err);
            }
        }

        function renderInfo(d) {
            document.getElementById('walletAddress').textContent = d.address;
            const bal = parseFloat(d.balance);
            document.getElementById('walletBalance').textContent = (isNaN(bal) ? d.balance : bal.toLocaleString(langCode, { maximumFractionDigits: 6 })) + ' IRT';
            document.getElementById('registerStatus').textContent = d.is_registered ? L.registered : L.not_registered;
            const tr = parseFloat(d.total_rewards);
            document.getElementById('totalRewards').textContent = (isNaN(tr) ? d.total_rewards : tr.toLocaleString(langCode, { maximumFractionDigits: 6 })) + ' IRT';
            (document.getElementById('inviteCount')).textContent = (d.invite_count ?? 0) + ' <?= t('people') ?>';

            // 分享链接
            const shareSec = document.getElementById('shareSection');
            if (d.address) {
                shareSec.style.display = '';
                document.getElementById('shareLinkText').textContent = window.location.origin + '/wallet?ref=' + d.address;
                document.getElementById('shareDirectInviter').textContent = d.inviter || L.none;
                document.getElementById('shareL1Inviter').textContent = (d.inviter_chain && d.inviter_chain[0]) ? d.inviter_chain[0] : L.none;
                document.getElementById('shareL2Inviter').textContent = (d.inviter_chain && d.inviter_chain[1]) ? d.inviter_chain[1] : L.none;
                document.getElementById('shareL3Inviter').textContent = (d.inviter_chain && d.inviter_chain[2]) ? d.inviter_chain[2] : L.none;
            } else {
                shareSec.style.display = 'none';
            }

            let html = '';
            html += '<div class="info-grid">';
            html += '<div class="info-item"><label>' + L.direct_inviter + '</label><code>' + (d.inviter ? d.inviter : L.none) + '</code></div>';
            html += '<div class="info-item"><label>' + L.l1_inviter + '</label><code>' + (d.inviter_chain && d.inviter_chain[0] ? d.inviter_chain[0] : L.none) + '</code></div>';
            html += '<div class="info-item"><label>' + L.l2_inviter + '</label><code>' + (d.inviter_chain && d.inviter_chain[1] ? d.inviter_chain[1] : L.none) + '</code></div>';
            html += '<div class="info-item"><label>' + L.l3_inviter + '</label><code>' + (d.inviter_chain && d.inviter_chain[2] ? d.inviter_chain[2] : L.none) + '</code></div>';
            html += '</div>';
            document.getElementById('inviteInfo').innerHTML = html;
        }

        function copyShareLink() {
            const text = document.getElementById('shareLinkText').textContent;
            if (!text || text === '-') return;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function () {
                    showToast('<?= t('copy_link_success') ?>');
                });
            } else {
                // fallback
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                showToast('<?= t('copy_link_success') ?>');
            }
        }

        function showToast(msg) {
            let t = document.getElementById('shareToast');
            if (!t) {
                t = document.createElement('div');
                t.id = 'shareToast';
                t.className = 'share-toast';
                document.body.appendChild(t);
            }
            t.textContent = msg;
            t.classList.add('show');
            clearTimeout(t._timer);
            t._timer = setTimeout(function () { t.classList.remove('show'); }, 2000);
        }

        async function loadTxs() {
            if (!currentAddress) return;
            try {
                const res = await fetch('/api/geth-wallet?action=txs&address=' + encodeURIComponent(currentAddress));
                const data = await res.json();
                if (data.success) {
                    renderTxs(data.data || []);
                } else {
                    document.getElementById('txContainer').innerHTML = '<p class="empty-state">' + (data.message || L.loading_failed) + '</p>';
                }
            } catch (err) {
                document.getElementById('txContainer').innerHTML = '<p class="empty-state">' + L.loading_failed + '</p>';
                console.error('Load txs failed:', err);
            }
        }

        function renderTxs(records) {
            if (records.length === 0) {
                document.getElementById('txContainer').innerHTML = '<p class="empty-state">' + L.no_records + '</p>';
                return;
            }
            let html = '<div class="table-wrapper"><table><thead><tr><th><?= t('tx_time') ?></th><th><?= t('transactions') ?></th>';
            html += '<th><?= t('tx_hash') ?></th></tr></thead><tbody>';
            records.forEach(r => {
                let detail = '';
                let badgeCls = '';
                if (r.type === 'transfer') {
                    const direction = r.to === currentAddress ? 'receive' : 'send';
                    badgeCls = direction === 'receive' ? 'level-1' : 'level-3';
                    const label = direction === 'receive' ? L.tx_receive : L.tx_transfer;
                    detail = '<span class="level-badge ' + badgeCls + '">' + label + '</span> ' + (parseFloat(r.value) || r.value) + ' IRT<br>'
                        + L.from + ': ' + r.from + '<br>' + L.to + ': ' + r.to;
                } else if (r.type === 'registered') {
                    badgeCls = 'level-2';
                    detail = '<span class="level-badge ' + badgeCls + '">' + L.tx_register + '</span><br>'
                        + L.inviter + ': ' + r.inviter;
                } else if (r.type === 'reward') {
                    const lvId = 'level-' + Math.min(r.level, 3);
                    detail = '<span class="level-badge ' + lvId + '">' + L.tx_reward + ' ' + L.level + ' ' + r.level + '</span> ' + (parseFloat(r.value) || r.value) + ' IRT';
                }
                html += '<tr><td>#' + r.block_number + '</td><td>' + detail + '</td><td class="address">' + r.tx_hash + '</td></tr>';
            });
            html += '</tbody></table></div>';
            document.getElementById('txContainer').innerHTML = html;
        }

        loadAccounts();

        // 退出登录：清除 session 登录态并返回首页
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                fetch('/auth/logout', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' } })
                    .then(function () {
                        try { localStorage.removeItem('wallet_reg_addr'); } catch (err) {}
                        window.location.href = '/';
                    })
                    .catch(function () {
                        window.location.href = '/';
                    });
            });
        }
    </script>
</body>
</html>