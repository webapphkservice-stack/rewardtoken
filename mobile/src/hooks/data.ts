import { useCallback, useEffect, useRef, useState } from 'react';
import { translate } from '../i18n/LanguageContext';
import { apiFetch, errMessage } from '../api/apiFetch';
import type {
  BlockListData,
  RewardItem,
  StatusData,
  TxRecord,
  WalletInfo,
} from '../api/types';

export interface GalleryLike<T> {
  data: T | null;
  loading: boolean;
  error: string | null;
  refresh: () => Promise<void>;
}

function useLoad<T>(fetcher: () => Promise<T>, refreshKey: unknown[]): GalleryLike<T> {
  const [data, setData] = useState<T | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await fetcher();
      setData(res);
    } catch (e) {
      setError(errMessage(e));
    } finally {
      setLoading(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [fetcher, ...refreshKey]);

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, refreshKey);

  // 保持 refresh 引用稳定，供 useFocusEffect 等场景安全依赖
  const loadRef = useRef(load);
  useEffect(() => {
    loadRef.current = load;
  });

  const refresh = useCallback(async () => {
    await loadRef.current();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return { data, loading, error, refresh };
}

export function useStatus(): GalleryLike<StatusData> {
  return useLoad(async () => {
    const res = await apiFetch<StatusData>('/api/status');
    if (!res.success) throw new Error(res.message || translate('load_failed', '加载失败'));
    return res;
  }, []);
}

export function useWalletInfo(address: string | null, enabled: boolean): GalleryLike<WalletInfo> {
  return useLoad(async () => {
    if (!address) throw new Error(translate('no_wallet_address', '无钱包地址'));
    const res = await apiFetch<{ success: boolean; message?: string; data?: WalletInfo }>(
      `/api/geth-wallet?action=info&address=${encodeURIComponent(address)}`
    );
    if (!res.success) throw new Error(res.message || translate('load_failed', '加载失败'));
    return res.data ?? ({} as WalletInfo);
  }, [address, enabled]);
}

export function useRewards(address: string | null): GalleryLike<RewardItem[]> {
  return useLoad(async () => {
    const query = address ? `?address=${encodeURIComponent(address)}` : '';
    const res = await apiFetch<{ success: boolean; message?: string; data?: RewardItem[] }>(
      `/api/rewards${query}`
    );
    if (!res.success) throw new Error(res.message || translate('load_failed', '加载失败'));
    return res.data ?? [];
  }, [address]);
}

export function useBlocks(limit = 10): {
  data: BlockListData | null;
  loading: boolean;
  error: string | null;
  refresh: () => Promise<void>;
  loadMore: () => Promise<void>;
  loadingMore: boolean;
} {
  const [data, setData] = useState<BlockListData | null>(null);
  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchPage = useCallback(async (offset: number): Promise<BlockListData> => {
    const res = await apiFetch<{ success: boolean; message?: string; data?: BlockListData }>(
      `/api/block?action=list&offset=${offset}&limit=${limit}`
    );
    if (!res.success) throw new Error(res.message || translate('load_failed', '加载失败'));
    return res.data ?? { latest: null, offset, count: 0, has_more: false, blocks: [] };
  }, [limit]);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      setData(await fetchPage(0));
    } catch (e) {
      setError(errMessage(e));
    } finally {
      setLoading(false);
    }
  }, [fetchPage]);

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const refresh = useCallback(async () => {
    await load();
  }, [load]);

  const loadMore = useCallback(async () => {
    if (!data?.has_more || loadingMore) return;
    setLoadingMore(true);
    try {
      const next = await fetchPage(data.offset + data.count);
      setData((prev) =>
        prev
          ? { ...next, blocks: [...(prev.blocks ?? []), ...(next.blocks ?? [])] }
          : next
      );
    } catch {
      // 翻页失败静默
    } finally {
      setLoadingMore(false);
    }
  }, [data, loadingMore, fetchPage]);

  return { data, loading, error, refresh, loadMore, loadingMore };
}

export function useTxs(address: string | null): GalleryLike<TxRecord[]> {
  return useLoad(async () => {
    if (!address) throw new Error(translate('no_wallet_address', '无钱包地址'));
    const res = await apiFetch<{ success: boolean; message?: string; data?: TxRecord[] }>(
      `/api/geth-wallet?action=txs&address=${encodeURIComponent(address)}`
    );
    if (!res.success) throw new Error(res.message || translate('load_failed', '加载失败'));
    return res.data ?? [];
  }, [address]);
}