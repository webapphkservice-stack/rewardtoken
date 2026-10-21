import { useRouter } from 'expo-router';
import React, { useState } from 'react';
import { Alert, Linking, Modal, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import Constants from 'expo-constants';
import { BrandHeader } from '../../../src/components/BrandHeader';
import { SectionHeading } from '../../../src/components/Cond';
import { useAuth } from '../../../src/context/auth';
import { useLanguage } from '../../../src/i18n/LanguageContext';
import { colors, fonts, radius, spacing } from '../../../src/theme/theme';

function shortAddr(a?: string | null): string {
  if (!a) return '未登录';
  return `${a.slice(0, 8)}…${a.slice(-6)}`;
}

export default function MineScreen() {
  const router = useRouter();
  const { address, username, isAuthed, logout } = useAuth();
  const { locale, setLocale, labels, t } = useLanguage();
  const [langVisible, setLangVisible] = useState(false);

  const currentLabel = labels.find((l) => l.code === locale);
  const currentNative = currentLabel?.native ?? locale;

  return (
    <SafeAreaView edges={['top', 'left', 'right']} style={styles.safe}>
      <BrandHeader />
      <ScrollView contentContainerStyle={styles.list} showsVerticalScrollIndicator={false}>
        <SectionHeading title={t('personal_center', '个人中心')} />

        <View style={styles.userCard}>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>{username?.[0]?.toUpperCase() ?? '?'}</Text>
          </View>
          <View style={styles.userInfo}>
            <Text style={styles.username}>{username ?? t('not_logged_in', '未登录')}</Text>
            <Text style={styles.address} numberOfLines={1}>
              {isAuthed ? shortAddr(address) : t('login_to_buy', '登录后可购买代币与查看订单')}
            </Text>
          </View>
        </View>

        {!isAuthed ? (
          <Pressable style={styles.row} onPress={() => router.push('/login')}>
            <Text style={styles.rowLabel}>{t('login', '登录')}</Text>
            <Text style={styles.arrow}>›</Text>
          </Pressable>
        ) : (
          <>
            <Pressable style={styles.row} onPress={() => router.push('/buy')}>
              <Text style={styles.rowLabel}>{t('buy_token', '购买代币')}</Text>
              <Text style={styles.arrow}>›</Text>
            </Pressable>
            <Pressable style={styles.row} onPress={() => router.push('/orders')}>
              <Text style={styles.rowLabel}>{t('my_orders', '我的订单')}</Text>
              <Text style={styles.arrow}>›</Text>
            </Pressable>
          </>
        )}

        {/* 语言选择器 — 未登录也能改 */}
        <Pressable style={styles.row} onPress={() => setLangVisible(true)}>
          <Text style={styles.rowLabel}>{t('select_language', '选择语言')}</Text>
          <Text style={styles.rowValue}>{currentNative} ›</Text>
        </Pressable>

        {/* 法律文档 — 所有用户可见 */}
        {(() => {
          const base = Constants.expoConfig?.extra?.apiBaseUrl ?? 'https://token.daliao.ai';
          return (
            <>
              <Pressable style={styles.row} onPress={() => Linking.openURL(`${base}/terms`)}>
                <Text style={styles.rowLabel}>{t('terms_of_service', '服务条款')}</Text>
                <Text style={styles.arrow}>›</Text>
              </Pressable>
              <Pressable style={styles.row} onPress={() => Linking.openURL(`${base}/privacy`)}>
                <Text style={styles.rowLabel}>{t('privacy_policy', '隐私政策')}</Text>
                <Text style={styles.arrow}>›</Text>
              </Pressable>
            </>
          );
        })()}

        {isAuthed ? (
          <Pressable
            style={styles.row}
            onPress={async () => {
              await logout();
              router.replace('/(app)/(tabs)/index');
            }}
          >
            <Text style={[styles.rowLabel, { color: colors.danger }]}>{t('logout', '退出登录')}</Text>
            <Text style={styles.arrow}>›</Text>
          </Pressable>
        ) : null}
      </ScrollView>

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
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.bg },
  list: { paddingHorizontal: spacing.md, paddingBottom: spacing.xl },
  userCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    padding: spacing.md,
    marginBottom: spacing.md,
  },
  avatar: {
    width: 52,
    height: 52,
    borderRadius: 26,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: { fontFamily: fonts.heading, fontSize: 22, color: '#fff' },
  userInfo: { flex: 1, marginLeft: spacing.md, minWidth: 0 },
  username: { fontFamily: fonts.bodySemi, fontSize: 16, color: colors.text },
  address: { fontFamily: fonts.body, fontSize: 13, color: colors.muted, marginTop: 3 },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    padding: spacing.md,
    marginBottom: spacing.sm,
  },
  rowLabel: { fontFamily: fonts.bodySemi, fontSize: 15, color: colors.text },
  rowValue: { fontFamily: fonts.body, fontSize: 15, color: colors.muted },
  arrow: { fontFamily: fonts.heading, fontSize: 18, color: colors.muted },
  // 模态框
  modalBackdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
  modalSheet: {
    backgroundColor: colors.surface,
    borderTopLeftRadius: radius.lg,
    borderTopRightRadius: radius.lg,
    padding: spacing.lg,
    paddingBottom: spacing.xl,
    maxHeight: '72%',
  },
  modalTitle: { fontFamily: fonts.heading, fontSize: 18, color: colors.text, marginBottom: spacing.md, textAlign: 'center' },
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