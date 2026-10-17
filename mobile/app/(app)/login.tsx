import { useRouter } from 'expo-router';
import React, { useState } from 'react';
import { Alert, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Button, Spacer } from '../../src/components/Button';
import { Field } from '../../src/components/Field';
import { BackBtn, BrandHeader } from '../../src/components/BrandHeader';
import { useAuth } from '../../src/context/auth';
import { colors, spacing, typography } from '../../src/theme/theme';

export default function LoginScreen() {
  const router = useRouter();
  const { login } = useAuth();
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const onSubmit = async () => {
    if (!username.trim() || !password) {
      Alert.alert('提示', '请输入用户名和密码');
      return;
    }
    setSubmitting(true);
    try {
      await login(username.trim(), password);
      router.back();
    } catch (e) {
      Alert.alert('登录失败', e instanceof Error ? e.message : '请稍后重试');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <SafeAreaView edges={['top', 'left', 'right']} style={styles.safe}>
      <BrandHeader right={<BackBtn onPress={() => router.back()} />} />
      <KeyboardAvoidingView
        style={styles.flex}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      >
        <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
          <Text style={typography.title}>登录</Text>
          <Text style={styles.sub}>输入注册时设置的用户名与密码</Text>

          <Spacer h={spacing.lg} />

          <Field
            label="用户名"
            value={username}
            onChangeText={setUsername}
            placeholder="请输入用户名"
            autoCapitalize="none"
          />
          <Field
            label="密码"
            value={password}
            onChangeText={setPassword}
            placeholder="请输入密码"
            secureTextEntry
          />

          <Spacer h={spacing.md} />
          <Button title="登录" onPress={onSubmit} loading={submitting} />
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.bg },
  flex: { flex: 1 },
  scroll: { paddingHorizontal: spacing.md, paddingBottom: spacing.xl },
  sub: { fontFamily: 'OpenSans_400Regular', fontSize: 14, color: colors.muted, marginTop: spacing.sm },
});