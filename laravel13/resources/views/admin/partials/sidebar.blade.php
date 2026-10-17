@php
    $config = getSiteConfig();
    $langCode = currentLanguage();
    $currentPath = request()->path();
@endphp
<div class="sidebar">
    <div class="sidebar-brand">
        <h2>⚙️ <?= t('admin_panel') ?></h2>
        <p><?= htmlspecialchars($config['site_name'] ?? t('site_name')) ?></p>
    </div>
    <nav class="sidebar-nav">
        <a href="/admin" class="<?= $currentPath === 'admin' ? 'active' : '' ?>">
            <span class="icon">📊</span> <?= t('control_panel') ?>
        </a>
        <a href="/admin/deploy" class="<?= $currentPath === 'admin/deploy' ? 'active' : '' ?>">
            <span class="icon">🚀</span> <?= t('deploy') ?>
        </a>
        <a href="/admin/settings" class="<?= $currentPath === 'admin/settings' ? 'active' : '' ?>">
            <span class="icon">⚙️</span> <?= t('basic_settings') ?>
        </a>
        <a href="/admin/rewards" class="<?= $currentPath === 'admin/rewards' ? 'active' : '' ?>">
            <span class="icon">💰</span> <?= t('reward_records') ?>
        </a>
        <a href="/admin/node" class="<?= $currentPath === 'admin/node' ? 'active' : '' ?>">
            <span class="icon">🖥️</span> <?= t('node_management') ?>
        </a>
        <a href="/">
            <span class="icon">←</span> <?= t('back_to_frontend') ?>
        </a>
    </nav>
    <div class="sidebar-lang">
        <select class="lang-select" onchange="changeLanguage(this.value)">
            <?php foreach (languageOptions() as $code => $label): ?>
                <option value="<?= $code ?>" <?= $code === $langCode ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<script>
    function changeLanguage(lang) {
        const url = new URL(window.location.href);
        url.searchParams.set('lang', lang);
        window.location.href = url.toString();
    }
</script>
<style>
    .sidebar-lang {
        padding: 16px;
        border-top: 1px solid #e2e8f0;
        margin-top: auto;
    }
    .sidebar-lang .lang-select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        font-size: 0.9rem;
        color: #4a5568;
        cursor: pointer;
    }
</style>
