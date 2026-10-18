import AsyncStorage from '@react-native-async-storage/async-storage';
import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';

import zhCN from './zh-cn.json';
import zhTW from './zh-tw.json';
import en from './en.json';
import ja from './ja.json';
import ko from './ko.json';
import ms from './ms.json';
import th from './th.json';
import vi from './vi.json';

export type Locale = 'zh-cn' | 'zh-tw' | 'en' | 'ja' | 'ko' | 'ms' | 'th' | 'vi';

export const LOCALES: { code: Locale; label: string; native: string }[] = [
  { code: 'zh-cn', label: 'Chinese (Simplified)', native: '简体中文' },
  { code: 'zh-tw', label: 'Chinese (Traditional)', native: '繁體中文' },
  { code: 'en', label: 'English', native: 'English' },
  { code: 'ja', label: 'Japanese', native: '日本語' },
  { code: 'ko', label: 'Korean', native: '한국어' },
  { code: 'ms', label: 'Malay', native: 'Bahasa Melayu' },
  { code: 'th', label: 'Thai', native: 'ไทย' },
  { code: 'vi', label: 'Vietnamese', native: 'Tiếng Việt' },
];

const STORAGE_KEY = 'irt.language';
const DEFAULT_LOCALE: Locale = 'zh-cn';

type Dict = Record<string, string>;

// Metro 要求静态 import，不能用动态 require
const ALL_DICTS: Record<Locale, Dict> = {
  'zh-cn': zhCN as Dict,
  'zh-tw': zhTW as Dict,
  en: en as Dict,
  ja: ja as Dict,
  ko: ko as Dict,
  ms: ms as Dict,
  th: th as Dict,
  vi: vi as Dict,
};

/**
 * 当前全局 locale，由 LanguageProvider 在 locale 变化时同步更新。
 * 独立于 React 组件的模块（apiFetch / hooks / context）可通过 translate() 读取。
 */
let _currentLocale: Locale = DEFAULT_LOCALE;

/**
 * 模块级翻译函数（非组件、不能用 hooks 的地方使用）。
 * 优先用 LanguageProvider 当前 locale 的字典，找不到时 fallback。
 */
export function translate(key: string, fallback?: string, params?: Record<string, string | number>): string {
  const dict = ALL_DICTS[_currentLocale] ?? ALL_DICTS[DEFAULT_LOCALE];
  const raw = dict[key] ?? fallback ?? `[${key}]`;
  if (params) {
    return raw.replace(/\{(\w+)\}/g, (_, v) => (params[v] ?? `{${v}}`) as string);
  }
  return raw;
}

interface Ctx {
  locale: Locale;
  setLocale: (l: Locale) => Promise<void>;
  /**
   * 获取翻译文案。
   * @param key   翻译 key（JSON 中的属性名）
   * @param fallback 当 key 不存在时的兜底文案（也作为默认值直接显示）
   * @param params  可选的占位符替换对象，例如 `t('buy_title', '购买 {symbol}', { symbol: 'IRT' })`
   */
  t: (key: string, fallback?: string, params?: Record<string, string | number>) => string;
  labels: typeof LOCALES;
}

const LanguageCtx = createContext<Ctx | null>(null);

export function LanguageProvider({ children }: { children: React.ReactNode }) {
  const [locale, setLocaleState] = useState<Locale>(DEFAULT_LOCALE);

  // 启动时从 AsyncStorage 恢复 locale
  useEffect(() => {
    (async () => {
      try {
        const saved = await AsyncStorage.getItem(STORAGE_KEY);
        if (saved && LOCALES.find((l) => l.code === saved)) {
          setLocaleState(saved as Locale);
        }
      } catch {
        // 忽略
      }
    })();
  }, []);

  // 每次 locale 变化，同步到模块级变量（供 translate() 读取）
  useEffect(() => {
    _currentLocale = locale;
  }, [locale]);

  const setLocale = useCallback(async (l: Locale) => {
    setLocaleState(l);
    try {
      await AsyncStorage.setItem(STORAGE_KEY, l);
    } catch {
      // 忽略
    }
  }, []);

  const t = useCallback(
    (key: string, fallback?: string, params?: Record<string, string | number>) => {
      const dict = ALL_DICTS[locale] ?? ALL_DICTS[DEFAULT_LOCALE];
      const raw = dict[key] ?? fallback ?? `[${key}]`;
      if (params) {
        return raw.replace(/\{(\w+)\}/g, (_, v) => (params[v] ?? `{${v}}`) as string);
      }
      return raw;
    },
    [locale]
  );

  const value = useMemo<Ctx>(
    () => ({ locale, setLocale, t, labels: LOCALES }),
    [locale, setLocale, t]
  );

  return <LanguageCtx.Provider value={value}>{children}</LanguageCtx.Provider>;
}

export function useLanguage(): Ctx {
  const ctx = useContext(LanguageCtx);
  if (!ctx) throw new Error('useLanguage 必须在 LanguageProvider 内使用');
  return ctx;
}

/** 便捷 hook：useTranslation = useLanguage().t */
export function useTranslation() {
  return useLanguage().t;
}
