{{-- 顶部栏（统一调用）：品牌标题 + 语言切换 + 登录态入口，需页面已定义 $langCode --}}
@php
    $topbarUsername = session('login_username');
    $topbarBaseURI = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
@endphp
<header class="topbar">
    <a class="topbar-brand" href="/"><?= htmlspecialchars(t('site_name_full')) ?></a>
    <div class="topbar-actions">
        <button type="button" class="topbar-lang-btn" id="topbarLangBtn" aria-label="language">
            <img class="dock-icon" src="/images/en.png" alt="">
        </button>
        <div class="topbar-lang-popup" id="topbarLangPopup">
            <?php foreach (languageOptions() as $code => $label): ?>
                <a href="<?= htmlspecialchars($topbarBaseURI . '?lang=' . $code) ?>" class="<?= $code === $langCode ? 'active' : '' ?>"><?= htmlspecialchars($label) ?></a>
            <?php endforeach; ?>
        </div>
        <?php if ($topbarUsername): ?>
            <a class="topbar-user" href="/my" title="<?= htmlspecialchars(t('my')) ?>">
                <span class="topbar-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr((string) $topbarUsername, 0, 1))) ?></span>
                <span class="topbar-username"><?= htmlspecialchars($topbarUsername) ?></span>
            </a>
        <?php else: ?>
            <button type="button" class="topbar-login" id="topbarLoginBtn"><?= t('login_title') ?></button>
        <?php endif; ?>
    </div>
</header>
