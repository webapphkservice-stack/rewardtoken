import { useRouter } from 'expo-router';
import React, { useState } from 'react';
import { Alert, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiFetch, errMessage } from '../../src/api/apiFetch';
import type { TxResult } from '../../src/api/types';
import { BackBtn, BrandHeader } from '../../src/components/BrandHeader';
import { Button, Spacer } from '../../src/components/Button';
import { Field } from '../../src/components/Field';
import { useWalletAddress } from '../../src/hooks/walletStore';
import { colors, fonts, spacing, typography } from '../../src/theme/theme';

export default function RegisterScreen() {
  const router = useRouter();
  const { walletAddress } = useWalletAddress();
  const [inviter, setInviter] = useState('');
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const onSubmit = async () => {
    if (!walletAddress) {
      Alert.alert('提示', '请先在钱包页创建钱包');
      return;
    }
    if (!username.trim() || !password) {
      Alert.alert('提示', '请输入用户名和密码');
      return;
    }
    setSubmitting(true);
    try {
      const res = await apiFetch<{ success: boolean; message?: string; data?: TxResult }>('/api/geth-wallet', {
        method: 'POST',
        body: {
          action: 'register',
          from: walletAddress,
          inviter: inviter.trim() || '0x0000000000000000000000000000000000000000',
          password,
          username: username.trim(),
        },
      });
      if (!res.success) throw new Error(res.message || '注册失败');
      Alert.alert('注册成功', `已上链，交易哈希：\n${res.data?.tx_hash}`, [{ text: '好的' }]);
      router.back();
    } catch (e) {
      Alert.alert('注册失败', errMessage(e));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <SafeAreaView edges={['top', 'left', 'right']} style={styles.safe}>
      <BrandHeader right={<BackBtn onPress={() => router.back()} />} />
      <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
          <Text style={typography.title}>注册上链</Text>
          <Text style={styles.sub}>填写信息调用合约 register() 建立邀请关系</Text>

          <Spacer h={spacing.lg} />

          <View style={styles.addrBox}>
            <Text style={styles.addrLabel}>当前钱包</Text>
            <Text style={styles.addrValue} selectable numberOfLines={3}>{walletAddress ?? '请先在钱包页创建'}</Text>
          </View>

          <Field label="用户名" value={username} onChangeText={setUsername} placeholder="设置用户名（用于登录）" autoCapitalize="none" />
          <Field label="密码" value={password} onChangeText={setPassword} placeholder="设置密码（用于登录与转账）" secureTextEntry />
          <Field label="邀请人地址（可选）" value={inviter} onChangeText={setInviter} placeholder="填写上级邀请人的钱包地址" autoCapitalize="none" />

          <Spacer h={spacing.md} />
          <Button title="注册并上链" onPress={onSubmit} loading={submitting} />
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.bg },
  flex: { flex: 1 },
  scroll: { paddingHorizontal: spacing.md, paddingBottom: spacing.xl },
  sub: { fontFamily: fonts.body, fontSize: 14, color: colors.muted, marginTop: spacing.sm },
  addrBox: {
    backgroundColor: colors.surface,
    borderRadius: 10,
    padding: spacing.md,
    marginBottom: spacing.md,
  },
  addrLabel: { fontFamily: fonts.bodySemi, fontSize: 13, color: colors.muted, marginBottom: 6 },
  addrValue: { fontFamily: fonts.body, fontSize: 13, color: colors.text, lineHeight: 20 },
});