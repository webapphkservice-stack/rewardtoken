import { useRouter, useFocusEffect } from 'expo-router';
import React, { useCallback, useState } from 'react';
import { Alert, Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiFetch, errMessage } from '../../../src/api/apiFetch';
import type { WalletInfo, WalletCreated } from '../../../src/api/types';
import { TopBar } from '../../../src/components/TopBar';
import { SectionHeading } from '../../../src/components/Cond';
import { Button } from '../../../src/components/Button';
import { Empty, Loader } from '../../../src/components/StateView';
import { useWalletInfo } from '../../../src/hooks/data';
import { useWalletAddress } from '../../../src/hooks/walletStore';
import { useAuth } from '../../../src/context/auth';
import { useLanguage } from '../../../src/i18n/LanguageContext';
import { colors, fonts, radius, spacing } from '../../../src/theme/theme';

export default function WalletScreen() {
  const router = useRouter();
  const [creating, setCreating] = useState(false);
  const { address: authAddress } = useAuth();
  const { walletAddress: storedWallet, saveWallet } = useWalletAddress();
  const { t } = useLanguage();
  // 登录地址优先（与 Web 端一致），本地钱包存储兜底
  const address = authAddress ?? storedWallet;
  const { data, loading, error, refresh } = useWalletInfo(address, !!address);

  // 每次切到钱包页自动刷新，避免奖励/余额等数据过期
  useFocusEffect(
    useCallback(() => {
      if (address) refresh();
    }, [address, refresh])
  );

  const createWallet = async () => {
    setCreating(true);
    try {
      const res = await apiFetch<{ success: boolean; message?: string; data?: WalletCreated }>(
        '/api/geth-wallet',
        { method: 'POST', body: { action: 'ensure_wallet' } }
      );
      if (!res.success) throw new Error(res.message || t('wallet_create_failed', '创建失败'));
      const created = res.data!;
      await saveWallet(created.address);
      Alert.alert(
        t('wallet_created_title', '钱包已创建'),
        `${t('wallet_created_hint', '请立即保存私钥（仅本次显示）：')}\n${created.private_key}`,
        [{ text: t('i_know', '我知道了') }]
      );
      if (!address) {
        router.push('/register');
      } else {
        refresh();
      }
    } catch (e) {
      Alert.alert(t('wallet_create_failed', '创建失败'), errMessage(e));
    } finally {
      setCreating(false);
    }
  };

  const regStatusLabel = data ? (data.is_registered ? t('registered', '已注册') : t('not_registered', '未注册')) : '—';

  return (
    <SafeAreaView edges={['top', 'left', 'right']} style={styles.safe}>
      <TopBar title={t('my_wallet', '我的钱包')} />
      <ScrollView
        refreshControl={<RefreshControl refreshing={false} onRefresh={refresh} tintColor={colors.primary} />}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
      >
        <SectionHeading title={t('my_wallet', '我的钱包')} />
        <View style={styles.card}>
          <View style={styles.line}>
            <Text style={styles.label}>{t('address', '地址')}</Text>
            <Text style={[styles.value, styles.hash]} numberOfLines={2}>{address ?? t('not_created', '未创建')}</Text>
          </View>
          <View style={styles.line}>
            <Text style={styles.label}>{t('register_status', '注册状态')}</Text>
            <Text style={styles.value}>{regStatusLabel}</Text>
          </View>
          <View style={styles.line}>
            <Text style={styles.label}>{t('balance', '余额')}</Text>
            <Text style={styles.value}>{data ? data.balance : '—'}</Text>
          </View>
          <View style={styles.line}>
            <Text style={styles.label}>{t('total_rewards', '累计奖励')}</Text>
            <Text style={styles.value}>{data ? data.total_rewards : '—'}</Text>
          </View>
          <View style={styles.line}>
            <Text style={styles.label}>{t('invite_count', '邀请人数')}</Text>
            <Text style={styles.value}>{data ? data.invite_count : '—'}</Text>
          </View>
          <View style={styles.line}>
            <Text style={styles.label}>{t('upstream_inviter', '上游邀请人')}</Text>
            <Text style={[styles.value, styles.hash]} numberOfLines={2}>{data?.inviter ?? '—'}</Text>
          </View>
        </View>

        {address && !loading && !error ? (
          <>
            <Button title={t('transfer', '转账')} variant="primary" style={styles.btn} onPress={() => router.push('/transfer')} />
          </>
        ) : null}

        {address ? (
          <>
            <SectionHeading title={t('invite_link', '我的邀请链接')} />
            <View style={styles.card}>
              <Text style={styles.small}>{t('invite_link_hint', '把下方地址发给他人，对方注册时填写即可建立三级推荐奖励：')}</Text>
              <Text style={[styles.value, styles.hash]} selectable numberOfLines={4}>{address}</Text>
            </View>
          </>
        ) : (
          <>
            <SectionHeading title={t('create_wallet', '创建钱包')} />
            <View style={styles.card}>
              <Text style={styles.small}>{t('wallet_hint', '创建钱包后私钥仅本次显示，请务必保存。私钥由后端统一托管签名，转账时输入密码即可。')}</Text>
            </View>
            <Button title={creating ? t('creating', '创建中…') : t('create_wallet', '创建钱包')} loading={creating} style={styles.btn} onPress={createWallet} />
            <Button title={t('register_on_chain', '注册(上传链)')} variant="outline" style={styles.btn} onPress={() => router.push('/register')} />
          </>
        )}

        {address && loading ? <Loader /> : null}
        {address && error ? <Empty text={t('load_failed', '加载失败')} hint={error} /> : null}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.bg },
  list: { paddingHorizontal: spacing.md, paddingBottom: spacing.xl },
  card: {
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    padding: spacing.md,
    marginBottom: spacing.md,
  },
  line: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', paddingVertical: 6 },
  label: { fontFamily: fonts.body, fontSize: 13, color: colors.muted },
  value: { fontFamily: fonts.bodySemi, fontSize: 13, color: colors.text },
  hash: { flex: 1, marginLeft: spacing.md, textAlign: 'right' },
  small: { fontFamily: fonts.body, fontSize: 13, color: colors.muted, lineHeight: 20 },
  btn: { marginBottom: spacing.md },
});
