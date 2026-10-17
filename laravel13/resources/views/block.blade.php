@php
    $config = getSiteConfig();
    $langCode = currentLanguage();
@endphp
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(str_replace('_', '-', $langCode)) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('block_info_title') ?> - <?= htmlspecialchars(t('site_name_full')) ?></title>
    <link rel="stylesheet" href="/css/app.css?v=20260918btx6">
    <script src="/js/app.js?v=5" defer></script>
</head>
<body>
    <div class="container">
        @include('partials.dock', ['active' => 'block'])

        <div class="header">
            <h1>⛓ <?= t('block_info_title') ?></h1>
            <p><?= t('block_info_desc') ?></p>
        </div>

        <div id="blockAlert" class="alert" style="display: none;"></div>

        <div class="card">
            <h2><?= t('recent_blocks') ?></h2>
            <div id="blockListContainer">
                <p style="text-align: center; color: #718096; padding: 40px;"><?= t('loading') ?></p>
            </div>
        </div>
    </div>

    <script>
        const langCode = '<?= $langCode ?>';
        const L = {
            block_height: '<?= t('block_height') ?>',
            block_hash: '<?= t('block_hash') ?>',
            block_txs: '<?= t('block_txs') ?>',
            block_time: '<?= t('block_time') ?>',
            gas_used: '<?= t('gas_used') ?>',
            miner: '<?= t('miner') ?>',
            from: '<?= t('from') ?>',
            to: '<?= t('to') ?>',
            amount: '<?= t('amount') ?>',
            tx_hash: '<?= t('tx_hash') ?>',
            loading: '<?= t('loading') ?>',
            loading_failed: '<?= t('loading_failed') ?>',
            no_records: '<?= t('no_records') ?>',
            detail: '<?= t('view_detail') ?>',
            back: '<?= t('back') ?>',
            prev: '<?= t('prev') ?>',
            next: '<?= t('next') ?>',
            page: '<?= t('page') ?>'
        };

        function fmtTime(ts) {
            if (!ts) return '-';
            const d = new Date(ts * 1000);
            const p = function (n) { return n < 10 ? '0' + n : '' + n; };
            return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + ' ' + p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds());
        }

        function fmtAmount(wei) {
            if (wei === null || wei === undefined || wei === '' || wei === '0') return '0';
            if (typeof BigInt !== 'undefined') {
                try {
                    const w = BigInt(wei);
                    const unit = 1000000000000000000n;
                    const whole = w / unit;
                    const frac = w % unit;
                    const fracStr = (frac.toString()).padStart(18, '0').slice(0, 6).replace(/0+$/, '');
                    return whole.toString() + (fracStr ? '.' + fracStr : '');
                } catch (e) { /* fallthrough */ }
            }
            const n = parseFloat(wei) / 1e18;
            if (isNaN(n) || n === 0) return '0';
            return n.toLocaleString(undefined, { maximumFractionDigits: 6 });
        }

        function shortHash(h, n) {
            if (!h) return '-';
            const k = n || 10;
            return h.length > 2 * k ? h.slice(0, k) + '…' + h.slice(-k) : h;
        }

        function esc(s) {
            return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        let blockOffset = 0;
        const BLOCK_PAGE_SIZE = 20;

        async function loadBlocks() {
            const container = document.getElementById('blockListContainer');
            try {
                const res = await fetch('/api/block?action=list&count=' + BLOCK_PAGE_SIZE + '&offset=' + blockOffset);
                const data = await res.json();
                if (data.success) {
                    renderBlocks(data.data);
                } else {
                    container.innerHTML = '<p class="empty-state">' + esc(data.message || L.loading_failed) + '</p>';
                }
            } catch (err) {
                container.innerHTML = '<p class="empty-state">' + esc(L.loading_failed) + '</p>';
            }
        }

        function renderBlocks(data) {
            const container = document.getElementById('blockListContainer');
            const blocks = data.blocks || [];
            if (!blocks.length) {
                container.innerHTML = '<p class="empty-state">' + L.no_records + '</p>';
                return;
            }
            let html = '<div class="latest-badge">' + L.block_height + ': ' + data.latest + '</div>';
            html += '<div class="block-list">';
            blocks.forEach(function (b) {
                html += '<div class="block-item" onclick="loadDetail(' + b.number + ')">';
                html += '<div class="block-item-head"><span class="block-num">' + L.block_height + ' ' + b.number + '</span><span class="block-txcount">' + b.txCount + ' ' + L.block_txs + '</span></div>';
                html += '<div class="block-item-row"><span class="k">' + L.block_hash + '</span><span class="v mono">' + esc(b.hash) + '</span></div>';
                html += '<div class="block-item-row"><span class="k">' + L.block_time + '</span><span class="v">' + fmtTime(b.timestamp) + '</span></div>';
                html += '<div class="block-item-row"><span class="k">' + L.gas_used + '</span><span class="v">' + b.gasUsed + ' / ' + b.gasLimit + '</span></div>';
                const btxs = b.transactions || [];
                if (btxs.length) {
                    html += '<div class="block-item-txs">';
                    btxs.forEach(function (tx) {
                        const amtText = (tx.token_amount != null && tx.token_amount !== '0') ? fmtAmount(tx.token_amount) + ' IRT' : fmtAmount(tx.value) + ' ET';
                        html += '<div class="block-item-tx-row">'
                            + '<div class="tx-line"><span class="tx-label">' + L.tx_hash + '</span><span class="tx-val mono full-addr">' + esc(tx.hash) + '</span></div>'
                            + '<div class="tx-line"><span class="tx-label">' + L.from + '</span><span class="tx-val mono full-addr">' + esc(tx.from) + '</span></div>'
                            + '<div class="tx-line"><span class="tx-label">' + L.to + '</span><span class="tx-val mono full-addr">' + esc(tx.to) + '</span></div>'
                            + '<div class="tx-line tx-amt-line"><span class="tx-label">' + L.amount + '</span><span class="tx-val v-amount">' + amtText + '</span></div>'
                            + '</div>';
                    });
                    html += '</div>';
                }
                html += '</div>';
            });
            html += '</div>';
            html += '<div id="blockDetailContainer"></div>';
            html += renderPager(data);
            container.innerHTML = html;
        }

        function renderPager(data) {
            const cur = data.offset || 0;
            const page = Math.floor(cur / BLOCK_PAGE_SIZE) + 1;
            const hasPrev = cur > 0;
            const hasNext = data.has_more && (data.blocks || []).length >= BLOCK_PAGE_SIZE;
            let h = '<div class="block-pager">';
            h += '<button class="pager-btn" ' + (hasPrev ? 'onclick="goBlockPage(' + (cur - BLOCK_PAGE_SIZE) + ')"' : 'disabled') + '>‹ ' + L.prev + '</button>';
            h += '<span class="pager-info">' + L.page + ' ' + page + '</span>';
            h += '<button class="pager-btn" ' + (hasNext ? 'onclick="goBlockPage(' + (cur + BLOCK_PAGE_SIZE) + ')"' : 'disabled') + '>' + L.next + ' ›</button>';
            h += '</div>';
            return h;
        }

        function goBlockPage(offset) {
            if (offset < 0) return;
            blockOffset = offset;
            const top = document.getElementById('blockListContainer');
            window.scrollTo({ top: 0, behavior: 'smooth' });
            loadBlocks();
        }

        async function loadDetail(number) {
            const detailBox = document.getElementById('blockDetailContainer');
            if (!detailBox) return;
            detailBox.innerHTML = '<p class="empty-state">' + L.loading + '</p>';
            try {
                const res = await fetch('/api/block?action=detail&number=' + encodeURIComponent(number));
                const data = await res.json();
                if (data.success) {
                    detailBox.innerHTML = renderDetail(data.data);
                } else {
                    detailBox.innerHTML = '<p class="empty-state">' + esc(data.message || L.loading_failed) + '</p>';
                }
            } catch (err) {
                detailBox.innerHTML = '<p class="empty-state">' + esc(L.loading_failed) + '</p>';
            }
        }

        function renderDetail(b) {
            let html = '<div class="block-detail">';
            html += '<div class="block-detail-title">' + L.block_height + ' ' + b.number + '</div>';
            html += kv(L.block_hash, b.hash, true);
            html += kv(L.block_time, fmtTime(b.timestamp), false);
            html += kv(L.miner, b.miner, true);
            html += kv(L.gas_used, b.gasUsed + ' / ' + b.gasLimit, false);
            html += kv('Parent ' + L.block_hash, b.parentHash, true);

            const txs = b.transactions || [];
            html += '<div class="detail-section-title">' + L.block_txs + ' (' + txs.length + ')</div>';
            if (!txs.length) {
                html += '<p class="empty-state">' + L.no_records + '</p>';
            } else {
                html += '<div class="tx-list">';
                txs.forEach(function (tx) {
                    html += '<div class="tx-item">';
                    html += kv(L.tx_hash, tx.hash, true);
                    html += kv(L.from, tx.from, true);
                    html += kv(L.to, tx.to, true);
                    html += kv(L.amount, tx.value, false);
                    html += '</div>';
                });
                html += '</div>';
            }
            html += '</div>';
            return html;
        }

        function kv(k, v, mono) {
            return '<div class="detail-row"><span class="k">' + esc(k) + '</span><span class="v' + (mono ? ' mono' : '') + '">' + esc(v) + '</span></div>';
        }

        loadBlocks();
    </script>
</body>
</html>