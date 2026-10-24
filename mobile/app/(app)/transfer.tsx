import React, { useState } from 'react';
import { Alert, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import { apiFetch, errMessage } from '../../src/api/apiFetch';
import type { WalletInfo, TxResult } from '../../src/api/types';
import { TopBar } from '../../src/components/TopBar';
import { Button, Spacer } from '../../src/components/Button';
import { Field } from '../../src/components/Field';
import { useWalletInfo } from '../../src/hooks/data';
import { useWalletAddress } from '../../src/hooks/walletStore';
import { useLanguage } from '../../src/i18n/LanguageContext';
import { colors, fonts, radius, spacing, typography } from '../../src/theme/theme';

export default function TransferScreen() {
  const router = useRouter();
  const [mode, setMode] = useState<'send' | 'receive'>('send');
  const { walletAddress } = useWalletAddress();
  const { data } = useWalletInfo(walletAddress, !!walletAddress);
  const { t } = useLanguage();

  const [to, setTo] = useState('');
  const [amount, setAmount] = useState('');
  const [password, setPassword] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const onSend = async () => {
    if (!walletAddress) {
      Alert.alert(t('demo', '提示'), t('please_create_wallet_first', '请先在钱包页创建钱包'));
      return;
    }
    if (!to.trim() || !amount || !password) {
      Alert.alert(t('demo', '提示'), t('enter_all_fields', '请输入收款地址、金额和密码'));
      return;
    }
    setSubmitting(true);
    try {
      const res = await apiFetch<{ success: boolean; message?: string; data?: TxResult }>('/api/geth-wallet', {
        method: 'POST',
        body: { action: 'send', from: walletAddress, to: to.trim(), amount, password },
      });
      if (!res.success) throw new Error(res.message || t('transfer_failed', '转账失败'));
      Alert.alert(
        t('send_success', '转账成功'),
        `${t('tx_hash_label', '交易哈希：')}\n${res.data?.tx_hash}`,
        [{ text: t('i_know', '好的') }]
      );
      setTo('');
      setAmount('');
      setPassword('');
    } catch (e) {
      Alert.alert(t('transfer_failed', '转账失败'), errMessage(e));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <SafeAreaView edges={['top', 'left', 'right']} style={styles.safe}>
      <TopBar title={t('transfer', '转账')} showBack />
      <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
          <View style={styles.tabs}>
            <Tab label={t('send', '发送')} active={mode === 'send'} onPress={() => setMode('send')} />
            <Tab label={t('receive', '接收')} active={mode === 'receive'} onPress={() => setMode('receive')} />
          </View>

          {mode === 'receive' ? (
            <View style={styles.receiveBox}>
              <Text style={styles.receiveLabel}>{t('your_address', '您的收款地址')}</Text>
              <Text style={styles.receiveAddr} selectable numberOfLines={6}>
                {walletAddress ?? t('please_create_wallet_first', '请先在钱包页创建钱包')}
              </Text>
              <Text style={styles.receiveHint}>{t('receive_hint', '把该地址发给对方，对方选择发送并填入即可转给您。')}</Text>
            </View>
          ) : (
            <>
              <View style={styles.balanceBox}>
                <Text style={styles.balanceLabel}>{t('available_balance', '当前余额')}</Text>
                <Text style={styles.balanceValue}>{data?.balance ?? '—'}</Text>
              </View>
              <Field
                label={t('recipient_address', '收款地址')}
                value={to}
                onChangeText={setTo}
                placeholder="0x…"
                autoCapitalize="none"
              />
              <Field
                label={t('amount', '金额')}
                value={amount}
                onChangeText={setAmount}
                placeholder={t('amount_hint', '转入净额')}
                keyboardType="number-pad"
              />
              <Field
                label={t('transaction_password', '交易密码')}
                value={password}
                onChangeText={setPassword}
                placeholder={t('transaction_password_hint', '注册时设置的密码')}
                secureTextEntry
              />
              <Spacer h={spacing.md} />
              <Button title={t('send', '发送')} onPress={onSend} loading={submitting} />
            </>
          )}
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

function Tab({ label, active, onPress }: { label: string; active: boolean; onPress: () => void }) {
  return (
    <Text style={[styles.tab, active && styles.tabActive]} onPress={onPress}>
      {label}
    </Text>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.bg },
  flex: { flex: 1 },
  scroll: { paddingHorizontal: spacing.md, paddingBottom: spacing.xl },
  tabs: {
    flexDirection: 'row',
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    padding: 4,
    marginBottom: spacing.md,
  },
  tab: {
    flex: 1,
    textAlign: 'center',
    paddingVertical: 10,
    fontFamily: fonts.bodySemi,
    fontSize: 14,
    color: colors.muted,
    borderRadius: radius.sm,
  },
  tabActive: { backgroundColor: colors.bg, color: colors.primary },
  balanceBox: {
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    padding: spacing.md,
    marginBottom: spacing.md,
  },
  balanceLabel: { fontFamily: fonts.body, fontSize: 13, color: colors.muted },
  balanceValue: { fontFamily: fonts.heading, fontSize: 26, color: colors.primary, marginTop: 6 },
  receiveBox: {
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    padding: spacing.md,
  },
  receiveLabel: { fontFamily: fonts.bodySemi, fontSize: 14, color: colors.text, marginBottom: 10 },
  receiveAddr: { fontFamily: fonts.body, fontSize: 13, color: colors.text, lineHeight: 22 },
  receiveHint: { fontFamily: fonts.body, fontSize: 13, color: colors.muted, marginTop: 12, lineHeight: 20 },
});
