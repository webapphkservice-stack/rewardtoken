<?php
/**
 * 编译 Solidity 合约
 * 使用 solc 编译器，指定 --evm-version berlin 避免 PUSH0 兼容性问题
 */

$contractDir = __DIR__ . '/../storage/contracts';
$outputDir = __DIR__ . '/../storage/contracts/compiled';
$contractPath = $contractDir . '/InviteRewardToken.sol';

// 确保输出目录存在
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

echo "=== 编译邀请奖励代币合约 ===\n\n";

// 读取合约源码
$source = file_get_contents($contractPath);
if (!$source) {
    die("错误: 无法读取合约文件 $contractPath\n");
}
echo "合约文件: $contractPath\n";
echo "源码大小: " . strlen($source) . " bytes\n\n";

// 编译合约（关键：--evm-version berlin 避免 PUSH0 操作码不兼容）
echo "正在编译...\n";
$command = "solc --abi --bin --optimize --optimize-runs=200 --evm-version berlin --overwrite "
    . "-o " . escapeshellarg($outputDir) . " "
    . escapeshellarg($contractPath) . " 2>&1";

exec($command, $output, $returnCode);

if ($returnCode !== 0) {
    echo "编译失败:\n";
    echo implode("\n", $output) . "\n";
    exit(1);
}

// 验证编译结果
$abiFile = $outputDir . '/InviteRewardToken.abi';
$binFile = $outputDir . '/InviteRewardToken.bin';

if (!file_exists($abiFile) || !file_exists($binFile)) {
    echo "错误: 编译输出文件缺失\n";
    exit(1);
}

$abi = file_get_contents($abiFile);
$bin = file_get_contents($binFile);

echo "编译成功!\n";
echo "ABI 文件: $abiFile (" . strlen($abi) . " bytes)\n";
echo "字节码文件: $binFile (" . strlen($bin) . " bytes)\n";

// 解析 ABI 显示合约接口
$abiData = json_decode($abi, true);
echo "\n合约接口:\n";
foreach ($abiData as $item) {
    if ($item['type'] === 'function') {
        $inputs = implode(', ', array_map(function($i) {
            return $i['type'] . ' ' . $i['name'];
        }, $item['inputs'] ?? []));
        echo "  - {$item['name']}($inputs)";
        if (isset($item['outputs']) && count($item['outputs']) > 0) {
            $outputs = implode(', ', array_map(function($o) {
                return $o['type'];
            }, $item['outputs']));
            echo " returns ($outputs)";
        }
        echo " [{$item['stateMutability']}]\n";
    }
}

echo "\n编译完成!\n";
