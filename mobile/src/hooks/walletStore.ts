import AsyncStorage from '@react-native-async-storage/async-storage';
import { useCallback, useEffect, useState } from 'react';

const WALLET_KEY = 'irt.wallet_address';

/**
 * 当前钱包地址（链上账户）：用于余额/信息/推荐/转账/注册。
 * 与 session 登录态（用于购买/订单）相互独立。
 */
export function useWalletAddress() {
  const [walletAddress, setWalletAddress] = useState<string | null>(null);
  const [loaded, setLoaded] = useState(false);

  useEffect(() => {
    (async () => {
      try {
        const v = await AsyncStorage.getItem(WALLET_KEY);
        setWalletAddress(v);
      } catch {
        // 忽略
      } finally {
        setLoaded(true);
      }
    })();
  }, []);

  const saveWallet = useCallback(async (addr: string | null) => {
    setWalletAddress(addr);
    if (addr) await AsyncStorage.setItem(WALLET_KEY, addr);
    else await AsyncStorage.removeItem(WALLET_KEY);
  }, []);

  return { walletAddress, loaded, saveWallet };
}