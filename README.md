# IRT 邀请奖励代币平台 (Invite Reward Token)

基于以太坊私链（Geth）+ Laravel 13 的邀请奖励代币平台：用户注册钱包后通过三级邀请关系获得 IRT 代币奖励，支持 USDT 购买代币、链上转账、区块浏览与多语言界面。

## 项目组成

| 模块 | 目录 | 说明 |
|------|------|------|
| Web 后端 | `laravel13/` | Laravel 13 应用：页面、API、管理后台、合约部署与交互 |
| 智能合约 | `laravel13/storage/contracts/` | `InviteRewardToken.sol`（ERC-20 + 三级邀请奖励分发）及编译产物 |
| 链数据 | `laravel13/storage/geth/` | Geth 节点数据、keystore（不入库） |
| 移动端 | `mobile/` | Expo (React Native) 应用 `myweb3` |

## 核心功能

- **钱包注册**：生成 Geth keystore 账户，私钥仅展示一次；注册时按邀请链自动分发三级奖励
- **奖励信息**：查看当前账户的三级奖励明细与汇总
- **收发代币**：IRT 链上转账（离线签名 / dev 解锁账户）
- **区块信息**：区块与交易浏览，自动解码 ERC-20 transfer 金额
- **代币购买**：通过自建 [gmpay-edge](https://github.com/GMWalletApp/gmpay-edge) USDT 支付网关购买 IRT，单价与赠送数量可在后台配置
- **我的页面**：账户概览、邀请链、交易记录、购买订单
- **管理后台**（`/admin`）：节点管理、合约部署、奖励参数、代币购买设置
- **多语言**：简/繁中文、英、日、韩、马来、泰、越

## 代币购买流程

```
/buy 页面 → POST /api/buy/create-order（HMAC-SHA256 签名）
  → gmpay-edge 创建订单，返回 payment_url 收银台
  → 用户转账 USDT
  → gmpay 回调 POST /api/payment/webhook（验签 + 金额/币种/trade_id 校验 + 文件锁幂等）
  → 合约转账 IRT（等待链上 receipt status=0x1）
  → 前端轮询 /api/buy/query 显示到账
```

订单存储于 `laravel13/storage/orders.json`（不入库）。

## 环境要求

- PHP >= 8.2、Composer
- Geth v1.17.x（`brew install ethereum`）
- Bun（运行 gmpay-edge，独立仓库部署）
- Node.js >= 18（移动端 Expo）

## 快速开始

### 1. 启动 Geth 私链（dev 模式，Chain ID 1337）

```bash
cd laravel13/storage/geth
geth --datadir . --http --http.addr 127.0.0.1 --http.port 8545 \
     --http.api eth,net,web3 --dev --dev.period 1
```

dev 模式下 coinbase 账户自动解锁（`eth_accounts[0]`），用作发币与管理员账户。

### 2. 启动 Laravel

```bash
cd laravel13
composer install
cp .env.example .env && php artisan key:generate
php artisan serve --host 127.0.0.1 --port 8080
```

配置 `.env` 中的 Geth RPC 地址（默认 `http://127.0.0.1:8545`）。

### 3. 部署合约

访问管理后台 `/admin/deploy` 部署合约，合约地址写入
`storage/contracts/compiled/contract_address.txt`。

### 4. 启动支付网关（可选，购买功能需要）

gmpay-edge 为独立项目，在其目录执行：

```bash
./start-prod.sh   # 监听 :3000，GMPAY_DATA_DIR 指向 data/
```

并在其管理界面配置商户 API Key（PID/Secret）与 USDT 收款地址，
再填入 Laravel 侧支付控制器。

### 5. 移动端（可选）

```bash
cd mobile
npm install
npx expo start
```

## 目录结构

```
laravel13/
├── app/Http/Controllers/
│   ├── Api/          # 购买、支付回调、钱包、奖励等 API
│   ├── Admin/        # 管理后台
│   └── Web/          # 区块数据解析
├── resources/views/  # Blade 页面（磨砂玻璃风格主题色 #285793）
├── lang/             # 8 语种翻译
├── scripts/          # 合约编译/部署/交互脚本
└── storage/
    ├── contracts/    # 合约源码与编译产物
    └── geth/         # 链数据（不入库）
```

## 安全说明

以下敏感内容已通过 `.gitignore` 排除，**切勿提交或公开**：

- `laravel13/.env`（APP_KEY、RPC 配置）
- `laravel13/storage/accounts.json`、`orders.json`
- `laravel13/storage/geth/`（keystore 私钥文件、masterpass.txt）

已知事项：gmpay 商户 Secret 目前硬编码在
`BuyApiController.php` / `PaymentWebhookController.php`，
生产部署前建议改为环境变量注入并轮换密钥。
