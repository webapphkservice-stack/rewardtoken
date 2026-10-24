import { useRouter } from 'expo-router';
import React, { useState } from 'react';
import { Modal, Pressable, StyleSheet, Text, View } from 'react-native';
import { useAuth } from '../context/auth';
import { useLanguage, type Locale } from '../i18n/LanguageContext';
import { colors, fonts, radius, spacing } from '../theme/theme';

/** 语言按钮上的紧凑缩写（native 全称太长会挤爆顶栏） */
const SHORT_LABELS: Record<Locale, string> = {
  'zh-cn': '中',
  'zh-tw': '繁',
  en: 'EN',
  ja: '日',
  ko: '한',
  ms: 'MS',
  th: '泰',
  vi: 'VI',
};

interface TopBarProps {
  /** 页面标题；缺省时显示品牌名 */
  title?: string;
  /** 是否显示返回按钮（Stack 二级页为 true，Tabs 页为 false） */
  showBack?: boolean;
}

/**
 * 全局统一顶部栏：
 * ① 页面标题 + 返回按钮  ② 登录状态 / 头像入口  ③ 语言切换（8 语言弹层，持久化）
 */
export function TopBar({ title, showBack = false }: TopBarProps) {
  const router = useRouter();
  const { username, isAuthed } = useAuth();
  const { locale, setLocale, labels, t } = useLanguage();
  const [langVisible, setLangVisible] = useState(false);

  const onAccountPress = () => {
    if (isAuthed) {
      router.push('/(app)/(tabs)/mine');
    } else {
      router.push('/login');
    }
  };

  return (
    <View style={styles.bar}>
      <View style={styles.left}>
        {showBack ? (
          <Pressable onPress={() => router.back()} hitSlop={12} style={styles.backBtn}>
            <Text style={styles.backText}>‹</Text>
          </Pressable>
        ) : null}
        <Text style={styles.title} numberOfLines={1}>
          {title ?? 'Invite Reward Token'}
        </Text>
      </View>

      <View style={styles.right}>
        {/* 语言切换 */}
        <Pressable onPress={() => setLangVisible(true)} hitSlop={8} style={styles.langBtn}>
          <Text style={styles.langIcon}>A文</Text>
          <Text style={styles.langShort}>{SHORT_LABELS[locale] ?? locale}</Text>
        </Pressable>

        {/* 登录状态 / 头像入口 */}
        <Pressable onPress={onAccountPress} hitSlop={8}>
          {isAuthed ? (
            <View style={styles.avatar}>
              <Text style={styles.avatarText}>{username?.[0]?.toUpperCase() ?? '?'}</Text>
            </View>
          ) : (
            <View style={styles.loginPill}>
              <Text style={styles.loginText}>{t('login', '登录')}</Text>
            </View>
          )}
        </Pressable>
      </View>

      {/* 语言选择弹层 */}
      <Modal visible={langVisible} animationType="slide" transparent onRequestClose={() => setLangVisible(false)}>
        <Pressable style={styles.modalBackdrop} onPress={() => setLangVisible(false)}>
          <Pressable style={styles.modalSheet} onPress={() => {}}>
            <Text style={styles.modalTitle}>{t('select_language', '选择语言')}</Text>
            {labels.map((l) => {
              const selected = l.code === locale;
              return (
                <Pressable
                  key={l.code}
                  style={[styles.langItem, selected && { backgroundColor: colors.primarySoft }]}
                  onPress={async () => {
                    await setLocale(l.code);
                    setLangVisible(false);
                  }}
                >
                  <Text style={[styles.langNative, selected && { color: colors.primary }]}>{l.native}</Text>
                  <Text style={styles.langLabel}>{l.label}</Text>
                  {selected ? <Text style={styles.langCheck}>✓</Text> : null}
                </Pressable>
              );
            })}
          </Pressable>
        </Pressable>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  bar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    backgroundColor: colors.bg,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: colors.border,
  },
  left: { flexDirection: 'row', alignItems: 'center', flex: 1, minWidth: 0, marginRight: spacing.sm },
  backBtn: {
    width: 32,
    height: 32,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: spacing.xs,
  },
  backText: { fontSize: 24, fontFamily: fonts.heading, color: colors.text, lineHeight: 26 },
  title: { fontFamily: fonts.heading, fontSize: 18, color: colors.text, flexShrink: 1 },
  right: { flexDirection: 'row', alignItems: 'center' },
  langBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: spacing.sm,
    paddingVertical: 5,
    borderRadius: radius.sm,
    borderWidth: 1,
    borderColor: colors.border,
    marginRight: spacing.sm,
  },
  langIcon: { fontFamily: fonts.bodySemi, fontSize: 12, color: colors.primary, marginRight: 4 },
  langShort: { fontFamily: fonts.bodySemi, fontSize: 12, color: colors.text },
  avatar: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: { fontFamily: fonts.heading, fontSize: 15, color: '#fff' },
  loginPill: {
    paddingHorizontal: spacing.md,
    paddingVertical: 6,
    borderRadius: radius.sm,
    backgroundColor: colors.primary,
  },
  loginText: { fontFamily: fonts.bodySemi, fontSize: 13, color: '#fff' },
  // 语言弹层（与"我的"页风格一致）
  modalBackdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
  modalSheet: {
    backgroundColor: colors.surface,
    borderTopLeftRadius: radius.lg,
    borderTopRightRadius: radius.lg,
    padding: spacing.lg,
    paddingBottom: spacing.xl,
    maxHeight: '72%',
  },
  modalTitle: {
    fontFamily: fonts.heading,
    fontSize: 18,
    color: colors.text,
    marginBottom: spacing.md,
    textAlign: 'center',
  },
  langItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: spacing.sm + 4,
    paddingHorizontal: spacing.md,
    borderRadius: radius.sm,
    marginBottom: 2,
  },
  langNative: { fontFamily: fonts.bodySemi, fontSize: 16, color: colors.text, minWidth: 120 },
  langLabel: { fontFamily: fonts.body, fontSize: 13, color: colors.muted, flex: 1 },
  langCheck: { fontFamily: fonts.heading, fontSize: 18, color: colors.primary, marginLeft: 8 },
});
