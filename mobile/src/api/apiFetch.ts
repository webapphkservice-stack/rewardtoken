import { apiBaseURL } from './client';
import { buildCookieHeader, saveSessionCookie } from './session';

export type ApiResponse<T = unknown> = {
  success: boolean;
  message?: string;
  data?: T;
}; // 部分接口（/api/status 等）成功时还会平铺额外字段，业务层做类型断言扩展

/** 全局会话回调：会话失效时由 AuthContext 注入，用于清理登录态。 */
let onSessionExpired: (() => void) | null = null;
export function setSessionExpiredHandler(handler: (() => void) | null): void {
  onSessionExpired = handler;
}

export class ApiRequestError extends Error {
  status: number;
  constructor(status: number, message: string) {
    super(message);
    this.status = status;
  }
}

interface FetchOptions {
  method?: 'GET' | 'POST';
  body?: Record<string, unknown> | string;
  timeout?: number;
  /** 显式关闭登录态失效回调（避免登录/登出自身触发循环） */
  skipAuth?: boolean;
}

/**
 * 统一请求：自动携带 session Cookie、解析 Set-Cookie 回写、超时控制、错误解析。
 * 后端 JSON 格式：{success: bool, message?: string, data?: ...}（或平铺字段）。
 */
export async function apiFetch<T = unknown>(
  path: string,
  options: FetchOptions = {}
): Promise<T> {
  const { method = 'GET', body, timeout = 20000, skipAuth = false } = options;

  const headers: Record<string, string> = {};
  const cookie = await buildCookieHeader();
  if (cookie) headers.Cookie = cookie;
  if (body && typeof body === 'object') headers['Content-Type'] = 'application/json';

  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeout);

  let raw: Response;
  try {
    raw = await fetch(`${apiBaseURL}${path}`, {
      method,
      headers,
      body: body ? (typeof body === 'string' ? body : JSON.stringify(body)) : undefined,
      signal: controller.signal,
      credentials: 'include',
    });
  } catch (e) {
    clearTimeout(timer);
    throw new ApiRequestError(0, '网络异常，请检查网络或后端服务是否可用');
  }
  clearTimeout(timer);

  // 会话 cookie 回写（登录时后端下发 Set-Cookie）
  const setCookie = raw.headers.get('set-cookie') ?? '';
  if (setCookie) {
    await saveSessionCookie(setCookie);
  }

  const text = await raw.text();
  let json: Record<string, unknown> | null = null;
  if (text) {
    try {
      json = JSON.parse(text);
    } catch {
      json = null;
    }
  }

  if (raw.status === 401 && !skipAuth) {
    onSessionExpired?.();
  }

  // 后端业务失败：success === false
  if (json && json.success === false) {
    const msg = (json.message as string) || '请求失败';
    if (raw.status === 401 && !skipAuth) {
      onSessionExpired?.();
    }
    throw new ApiRequestError(raw.status, msg);
  }

  if (!raw.ok) {
    throw new ApiRequestError(raw.status, '请求失败');
  }

  return (json ?? {}) as T;
}

/** 帮助函数：读取请求错误消息 */
export function errMessage(e: unknown): string {
  if (e instanceof ApiRequestError) return e.message;
  if (e instanceof Error) return e.message;
  return String(e);
}