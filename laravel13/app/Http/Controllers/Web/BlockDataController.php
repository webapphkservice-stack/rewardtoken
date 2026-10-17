<?php

namespace App\Http\Controllers\Web;

use App\Services\RpcService;

class BlockDataController
{
    /**
     * 区块信息数据接口（需登录）
     * action=list      => 最近 N 个区块列表
     * action=detail    => 指定高度/哈希的区块详情（含交易）
     */
    public function index()
    {
        $action = request()->input('action', 'list');

        try {
            switch ($action) {
                case 'list':
                    $this->listBlocks();
                    break;
                case 'detail':
                    $this->blockDetail();
                    break;
                default:
                    \jsonResponse(false, t('unknown_action'));
            }
        } catch (\Throwable $e) {
            \jsonResponse(false, $e->getMessage());
        }
    }

    private function listBlocks()
    {
        $rpc = RpcService::getInstance();
        $latestHex = $rpc->call('eth_blockNumber');
        $latest = (int) $this->hexToDec($latestHex);
        $count = max(1, min(20, (int) request()->input('count', 20)));
        $offset = max(0, (int) request()->input('offset', 0));
        $blocks = [];

        $end = $latest - $offset;
        $start = max(0, $end - $count + 1);

        for ($i = $end; $i >= $start; $i--) {
            $b = $rpc->call('eth_getBlockByNumber', ['0x' . dechex($i), true]);
            if (!$b) {
                continue;
            }
            $txs = [];
            foreach ($b['transactions'] ?? [] as $tx) {
                $input = $tx['input'] ?? '0x';
                $txs[] = [
                    'hash'         => $tx['hash'] ?? '',
                    'from'         => $tx['from'] ?? '',
                    'to'           => $tx['to'] ?? '',
                    'value'        => $this->hexToDec($tx['value'] ?? '0x0'),
                    'token_amount' => $this->decodeErc20Transfer($input, $tx['to'] ?? ''),
                ];
            }
            $blocks[] = [
                'number'     => $i,
                'hash'       => $b['hash'] ?? '',
                'parentHash' => $b['parentHash'] ?? '',
                'miner'      => $b['miner'] ?? '',
                'gasUsed'    => $this->hexToDec($b['gasUsed'] ?? '0x0'),
                'gasLimit'   => $this->hexToDec($b['gasLimit'] ?? '0x0'),
                'timestamp'  => (int) $this->hexToDec($b['timestamp'] ?? '0x0'),
                'txCount'    => count($b['transactions'] ?? []),
                'size'       => $this->hexToDec($b['size'] ?? '0x0'),
                'transactions' => $txs,
            ];
        }

        \jsonResponse(true, '', [
            'latest' => $latest,
            'offset' => $offset,
            'count'  => $count,
            'has_more' => $start > 0,
            'blocks' => $blocks,
        ]);
    }

    private function blockDetail()
    {
        $rpc = RpcService::getInstance();
        $number = request()->input('number');
        $hash = request()->input('hash');

        if ($hash) {
            $b = $rpc->call('eth_getBlockByHash', [$hash, true]);
        } else {
            $n = ($number === '' || $number === null) ? 'latest' : $number;
            $b = $rpc->call('eth_getBlockByNumber', ['0x' . dechex((int) $n), true]);
        }

        if (!$b) {
            \jsonResponse(false, t('block_not_found'));
            return;
        }

        $txs = [];
        foreach ($b['transactions'] ?? [] as $tx) {
            $txs[] = [
                'hash'     => $tx['hash'] ?? '',
                'from'     => $tx['from'] ?? '',
                'to'       => $tx['to'] ?? '',
                'value'    => $this->hexToDec($tx['value'] ?? '0x0'),
                'gas'      => $this->hexToDec($tx['gas'] ?? '0x0'),
                'gasPrice' => $this->hexToDec($tx['gasPrice'] ?? '0x0'),
                'input'    => $tx['input'] ?? '0x',
                'nonce'    => $this->hexToDec($tx['nonce'] ?? '0x0'),
            ];
        }

        \jsonResponse(true, '', [
            'number'       => $this->hexToDec($b['number'] ?? '0x0'),
            'hash'         => $b['hash'] ?? '',
            'parentHash'   => $b['parentHash'] ?? '',
            'miner'        => $b['miner'] ?? '',
            'gasUsed'      => $this->hexToDec($b['gasUsed'] ?? '0x0'),
            'gasLimit'     => $this->hexToDec($b['gasLimit'] ?? '0x0'),
            'timestamp'    => (int) $this->hexToDec($b['timestamp'] ?? '0x0'),
            'size'         => $this->hexToDec($b['size'] ?? '0x0'),
            'difficulty'   => $this->hexToDec($b['difficulty'] ?? '0x0'),
            'extraData'    => $b['extraData'] ?? '0x',
            'transactions' => $txs,
        ]);
    }

    private function hexToDec($hex): string
    {
        if (!is_string($hex) || stripos($hex, '0x') !== 0) {
            return (string) ($hex ?? '0');
        }
        return (string) base_convert(substr($hex, 2), 16, 10);
    }

    /**
     * 解码 ERC-20 transfer(address,uint256) 的 input，返回代币数量（wei 十进制字符串）。
     * selector = 0xa9059cbb。若非 transfer 调用或无法解码则返回 null。
     */
    private function decodeErc20Transfer(string $input, string $to): ?string
    {
        if (!is_string($input) || stripos($input, '0x') !== 0 || strlen($input) < 10) {
            return null;
        }
        $body = substr($input, 2);
        // selector 前 8 字符
        if (strtolower(substr($body, 0, 8)) !== 'a9059cbb') {
            return null;
        }
        // 需 to 为合约地址才属于代币转账（避免普通 0 值调用误判）
        $contract = readContractAddress();
        if ($contract && strcasecmp($to, $contract) !== 0) {
            return null;
        }
        // 总 input 应含 4B selector + 32B to + 32B amount = 68 字节 = 136 hex 字符
        if (strlen($body) < 8 + 64 + 64) {
            return null;
        }
        $amountHex = substr($body, 8 + 64, 64);
        // 转十进制（避免 float 精度，用 18 位 wei 校验）
        $amount = $this->gmpHexToDec('0x' . $amountHex);
        return $amount !== null ? $amount : null;
    }

    private function gmpHexToDec(string $hex): ?string
    {
        if (!function_exists('gmp_init') || stripos($hex, '0x') !== 0) {
            return null;
        }
        return gmp_strval(gmp_init(substr($hex, 2), 16), 10);
    }
}