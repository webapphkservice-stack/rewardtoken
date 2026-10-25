@php
    $config = getSiteConfig();
    $langCode = currentLanguage();
    $symbol = $config['symbol'] ?? 'IRT';
@endphp
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(str_replace('_', '-', $langCode)) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('member_list') ?> - <?= htmlspecialchars($config['site_name'] ?? t('site_name')) ?></title>
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
        .table-wrap { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
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
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #718096;
        }
        .num { text-align: right; }
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
            <h1>👥 <?= t('member_list') ?></h1>
            <p><?= t('member_list_desc') ?></p>
        </div>

        <div class="card">
            <h2><?= t('statistics') ?></h2>
            <div class="summary-grid">
                <div class="summary-item">
                    <label><?= t('total_members') ?></label>
                    <div class="value"><?= $totalMembers ?></div>
                </div>
                <div class="summary-item">
                    <label><?= t('total_spent_usdt') ?></label>
                    <div class="value"><?= number_format($totalPurchasedUsdt, 2) ?></div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2><?= t('member_details') ?></h2>
            <div class="table-wrap">
                <?php if (empty($members)): ?>
                    <p class="empty-state"><?= t('no_members') ?></p>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th><?= t('username') ?></th>
                            <th><?= t('wallet_address') ?></th>
                            <th><?= t('inviter') ?></th>
                            <th><?= t('register_time') ?></th>
                            <th class="num"><?= t('token_balance') ?> (<?= htmlspecialchars($symbol) ?>)</th>
                            <th class="num"><?= t('purchase_order_count') ?></th>
                            <th class="num"><?= t('total_spent_usdt') ?> (USDT)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['username']) ?></td>
                            <td class="address"><?= htmlspecialchars($m['address']) ?></td>
                            <td class="address"><?= $m['inviter'] !== '' && !preg_match('/^0x0{40}$/i', $m['inviter']) ? htmlspecialchars($m['inviter']) : '—' ?></td>
                            <td><?= $m['registered_at'] > 0 ? date('Y-m-d H:i', $m['registered_at']) : '—' ?></td>
                            <td class="num"><?= $m['balance'] !== null ? htmlspecialchars($m['balance']) : '—' ?></td>
                            <td class="num"><?= $m['order_count'] ?></td>
                            <td class="num"><?= number_format($m['total_usdt'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
