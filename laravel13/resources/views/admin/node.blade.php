@php
    $config = getSiteConfig();
    $langCode = currentLanguage();
@endphp
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(str_replace('_', '-', $langCode)) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('node_management') ?> - <?= htmlspecialchars($config['site_name'] ?? t('site_name')) ?></title>
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
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .status-item {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #41888A;
        }
        .status-item label {
            display: block;
            font-size: 0.85rem;
            color: #718096;
            margin-bottom: 8px;
        }
        .status-item .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-running { background: #d1fae5; color: #065f46; }
        .status-stopped { background: #fee2e2; color: #991b1b; }
        .btn-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #41888A 0%, #2a6b6d 100%);
            color: #fff;
        }
        .btn-success {
            background: #10b981;
            color: #fff;
        }
        .btn-danger {
            background: #ef4444;
            color: #fff;
        }
        .btn-warning {
            background: #f59e0b;
            color: #fff;
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
        }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-info { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #fff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            vertical-align: middle;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .log-viewer {
            background: #1a202c;
            color: #e2e8f0;
            padding: 16px;
            border-radius: 8px;
            font-family: 'SF Mono', Monaco, monospace;
            font-size: 0.85rem;
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }
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
            <h1>🖥️ <?= t('node_management') ?></h1>
            <p><?= t('node_management_desc') ?></p>
        </div>

        <div class="card">
            <h2><?= t('running_status') ?></h2>
            <div class="status-grid">
                <div class="status-item">
                    <label><?= t('node_status') ?></label>
                    <div class="value" id="nodeStatus"><?= t('connecting') ?></div>
                </div>
                <div class="status-item">
                    <label><?= t('process_pid') ?></label>
                    <div class="value" id="nodePid">-</div>
                </div>
                <div class="status-item">
                    <label><?= t('current_block') ?></label>
                    <div class="value" id="blockNumber">-</div>
                </div>
                <div class="status-item">
                    <label><?= t('peer_count') ?></label>
                    <div class="value" id="peerCount">-</div>
                </div>
            </div>

            <div id="alert" class="alert"></div>

            <div class="btn-group">
                <button class="btn btn-success" id="startBtn" onclick="nodeAction('start')">
                    <?= t('start_node') ?>
                </button>
                <button class="btn btn-danger" id="stopBtn" onclick="nodeAction('stop')">
                    <?= t('stop_node') ?>
                </button>
                <button class="btn btn-warning" id="restartBtn" onclick="nodeAction('restart')">
                    <?= t('restart_node') ?>
                </button>
                <button class="btn btn-primary" id="refreshBtn" onclick="loadStatus()">
                    <?= t('refresh_status') ?>
                </button>
            </div>
        </div>

        <div class="card">
            <h2><?= t('node_info') ?></h2>
            <div class="status-grid">
                <div class="status-item">
                    <label><?= t('rpc_address') ?></label>
                    <div class="value" id="rpcUrl" style="font-size: 1rem;">-</div>
                </div>
                <div class="status-item">
                    <label><?= t('sync_status') ?></label>
                    <div class="value" id="syncStatus">-</div>
                </div>
                <div class="status-item">
                    <label><?= t('account_count') ?></label>
                    <div class="value" id="accountCount">-</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2><?= t('running_logs') ?></h2>
            <div class="log-viewer" id="logViewer"><?= t('no_logs') ?></div>
        </div>
    </main>

    <script>
        const runningText = '<?= t('running_status_value') ?>';
        const stoppedText = '<?= t('stopped_status_value') ?>';
        const syncedText = '<?= t('synced') ?>';
        const syncingText = '<?= t('syncing') ?>';
        const confirmStart = '<?= t('confirm_start') ?>';
        const confirmStop = '<?= t('confirm_stop') ?>';
        const confirmRestart = '<?= t('confirm_restart') ?>';
        const executingText = '<?= t('executing') ?>';
        const getStatusFailedText = '<?= t('get_status_failed') ?>';
        const actionFailedText = '<?= t('action_failed') ?>';

        async function loadStatus() {
            try {
                const res = await fetch('/api/node?action=status');
                const data = await res.json();

                if (data.success) {
                    const status = data.data;
                    const statusEl = document.getElementById('nodeStatus');
                    
                    if (status.running) {
                        statusEl.innerHTML = '<span class="status-badge status-running">' + runningText + '</span>';
                    } else {
                        statusEl.innerHTML = '<span class="status-badge status-stopped">' + stoppedText + '</span>';
                    }

                    document.getElementById('nodePid').textContent = status.pid || '-';
                    document.getElementById('blockNumber').textContent = status.block_number !== null ? status.block_number : '-';
                    document.getElementById('peerCount').textContent = status.peer_count !== undefined ? status.peer_count : '-';
                    document.getElementById('rpcUrl').textContent = status.rpc_url || 'http://localhost:8545';
                    document.getElementById('syncStatus').textContent = status.syncing === false ? syncedText : syncingText;
                    document.getElementById('accountCount').textContent = status.accounts ? status.accounts.length : '-';

                    document.getElementById('startBtn').disabled = status.running;
                    document.getElementById('stopBtn').disabled = !status.running;
                    document.getElementById('restartBtn').disabled = !status.running;
                } else {
                    showAlert(data.message || getStatusFailedText, 'error');
                }
            } catch (err) {
                showAlert(getStatusFailedText + ': ' + err.message, 'error');
            }
        }

        async function nodeAction(action) {
            const confirmMsg = {
                'start': confirmStart,
                'stop': confirmStop,
                'restart': confirmRestart
            };

            if (!confirm(confirmMsg[action])) {
                return;
            }

            showAlert(executingText + ' ' + action + '...', 'info');

            try {
                const res = await fetch('/api/node?action=' + action);
                const data = await res.json();

                if (data.success) {
                    showAlert(data.message, 'success');
                } else {
                    showAlert(data.message, 'error');
                }
                
                setTimeout(loadStatus, 2000);
            } catch (err) {
                showAlert(actionFailedText + ': ' + err.message, 'error');
            }
        }

        function showAlert(message, type) {
            const alert = document.getElementById('alert');
            alert.className = 'alert alert-' + type;
            alert.textContent = message;
            alert.style.display = 'block';
        }

        loadStatus();
        setInterval(loadStatus, 5000);
    </script>
</body>
</html>
