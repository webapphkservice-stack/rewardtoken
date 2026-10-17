import AsyncStorage from '@react-native-async-storage/async-storage';
import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
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
            const res = await apiFetch<{ logged_in: boolean; address?: string; username?: string }>(
              '/auth/status',
              { timeout: 10000 }
            );
            if (res.logged_in && res.address) {
              await persistLogin(res.address, res.username ?? u);
            } else {
              await persistLogin(null, null);
            }
          } catch {
            // 后端不可达时保留本地登录态，不强制登出
          }
        }
      } catch {
        // 忽略恢复错误
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
    const res = await apiFetch<{ address: string; username: string }>('/auth/login', {
      method: 'POST',
      body: { username: uname, password },
      skipAuth: true,
    });
    await persistLogin(res.address, res.username);
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