import { useRouter } from 'expo-router';
import React, { useState } from 'react';
import { Alert, Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiFetch, errMessage } from '../../../src/api/apiFetch';
import type { WalletInfo, WalletCreated } from '../../../src/api/types';
import { BrandHeader } from '../../../src/components/BrandHeader';
import { SectionHeading } from '../../../src/components/Cond';
import { Button } from '../../../src/components/Button';
import { Empty, Loader } from '../../../src/components/StateView';
import { useWalletInfo } from '../../../src/hooks/data';
import { useWalletAddress } from '../../../src/hooks/walletStore';
import { colors, fonts, radius, spacing } from '../../../src/theme/theme';

function shortAddr(a?: string | null): string {
  if (!a) return '未创建';
  return `${a.slice(0, 6)}…${a.slice(-4)}`;
}

export default function WalletScreen() {
  const router = useRouter();
  const [creating, setCreating] = useState(false);
  const { walletAddress: address, saveWallet } = useWalletAddress();
  const { data, loading, error, refresh } = useWalletInfo(address, !!address);

  const createWallet = async () => {
    setCreating(true);
    try {
      const res = await apiFetch<{ success: boolean; message?: string; data?: WalletCreated }>(
        '/api/geth-wallet',
        { method: 'POST', body: { action: 'ensure_wallet' } }
      );
      if (!res.success) throw new Error(res.message || '创建失败');
      const created = res.data!;
      await saveWallet(created.address);
      Alert.alert('钱包已创建', `地址：${created.address}\n\n请立即保存私钥（仅本次显示）：\n${created.private_key}`, [
        { text: '我知道了' },
      ]);
      if (!address) {
        router.push('/register');
      } else {
        refresh();
      }
    } catch (e) {
      Alert.alert('创建失败', errMessage(e));
    } finally {
      setCreating(false);
    }
  };

  return (
    <SafeAreaView edges={['top', 'left', 'right']} style={styles.safe}>
      <BrandHeader />
      <ScrollView
        refreshControl={<RefreshControl refreshing={false} onRefresh={refresh} tintColor={colors.primary} />}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
      >
        <SectionHeading title="我的钱包" />
        <View style={styles.card}>
          <View style={styles.line}>
            <Text style={styles.label}>地址</Text>
            <Text style={[styles.value, styles.hash]} numberOfLines={2}>{address ?? '未创建'}</Text>
          </View>
          <View style={styles.line}>
            <Text style={styles.label}>注册状态</Text>
            <Text style={styles.value}>{data ? (data.is_registered ? '已注册' : '未注册') : '—'}</Text>
          </View>
          <View style={styles.line}>
            <Text style={styles.label}>余额</Text>
            <Text style={styles.value}>{data ? data.balance : '—'}</Text>
          </View>
          <View style={styles.line}>
            <Text style={styles.label}>累计奖励</Text>
            <Text style={styles.value}>{data ? data.total_rewards : '—'}</Text>
          </View>
          <View style={styles.line}>
            <Text style={styles.label}>邀请人数</Text>
            <Text style={styles.value}>{data ? data.invite_count : '—'}</Text>
          </View>
          <View style={styles.line}>
            <Text style={styles.label}>上游邀请人</Text>
            <Text style={[styles.value, styles.hash]} numberOfLines={2}>{data?.inviter ?? '—'}</Text>
          </View>
        </View>

        {address && !loading && !error ? (
          <>
            <Button title="转账" variant="primary" style={styles.btn} onPress={() => router.push('/transfer')} />
          </>
        ) : null}

        {address ? (
          <>
            <SectionHeading title="我的邀请链接" />
            <View style={styles.card}>
              <Text style={styles.small}>把下方地址发给他人，对方注册时填写即可建立三级推荐奖励：</Text>
              <Text style={[styles.value, styles.hash]} selectable numberOfLines={4}>{address}</Text>
            </View>
          </>
        ) : (
          <>
            <SectionHeading title="创建钱包" />
            <View style={styles.card}>
              <Text style={styles.small}>
                创建钱包后私钥仅本次显示，请务必保存。私钥由后端统一托管签名，转账时输入密码即可。
              </Text>
            </View>
            <Button title={creating ? '创建中…' : '创建钱包'} loading={creating} style={styles.btn} onPress={createWallet} />
            <Button title="注册(上传链)" variant="outline" style={styles.btn} onPress={() => router.push('/register')} />
          </>
        )}

        {address && loading ? <Loader /> : null}
        {address && error ? <Empty text="加载失败" hint={error} /> : null}
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