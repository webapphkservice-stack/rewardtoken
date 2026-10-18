import AsyncStorage from '@react-native-async-storage/async-storage';
import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { translate } from '../i18n/LanguageContext';
import { apiFetch, setSessionExpiredHandler } from '../api/apiFetch';
import { clearSessionCookie, getSessionCookie } from '../api/session';

const ADDRESS_KEY = 'irt.address';
const USERNAME_KEY = 'irt.username';

interface AuthContextValue {
  loading: boolean;
  /** 当前登录地址（session 登录态恢复成功时非空） */
  address: string | null;
  username: string | null;
  isAuthed: boolean;
  login: (username: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  /** 设置登录态（钱包注册成功后，若用户未登录取 session 则直接写入）。恢复用 refreshStatus */
  setLogin: (address: string, username: string) => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [address, setAddress] = useState<string | null>(null);
  const [username, setUsername] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const persistLogin = useCallback(async (addr: string | null, uname: string | null) => {
    setAddress(addr);
    setUsername(uname);
    if (addr) await AsyncStorage.setItem(ADDRESS_KEY, addr);
    else await AsyncStorage.removeItem(ADDRESS_KEY);
    if (uname) await AsyncStorage.setItem(USERNAME_KEY, uname);
    else await AsyncStorage.removeItem(USERNAME_KEY);
  }, []);

  // 启动时恢复本地登录态 + 用 /auth/status 校验 session 是否仍有效
  useEffect(() => {
    (async () => {
      try {
        const [a, u, cookie] = await Promise.all([
          AsyncStorage.getItem(ADDRESS_KEY),
          AsyncStorage.getItem(USERNAME_KEY),
          getSessionCookie(),
        ]);

        if (a) setAddress(a);
        if (u) setUsername(u);

        if (cookie) {
          try {
            // 后端返回 { success: true, data: { logged_in, address, username } }
            // 需要解包 .data 拿到真实的 { logged_in } 对象
            const rawRes = await apiFetch<{
              success?: boolean;
              data?: { logged_in?: boolean; address?: string; username?: string };
              logged_in?: boolean;
            }>('/auth/status', { timeout: 10000 });
            const res: { logged_in: boolean; address?: string; username?: string } =
              (rawRes.data ?? rawRes) as any;
            if (res.logged_in && res.address) {
              await persistLogin(res.address, res.username ?? u);
            } else {
              // session 已失效：清 cookie 和本地登录态，保持冷启动干净
              await persistLogin(null, null);
              await clearSessionCookie();
            }
          } catch {
            // 后端不可达或其他异常 → 保留本地登录态，不强制登出
          }
        }
      } catch {
        // 忽略顶层恢复异常
      } finally {
        setLoading(false);
      }
    })();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // 全局会话失效（401）：清理本地登录态
  useEffect(() => {
    setSessionExpiredHandler(() => {
      clearSessionCookie();
      persistLogin(null, null);
    });
    return () => setSessionExpiredHandler(null);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const login = useCallback(async (uname: string, password: string) => {
    // 后端返回 {success, message, data:{address, username}}，需解包 data
    const res = await apiFetch<{
      data?: { address: string; username: string };
      address?: string;
      username?: string;
    }>('/auth/login', {
      method: 'POST',
      body: { username: uname, password },
      skipAuth: true,
    });
    const d = res.data ?? res;
    if (!d?.address) {
      throw new Error(translate('login_response_missing_address', '登录响应缺少账户地址'));
    }
    await persistLogin(d.address, d.username ?? uname);
  }, [persistLogin]);

  const logout = useCallback(async () => {
    try {
      await apiFetch('/auth/logout', { method: 'POST', skipAuth: true });
    } catch {
      // 忽略退出网络错误
    }
    await clearSessionCookie();
    await persistLogin(null, null);
  }, [persistLogin]);

  const setLogin = useCallback(async (addr: string, uname: string) => {
    await persistLogin(addr, uname);
  }, [persistLogin]);

  const value = useMemo<AuthContextValue>(
    () => ({
      loading,
      address,
      username,
      isAuthed: !!address,
      login,
      logout,
      setLogin,
    }),
    [loading, address, username, login, logout, setLogin]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth 必须在 AuthProvider 内使用');
  return ctx;
}