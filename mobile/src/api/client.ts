import Constants from 'expo-constants';
import { Platform } from 'react-native';

/**
 * 后端 API 基地址推导（跨平台）：
 * 1. app.json extra.apiBaseUrl 优先；
 * 2. Expo Go LAN 调试时取 hostUri 的 IP（开发机），拼 :8000；
 * 3. 兜底 Android 10.0.2.2 / iOS 127.0.0.1。
 */
export function deriveBaseURL(): string {
  const extra = (Constants.expoConfig?.extra ?? {}) as Record<string, unknown>;
  const configured = extra.apiBaseUrl as string | undefined;
  if (configured && configured.trim() !== '') {
    return configured.replace(/\/$/, '');
  }

  const hostUri: string | undefined = Constants.expoConfig?.hostUri;
  if (hostUri) {
    const host = hostUri.split(':')[0];
    if (host) {
      return `http://${host}:8000`;
    }
  }

  if (Platform.OS === 'android') {
    return 'http://10.0.2.2:8000';
  }
  return 'http://127.0.0.1:8000';
}

export const apiBaseURL = deriveBaseURL();