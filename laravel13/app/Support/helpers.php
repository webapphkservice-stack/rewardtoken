<?php

use Web3\Contracts\Ethabi;
use Web3\Utils;

/* ==================== 站点配置 ==================== */

function getSiteConfig(): array
{
    $configFile = __DIR__ . '/../../config/site.json';
    if (file_exists($configFile)) {
        $json = file_get_contents($configFile);
        $config = json_decode($json, true);
        if (is_array($config)) {
            return $config;
        }
    }
    return [
        'site_name' => '邀请奖励代币',
        'site_name_full' => '邀请奖励代币 (IRT)',
        'site_description' => '基于 Geth 的三级推荐奖励系统演示',
        'symbol' => 'IRT',
    ];
}

/* ==================== 多语言 ==================== */

function supportedLanguages(): array
{
    return [
        'zh-cn' => '简体中文',
        'zh-tw' => '繁體中文',
        'en'    => 'English',
        'ja'    => '日本語',
        'ko'    => '한국어',
        'th'    => 'ไทย',
        'ms'    => 'Bahasa Melayu',
        'vi'    => 'Tiếng Việt',
    ];
}

function detectLanguage(): string
{
    $supportedLanguages = supportedLanguages();

    if (isset($_GET['lang']) && isset($supportedLanguages[$_GET['lang']])) {
        setcookie('lang', $_GET['lang'], time() + 60 * 60 * 24 * 30, '/');
        return $_GET['lang'];
    }

    if (isset($_COOKIE['lang']) && isset($supportedLanguages[$_COOKIE['lang']])) {
        return $_COOKIE['lang'];
    }

    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $acceptLangs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        foreach ($acceptLangs as $lang) {
            $lang = strtolower(trim(explode(';', $lang)[0]));
            if (isset($supportedLanguages[$lang])) {
                return $lang;
            }
            $base = explode('-', $lang)[0];
            if (isset($supportedLanguages[$base])) {
                return $base;
            }
        }
    }

    return 'zh-cn';
}

function loadTranslations(string $lang): array
{
    $file = __DIR__ . '/../../lang/' . $lang . '.php';
    if (file_exists($file)) {
        $translations = require $file;
        if (is_array($translations)) {
            return $translations;
        }
    }
    return require __DIR__ . '/../../lang/zh-cn.php';
}

function t(string $key, array $replacements = []): string
{
    static $translations = null;
    static $lang = null;

    if ($translations === null) {
        $lang = detectLanguage();
        $translations = loadTranslations($lang);
    }

    $text = $translations[$key] ?? $key;

    foreach ($replacements as $k => $v) {
        $text = str_replace('{' . $k . '}', (string) $v, $text);
    }

    return $text;
}

function currentLanguage(): string
{
    static $lang = null;
    if ($lang === null) {
        $lang = detectLanguage();
    }
    return $lang;
}

function languageOptions(): array
{
    return supportedLanguages();
}

/* ==================== 合约工具 ==================== */

/**
 * 编码合约方法调用：函数选择器(4字节) + ABI 编码参数
 */
function encodeCall(string $functionName, array $types, array $params): string
{
    $ethabi = new Ethabi();
    $sig = substr(Utils::sha3($functionName . '(' . implode(',', $types) . ')'), 0, 10);
    $encoded = substr($ethabi->encodeParameters($types, $params), 2);
    return $sig . $encoded;
}

/**
 * 解码 ABI 动态字符串返回（去掉 0x）
 */
function decodeString($hex): string
{
    if (!$hex || $hex === '0x') return '';
    $hex = substr($hex, 2); // 去掉 0x
    // 第一个 32 字节是动态数据的偏移（字节数）
    $offset = hexdec(substr($hex, 0, 64));
    // 长度位于偏移位置
    $length = hexdec(substr($hex, $offset * 2, 64));
    // 数据紧跟在长度后面
    $stringHex = substr($hex, $offset * 2 + 64, $length * 2);
    return hex2bin($stringHex);
}

/**
 * 通过 scrypt 从密码派生密钥（keystore v3 使用）。
 * 优先使用 python3 原生 hashlib.scrypt（快），否则回退 node crypto.scryptSync。
 * 成功返回二进制派生密钥，失败返回 null。
 */
function scryptDeriveKey(string $password, string $saltHex, int $n, int $r, int $p, int $dklen): ?string
{
    $payload = [
        'password' => $password,
        'salt' => $saltHex,
        'n' => $n,
        'r' => $r,
        'p' => $p,
        'dklen' => $dklen,
    ];

    // python3：hashlib.scrypt
    $pyScript = <<<'PY'
import json,sys,hashlib
p=json.load(open(sys.argv[1]))
k=hashlib.scrypt(p['password'].encode('utf-8'), n=p['n'], r=p['r'], p=p['p'], dklen=p['dklen'], salt=bytes.fromhex(p['salt']), maxmem=1024*1024*1024)
sys.stdout.write(k.hex())
PY;
    // node：crypto.scryptSync
    $nodeScript = <<<'JS'
const fs=require('fs'),c=require('crypto');
const p=JSON.parse(fs.readFileSync(process.argv[1],'utf8'));
const k=c.scryptSync(Buffer.from(p.password),Buffer.from(p.salt,'hex'),p.dklen,{N:p.n,r:p.r,p:p.p,maxmem:1024*1024*1024});
process.stdout.write(k.toString('hex'));
JS;

    foreach ([['python3', $pyScript], ['node', $nodeScript]] as [$bin, $script]) {
        @exec('command -v ' . escapeshellarg($bin), $found, $fc);
        if ($fc !== 0 || empty($found)) {
            continue;
        }
        $file = tempnam(sys_get_temp_dir(), 'scrypt');
        if ($file === false) {
            continue;
        }
        file_put_contents($file, json_encode($payload));
        $cmd = escapeshellcmd($bin) . ' -c ' . escapeshellarg($script) . ' ' . escapeshellarg($file);
        $out = shell_exec($cmd . ' 2>/dev/null');
        @unlink($file);
        if ($out !== null && $out !== '') {
            return hex2bin(trim($out)) ?: null;
        }
    }

    return null;
}

/**
 * 解密 keystore v3（scrypt + aes-128-ctr），成功返回私钥 hex（0x 前缀），失败返回 null。
 */
function decryptKeystoreV3(array $keystore, string $password): ?string
{
    $crypto = $keystore['crypto'] ?? null;
    if (!is_array($crypto) || ($crypto['kdf'] ?? '') !== 'scrypt') {
        return null;
    }
    $kdf = $crypto['kdfparams'] ?? [];
    $dklen = (int) ($kdf['dklen'] ?? 32);
    $key = scryptDeriveKey(
        $password,
        $kdf['salt'] ?? '',
        (int) ($kdf['n'] ?? 262144),
        (int) ($kdf['r'] ?? 8),
        (int) ($kdf['p'] ?? 1),
        $dklen
    );
    if (!$key || strlen($key) !== $dklen) {
        return null;
    }

    $cipherText = hex2bin($crypto['ciphertext'] ?? '');
    $mac = strtolower(substr(Utils::sha3(substr($key, 16, 16) . $cipherText), 2));
    if ($mac !== strtolower($crypto['mac'] ?? '')) {
        return null;
    }

    $iv = $crypto['cipherparams']['iv'] ?? '';
    $privateRaw = openssl_decrypt($cipherText, 'aes-128-ctr', substr($key, 0, 16), OPENSSL_RAW_DATA, hex2bin($iv));
    if ($privateRaw === false || strlen($privateRaw) !== 32) {
        return null;
    }

    return '0x' . bin2hex($privateRaw);
}

/**
 * 统一 JSON 响应（与原 api/*.php 输出契约保持一致）
 */
function gethMasterPassword(): string
{
    $file = storage_path('geth/masterpass.txt');
    if (is_file($file)) {
        $pass = trim((string) file_get_contents($file));
        if ($pass !== '') {
            return $pass;
        }
    }
    $pass = bin2hex(random_bytes(16));
    @file_put_contents($file, $pass);
    return $pass;
}

function jsonResponse(bool $success, string $message = '', $data = null): void
{
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
}

/**
 * 合约地址文件路径
 */
function contractAddressFile(): string
{
    return storage_path('contracts/compiled/contract_address.txt');
}

/**
 * 读取已部署的合约地址（未部署返回 null）
 */
function readContractAddress(): ?string
{
    $content = @file_get_contents(contractAddressFile());
    if (!$content) {
        return null;
    }
    $content = trim($content);
    return $content !== '' ? $content : null;
}
