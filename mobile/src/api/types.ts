// 后端统一 JSON 响应基型
export interface ApiEnvelope {
  success: boolean;
  message?: string;
  data?: unknown;
}

/** GET /api/status 返回（成功时平铺字段） */
export interface StatusData {
  success: boolean;
  message?: string;
  contract_address: string;
  admin: string;
  name: string;
  symbol: string;
  total_supply: number;
  invite_reward: number;
  referral_levels: number[];
  wallet_count: number;
  block: {
    number: number | null;
    hash: string | null;
    timestamp: number | null;
    transactions: number;
    gas_used: number;
    gas_limit: number;
    size: number;
    miner: string | null;
  } | null;
}

/** 钱包/账户信息 app 内定义 */
export interface WalletCreated {
  address: string;
  private_key: string;
}

/** GET /api/geth-wallet?action=info 返回 */
export interface WalletInfo {
  address: string;
  is_registered: boolean;
  inviter: string | null;
  inviter_chain: (string | null)[];
  invite_count: number;
  total_rewards: string;
  balance: string;
}

/** GET /api/geth-wallet?action=txs 记录 */
export interface TxRecord {
  type: 'transfer' | 'registered' | 'reward';
  from?: string;
  to?: string;
  value?: string;
  user?: string;
  inviter?: string;
  level?: number;
  block_number: number;
  tx_hash: string;
}

/** GET /api/rewards?address= 明细 */
export interface RewardItem {
  inviter: string;
  amount: number;
  level: number;
  block_number: number;
  tx_hash: string;
}

/** GET /api/block?action=list 区块 */
export interface BlockSummary {
  number: number;
  hash?: string;
  timestamp?: number;
  transactions?: number;
  gas_used?: number;
  gas_limit?: number;
  size?: number;
  miner?: string;
}

export interface BlockListData {
  latest: number | null;
  offset: number;
  count: number;
  has_more: boolean;
  blocks: BlockSummary[];
}

/** POST /api/orders 订单 */
export interface OrderItem {
  order_id: string;
  address?: string;
  amount?: string | number;
  qty?: number;
  total_usdt?: string | number;
  token_amount?: string | number;
  bonus_amount?: string | number;
  trade_id?: string;
  status?: string;
  created_at?: number;
  [key: string]: unknown;
}

/** 登录响应 */
export interface LoginData {
  address: string;
  username: string;
}

/** POST /api/geth-wallet action=register / send 返回 */
export interface TxResult {
  tx_hash: string;
}

/** POST /api/buy/create-order 返回 */
export interface BuyOrderData {
  order_id: string;
  trade_id: string;
  payment_url: string;
  expired_at: string;
}