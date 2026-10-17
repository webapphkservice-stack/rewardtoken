import AsyncStorage from '@react-native-async-storage/async-storage';

const SESSION_KEY = 'irt_session_cookie';

/**
 * Session cookie 管理：后端基于 Laravel session（driver=database），
 * 登录后会话 ID 通过 Set-Cookie 下发，原生 fetch 不会自动持久化，
 * 这里手动读取/保存，并在请求时拼接到 Cookie 头。
 */

export interface SessionCookie {
  name: string;
  value: string;
}

export async function saveSessionCookie(cookieHeader?: string | null): Promise<boolean> {
  if (!cookieHeader) return false;
  const parsed = parseSetCookie(cookieHeader);
  if (!parsed) return false;
  await AsyncStorage.setItem(SESSION_KEY, JSON.stringify(parsed));
  return true;
}

export async function getSessionCookie(): Promise<SessionCookie | null> {
  try {
    const raw = await AsyncStorage.getItem(SESSION_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw) as SessionCookie;
    if (!parsed?.name || !parsed?.value) return null;
    return parsed;
  } catch {
    return null;
  }
}

export async function clearSessionCookie(): Promise<void> {
  await AsyncStorage.removeItem(SESSION_KEY);
}

/** 构造请求用的 Cookie 头，如 "laravel_session=abc123" */
export async function buildCookieHeader(): Promise<string | null> {
  const cookie = await getSessionCookie();
  if (!cookie) return null;
  return `${cookie.name}=${cookie.value}`;
}

/** 从单条 Set-Cookie 头中提取 laravel_session=... */
function parseSetCookie(header: string): SessionCookie | null {
  // Laravel 返回 "laravel_session=xxxx; path=/; httponly; samesite=lax"
  const firstSegment = header.split(';')[0] ?? '';
  const eq = firstSegment.indexOf('=');
  if (eq <= 0) return null;
  const name = firstSegment.slice(0, eq).trim();
  const value = firstSegment.slice(eq + 1).trim();
  if (!name || !value) return null;
  return { name, value };
}