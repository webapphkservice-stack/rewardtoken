import AsyncStorage from '@react-native-async-storage/async-storage';

const SESSION_KEY = 'irt_session_cookie';
/** Laravel session cookie 名，取自 config/session.php 的 'driver' 相关前缀 */
const SESSION_COOKIE_NAME = 'laravel-session';

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

/**
 * 从 HTTP 响应的 Set-Cookie 头中提取 laravel-session=...
 * 注意 Laravel 同时下发 XSRF-TOKEN，我们只关心真正的 session cookie。
 * Set-Cookie 可能包含多个 cookie（逗号分隔的 header 或 raw headers 里多条 Set-Cookie）。
 */
function parseSetCookie(header: string): SessionCookie | null {
  if (!header) return null;
  // header 可能包含多条 Set-Cookie（逗号分隔），逐一解析找 laravel-session
  const segments = header.split(',');
  for (const seg of segments) {
    const firstSegment = seg.split(';')[0] ?? '';
    const eq = firstSegment.indexOf('=');
    if (eq <= 0) continue;
    const name = firstSegment.slice(0, eq).trim();
    const value = firstSegment.slice(eq + 1).trim();
    if (name === SESSION_COOKIE_NAME && name && value) {
      return { name, value };
    }
  }
  // 兜底：没找到 laravel-session 就返回 null，避免存错 XSRF-TOKEN
  return null;
}