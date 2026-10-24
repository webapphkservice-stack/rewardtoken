@php
    $config = getSiteConfig();
    $langCode = currentLanguage();
    $plans = $plans ?? purchasePlans();
    $symbol = $config['symbol'] ?? 'IRT';
@endphp
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(str_replace('_', '-', $langCode)) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('token_purchase')) ?> - <?= htmlspecialchars($config['site_name_full'] ?? t('site_name_full')) ?></title>
    <link rel="stylesheet" href="/css/app.css?v=20261024logo">
    <script src="/js/app.js?v=6" defer></script>
    <style>
        .buy-container {
            max-width: 520px;
            margin: 0 auto;
            padding: 24px 16px 100px;
        }
        .buy-card {
            background: rgba(255,255,255,0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 28px 24px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06), inset 0 1px 0 rgba(255,255,255,0.7);
            margin-bottom: 16px;
        }
        .buy-card h2 {
            margin: 0 0 16px;
            font-size: 1.2rem;
            color: #285793;
            border-bottom: 2px solid;
            border-image: linear-gradient(90deg, #285793, transparent) 1;
            padding-bottom: 10px;
        }
        .plan-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        .plan-card {
            background: rgba(40,87,147,0.06);
            border: 2px solid rgba(40,87,147,0.12);
            border-radius: 12px;
            padding: 14px 10px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s, transform 0.15s, box-shadow 0.15s;
        }
        .plan-card:hover { transform: translateY(-2px); }
        .plan-card.selected {
            border-color: #285793;
            background: rgba(40,87,147,0.10);
            box-shadow: 0 4px 14px rgba(40,87,147,0.18);
        }
        .plan-card .price-big {
            font-size: 1.4rem;
            font-weight: 700;
            color: #285793;
        }
        .plan-card .price-desc {
            font-size: 0.85rem;
            color: #666;
            margin-top: 4px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #444;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }
        .form-group .hint {
            font-size: 0.8rem;
            color: #999;
            margin-top: 4px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            background: rgba(255,255,255,0.8);
            outline: none;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }
        .form-group input:focus {
            border-color: #285793;
        }
        .total-display {
            background: rgba(40,87,147,0.08);
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }
        .total-display .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0;
        }
        .total-display .total-label {
            color: #666;
            font-size: 0.9rem;
        }
        .total-display .total-value {
            font-weight: 700;
            color: #285793;
            font-size: 1.1rem;
        }
        .btn-buy {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, #285793, #1a3d6e);
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(40,87,147,0.3);
            transition: all 0.2s;
        }
        .btn-buy:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40,87,147,0.4);
        }
        .btn-buy:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .btn-buy .loading {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            display: none;
            font-size: 0.9rem;
        }
        .alert-success {
            background: rgba(209,250,229,0.9);
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background: rgba(254,226,226,0.9);
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .alert-info {
            background: rgba(219,234,254,0.9);
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .payment-modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            display: none;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .payment-modal-overlay.active {
            display: flex;
        }
        .payment-modal {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 32px 28px;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 12px 48px rgba(0,0,0,0.15);
            position: relative;
        }
        .payment-modal h3 {
            margin: 0 0 8px;
            color: #285793;
            font-size: 1.2rem;
        }
        .payment-modal .modal-desc {
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 20px;
        }
        .payment-modal .qrcode-box {
            width: 200px;
            height: 200px;
            margin: 0 auto 20px;
            background: #f8f8f8;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .payment-modal .qrcode-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .payment-modal .payment-url-box {
            background: #f5f5f5;
            border-radius: 8px;
            padding: 10px 12px;
            word-break: break-all;
            font-size: 0.8rem;
            color: #333;
            margin-bottom: 16px;
        }
        .payment-modal .payment-url-box a {
            color: #285793;
            text-decoration: none;
        }
        .payment-modal .btn-close-modal {
            display: block;
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background: #fff;
            color: #666;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        .payment-modal .btn-close-modal:hover {
            background: #f5f5f5;
        }
        .payment-modal .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 16px;
        }
        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        .status-badge.paid {
            background: #d1fae5;
            color: #065f46;
        }
        .payment-modal .copy-url-btn {
            display: inline-block;
            margin-top: 4px;
            padding: 4px 10px;
            border: 1px solid #285793;
            border-radius: 6px;
            color: #285793;
            font-size: 0.8rem;
            cursor: pointer;
            background: transparent;
        }
        .payment-modal .copy-url-btn:hover {
            background: rgba(40,87,147,0.06);
        }
        .alert-login {
            background: rgba(254,226,226,0.9);
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .alert-login a {
            color: #285793;
            font-weight: 600;
            text-decoration: underline;
        }
        .nav-vspace {
            height: 60px;
        }
        @media (max-width: 560px) {
            .buy-container { padding: 16px 10px 100px; }
            .buy-card { padding: 20px 16px; }
        }
    </style>
</head>
<body>
    @include('partials.topbar')
    @include('partials.dock', ['active' => 'buy'])

    <div class="page-header" style="
        text-align:center;padding:20px 16px 8px;
        font-size:1.3rem;font-weight:700;color:#285793;
    "><?= htmlspecialchars(t('token_purchase')) ?></div>

    <div class="nav-vspace"></div>

    <div class="buy-container">
        <div id="globalAlert" class="alert alert-error" style="display:none;"></div>

        <div class="buy-card">
            <h2><?= htmlspecialchars(t('token_purchase')) ?></h2>

            <div class="plan-list" id="planList">
                <?php foreach ($plans as $i => $p): ?>
                    <div class="plan-card<?= $i === 0 ? ' selected' : '' ?>" data-plan="<?= $i ?>" onclick="selectPlan(<?= $i ?>)">
                        <div class="price-big"><?= htmlspecialchars(number_format($p['price_usdt'], 2)) ?> USDT</div>
                        <div class="price-desc">
                            = <?= htmlspecialchars(number_format($p['token_amount'])) ?> <?= htmlspecialchars($symbol) ?>
                            <?php if ($p['bonus_amount'] > 0): ?>
                                + <?= t('bonus_gift', ['amount' => number_format($p['bonus_amount']), 'symbol' => $symbol]) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="form-group">
                <label><?= t('buy_amount') ?></label>
                <input type="number" id="qtyInput" value="1" min="1" step="1">
                <div class="hint" id="planHint"><?= t('token_purchase_amount_hint', ['price' => $plans[0]['price_usdt']]) ?></div>
            </div>

            <div class="total-display">
                <div class="total-row">
                    <span class="total-label"><?= htmlspecialchars($symbol . ' ' . t('buy_amount')) ?></span>
                    <span class="total-value" id="tokenTotal"><?= htmlspecialchars(number_format($plans[0]['token_amount'])) ?> <?= htmlspecialchars($symbol) ?></span>
                </div>
                <div class="total-row" id="bonusRow" style="<?= $plans[0]['bonus_amount'] > 0 ? '' : 'display:none;' ?>">
                    <span class="total-label">🎁 <?= t('bonus_gift', ['amount' => '', 'symbol' => '']) ?></span>
                    <span class="total-value" id="bonusTotal" style="color:#e67e22;">+<?= htmlspecialchars(number_format($plans[0]['bonus_amount'])) ?> <?= htmlspecialchars($symbol) ?></span>
                </div>
                <div class="total-row" style="border-top:1px solid rgba(40,87,147,0.15);padding-top:10px;margin-top:6px;">
                    <span class="total-label"><?= t('buy_total') ?></span>
                    <span class="total-value" id="usdtTotal"><?= htmlspecialchars(number_format($plans[0]['price_usdt'], 2)) ?> USDT</span>
                </div>
            </div>

            <div id="loginGateAlert" class="alert alert-login" style="display:none;">
                <?= t('login_to_buy') ?>：<a href="javascript:void(0)" onclick="showLoginModal()"><?= t('submit_login') ?></a>
            </div>

            <button class="btn-buy" id="buyBtn" onclick="handleBuy()">
                <?= t('buy_now') ?>
            </button>
        </div>
    </div>

    {{-- 支付弹窗 --}}
    <div class="payment-modal-overlay" id="paymentModal">
        <div class="payment-modal">
            <h3><?= t('pay_with_usdt') ?></h3>
            <div class="modal-desc" id="modalOrderInfo"></div>
            <div class="status-badge pending" id="modalStatusBadge"><?= t('payment_pending') ?></div>
            <div class="qrcode-box" id="qrcodeBox">
                <div style="color:#999;font-size:0.85rem;" id="qrcodePlaceholder"><?= t('payment_url') ?></div>
                <img id="qrcodeImg" style="display:none;" alt="QR Code">
            </div>
            <div class="payment-url-box" id="paymentUrlBox">
                <div style="margin-bottom:4px;font-weight:600;color:#555;font-size:0.85rem;"><?= t('payment_url') ?>：</div>
                <a href="#" id="paymentUrlLink" target="_blank"></a>
                <button class="copy-url-btn" onclick="copyPaymentUrl()"><?= t('copy_address') ?></button>
            </div>
            <button class="btn-close-modal" onclick="closePaymentModal()"><?= t('close') ?></button>
        </div>
    </div>
    <script>
        const plans = <?= json_encode($plans) ?>;
        let planIndex = 0;
        const symbol = <?= json_encode($symbol) ?>;
        const hintTpl = <?= json_encode(t('token_purchase_amount_hint', ['price' => '{price}'])) ?>;
        let currentPaymentUrl = '';
        let currentTradeId = '';
        let currentOrderId = '';
        let paymentPollTimer = null;

        const buyNowText = <?= json_encode(t('buy_now')) ?>;
        const processingText = <?= json_encode(t('processing_payment')) ?>;
        const paymentPendingText = <?= json_encode(t('payment_pending')) ?>;
        const paymentSuccessText = <?= json_encode(t('payment_success')) ?>;
        const paymentFailedText = <?= json_encode(t('payment_failed')) ?>;
        const paymentCreatedText = <?= json_encode(t('payment_created')) ?>;
        const addressCopiedText = <?= json_encode(t('address_copied')) ?>;
        const buyAmountText = <?= json_encode(t('buy_amount')) ?>;

        const qtyInput = document.getElementById('qtyInput');
        const tokenTotalEl = document.getElementById('tokenTotal');
        const usdtTotalEl = document.getElementById('usdtTotal');
        const bonusTotalEl = document.getElementById('bonusTotal');
        const buyBtn = document.getElementById('buyBtn');
        const globalAlert = document.getElementById('globalAlert');
        const loginGateAlert = document.getElementById('loginGateAlert');

        // 切换购买方案
        function selectPlan(i) {
            planIndex = i;
            document.querySelectorAll('.plan-card').forEach(function (c) {
                c.classList.toggle('selected', parseInt(c.dataset.plan) === i);
            });
            updateTotals();
        }

        function currentPlan() {
            return plans[planIndex] || plans[0];
        }

        // 更新总量显示
        function updateTotals() {
            const p = currentPlan();
            const qty = parseInt(qtyInput.value) || 1;
            const totalTokens = p.token_amount * qty;
            const totalBonus = p.bonus_amount * qty;
            const totalUsdt = (p.price_usdt * qty).toFixed(2);
            tokenTotalEl.textContent = totalTokens.toLocaleString() + ' ' + symbol;
            const bonusRow = document.getElementById('bonusRow');
            if (p.bonus_amount > 0) {
                bonusRow.style.display = '';
                bonusTotalEl.textContent = '+' + totalBonus.toLocaleString() + ' ' + symbol;
            } else {
                bonusRow.style.display = 'none';
            }
            usdtTotalEl.textContent = totalUsdt + ' USDT';
            const hintEl = document.getElementById('planHint');
            if (hintEl) hintEl.textContent = hintTpl.replace('{price}', p.price_usdt);
        }

        qtyInput.addEventListener('input', updateTotals);

        function showAlert(el, msg, type) {
            el.className = 'alert alert-' + type;
            el.textContent = msg;
            el.style.display = 'block';
        }

        function hideAlert(el) {
            el.style.display = 'none';
        }

        // 检查登录（避免与 app.js 全局 checkLogin(dest) 重名而覆盖其跳转逻辑）
        async function checkLoginState() {
            try {
                const res = await fetch('/auth/status');
                const data = await res.json();
                return !!(data && data.data && data.data.logged_in === true);
            } catch (e) {
                return false;
            }
        }

        // 处理购买 - 调用后端 API
        async function handleBuy() {
            hideAlert(globalAlert);
            hideAlert(loginGateAlert);

            const logged = await checkLoginState();
            if (!logged) {
                loginGateAlert.style.display = 'block';
                return;
            }

            const qty = parseInt(qtyInput.value) || 1;

            buyBtn.disabled = true;
            buyBtn.innerHTML = '<span class="loading"></span> ' + processingText;

            try {
                // 下单地址由后端从登录 session 读取，无需前端传地址
                const formData = new FormData();
                formData.append('qty', qty.toString());
                formData.append('plan', String(planIndex));

                const resp = await fetch('/api/buy/create-order', {
                    method: 'POST',
                    body: formData
                });
                const result = await resp.json();

                if (!result.success) {
                    throw new Error(result.message || 'order creation failed');
                }

                currentPaymentUrl = result.data.payment_url;
                currentTradeId = result.data.trade_id;
                currentOrderId = result.data.order_id;
                showPaymentModal(result.data, qty);

            } catch (err) {
                showAlert(globalAlert, paymentFailedText + ': ' + err.message, 'error');
            } finally {
                buyBtn.disabled = false;
                buyBtn.innerHTML = buyNowText;
            }
        }

        // 显示支付弹窗
        function showPaymentModal(data, qty) {
            const modal = document.getElementById('paymentModal');
            const orderInfo = document.getElementById('modalOrderInfo');
            const qrImg = document.getElementById('qrcodeImg');
            const qrPlaceholder = document.getElementById('qrcodePlaceholder');
            const urlLink = document.getElementById('paymentUrlLink');

            const totalUsdt = (currentPlan().price_usdt * qty).toFixed(2);
            orderInfo.textContent = qty + ' ' + buyAmountText + ' = ' + totalUsdt + ' USDT';
            urlLink.textContent = data.payment_url;
            urlLink.href = data.payment_url;
            currentPaymentUrl = data.payment_url;

            // Try to show QR code
            qrImg.style.display = 'none';
            qrPlaceholder.style.display = 'block';
            qrPlaceholder.textContent = paymentPendingText;

            // Load QR from payment URL's built-in QR if available
            if (data.payment_url) {
                qrImg.src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(data.payment_url);
                qrImg.onload = function() {
                    qrImg.style.display = 'block';
                    qrPlaceholder.style.display = 'none';
                };
            }

            modal.classList.add('active');

            // Start polling for payment status
            startPaymentPoll(data.trade_id);
        }

        // 轮询支付状态 - 使用后端 API
        function startPaymentPoll(tradeId) {
            if (paymentPollTimer) {
                clearInterval(paymentPollTimer);
            }
            paymentPollTimer = setInterval(async () => {
                try {
                    const resp = await fetch('/api/buy/query?trade_id=' + encodeURIComponent(tradeId || currentTradeId) + '&order_id=' + encodeURIComponent(currentOrderId));
                    const result = await resp.json();

                    if (result.success && result.data) {
                        const status = result.data.status || result.data.order_status;
                        if (status === 2) {
                            // Payment successful - keep modal open for manual close
                            clearInterval(paymentPollTimer);
                            paymentPollTimer = null;
                            document.getElementById('modalStatusBadge').className = 'status-badge paid';
                            document.getElementById('modalStatusBadge').textContent = paymentSuccessText;
                            showAlert(globalAlert, paymentSuccessText, 'success');
                        } else if (status === 3 || status === 4) {
                            clearInterval(paymentPollTimer);
                            paymentPollTimer = null;
                            showAlert(globalAlert, paymentFailedText, 'error');
                            closePaymentModal();
                        }
                    }
                } catch (e) {
                    // Polling error, ignore and retry
                }
            }, 5000);
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.remove('active');
            if (paymentPollTimer) {
                clearInterval(paymentPollTimer);
                paymentPollTimer = null;
            }
        }

        function copyPaymentUrl() {
            if (navigator.clipboard && currentPaymentUrl) {
                navigator.clipboard.writeText(currentPaymentUrl).then(() => {
                    alert(addressCopiedText);
                });
            }
        }

        // Show login modal（登录弹窗由 partials/dock 定义，使用全局 showLogin）
        function showLoginModal() {
            if (typeof showLogin === 'function') {
                showLogin();
            }
        }

        // Close modal on overlay click
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('paymentModal');
            if (e.target === modal) {
                closePaymentModal();
            }
        });
    </script>
</body>
</html>