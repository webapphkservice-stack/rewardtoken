{{-- 底部 Dock 导航（统一调用）：传入 $active 指定当前页，取值 home|wallet|rewards|transfer|my --}}
@php $active = $active ?? ''; @endphp
<nav class="navbar">
    <a href="/"<?= $active === 'home' ? ' class="active"' : '' ?>><img class="dock-icon" src="/images/home.png" alt=""><?= t('home') ?></a>
    <button type="button" class="lang-btn" aria-label="language">
        <img class="dock-icon" src="/images/en.png" alt="">
    </button>
    <a href="/my" id="dockMyBtn" class="dock-my<?= $active === 'my' ? ' active' : '' ?>" title="<?= htmlspecialchars(t('my')) ?>"><img class="dock-icon" src="/images/my.png" alt="<?= htmlspecialchars(t('my')) ?>"></a>
    <div class="lang-popup" id="langPopup">
        <?php $baseURI = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>
        <?php foreach (languageOptions() as $code => $label): ?>
            <a href="<?= htmlspecialchars($baseURI . '?lang=' . $code) ?>" class="<?= $code === $langCode ? 'active' : '' ?>"><?= htmlspecialchars($label) ?></a>
        <?php endforeach; ?>
    </div>
    <button type="button" class="dock-toggle" aria-label="menu">
        <span class="bar"></span><span class="bar"></span><span class="bar"></span>
    </button>
    <div class="dock-menu" id="dockMenu">
        <a href="/buy" class="login-gate<?= $active === 'buy' ? ' active' : '' ?>"><img class="dock-icon" src="/images/buy.png" alt=""><?= t('token_purchase') ?></a>
        <a href="/wallet"<?= $active === 'wallet' ? ' class="active"' : '' ?>><img class="dock-icon" src="/images/wallet.png" alt=""><?= t('wallet_registration') ?></a>
        <a href="/rewards" class="login-gate<?= $active === 'rewards' ? ' active' : '' ?>"><img class="dock-icon" src="/images/invite.png" alt=""><?= t('reward_info') ?></a>
        <a href="/transfer" class="login-gate<?= $active === 'transfer' ? ' active' : '' ?>"><img class="dock-icon" src="/images/transfer.png" alt=""><?= t('send_receive') ?></a>
        <a href="/block"<?= $active === 'block' ? ' class="active"' : '' ?>><img class="dock-icon" src="/images/chain.png" alt=""><?= t('block_info') ?></a>
    </div>
</nav>

<div class="login-overlay" id="loginOverlay" style="display:none;">
    <div class="login-box">
        <div class="login-head">
            <h3><?= t('login_title') ?></h3>
            <button type="button" class="login-close" id="loginClose" aria-label="<?= htmlspecialchars(t('close')) ?>">&times;</button>
        </div>
        <div id="loginAlert" class="alert" style="display:none;"></div>
        <div class="form-group">
            <label><?= t('username') ?></label>
            <input type="text" id="loginUsername" autocomplete="username">
        </div>
        <div class="form-group">
            <label><?= t('account_password') ?></label>
            <input type="password" id="loginPassword" autocomplete="current-password">
        </div>
        <button type="button" class="btn-login" id="loginSubmit"><?= t('submit_login') ?></button>
        <div class="login-register">
            <span><?= t('no_account') ?></span>
            <a href="/wallet" id="loginRegisterLink"><?= t('go_register') ?></a>
        </div>
    </div>
</div>