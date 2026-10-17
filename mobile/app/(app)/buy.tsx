import * as Linking from 'expo-linking';
import { useRouter } from 'expo-router';
import React, { useState } from 'react';
import { Alert, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiFetch, errMessage } from '../../src/api/apiFetch';
import type { BuyOrderData } from '../../src/api/types';
import { BackBtn, BrandHeader } from '../../src/components/BrandHeader';
import { Button, Spacer } from '../../src/components/Button';
import { useAuth } from '../../src/context/auth';
import { colors, fonts, radius, spacing, typography } from '../../src/theme/theme';

export default function BuyScreen() {
  const router = useRouter();
  const { isAuthed, address } = useAuth();
  const [qty, setQty] = useState('1');
  const [creating, setCreating] = useState(false);

  const onCreate = async () => {
    const n = parseInt(qty, 10);
    if (!n || n < 1) {
      Alert.alert('提示', '请输入有效数量');
      return;
    }
    setCreating(true);
    try {
      const res = await apiFetch<{ success: boolean; message?: string; data?: BuyOrderData }>('/api/buy/create-order', {
        method: 'POST',
        body: { qty: n },
      });
      if (!res.success) throw new Error(res.message || '下单失败');
      const order = res.data!;
      // 打开系统浏览器进入 GMPay 支付页（避免 WebView 带入 session）
      await Linking.openURL(order.payment_url);
      Alert.alert('下单成功', `订单号 ${order.order_id}\n已在浏览器打开支付页面，支付完成后可在“我的订单”查看。`, [{ text: '好的' }]);
      router.push('/orders');
    } catch (e) {
      Alert.alert('下单失败', errMessage(e));
    } finally {
      setCreating(false);
    }
  };

  if (!isAuthed) {
    return (
      <SafeAreaView edges={['top', 'left', 'right']} style={styles.safe}>
        <BrandHeader right={<BackBtn onPress={() => router.back()} />} />
        <ScrollView contentContainerStyle={styles.scroll}>
          <Text style={typography.title}>购买代币</Text>
          <View style={styles.card}>
            <Text style={styles.hint}>购买代币需要先登录。登录后在浏览器完成 GMPay 网络支付。</Text>
          </View>
          <Button title="去登录" onPress={() => router.push('/login')} />
        </ScrollView>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView edges={['top', 'left', 'right']} style={styles.safe}>
      <BrandHeader right={<BackBtn onPress={() => router.back()} />} />
      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <Text style={typography.title}>购买代币</Text>
        <Text style={styles.sub}>交易将跳转 GMPay 在线支付（USD 计价）</Text>

        <Spacer h={spacing.lg} />

        <View style={styles.card}>
          <View style={styles.line}>
            <Text style={styles.label}>数量</Text>
            <View style={styles.qtyBox}>
              <Text style={styles.qtyMinus} onPress={() => setQty((q) => String(Math.max(1, (parseInt(q, 10) || 1) - 1)))}>−</Text>
              <Text style={styles.qtyVal}>{qty}</Text>
              <Text style={styles.qtyPlus} onPress={() => setQty((q) => String((parseInt(q, 10) || 1) + 1))}>＋</Text>
            </View>
          </View>
          <View style={styles.line}>
            <Text style={styles.label}>付款账号</Text>
            <Text style={styles.value} numberOfLines={1}>{address}</Text>
          </View>
        </View>

        <Button title={creating ? '创建订单中…' : '创建订单'} loading={creating} onPress={onCreate} />
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.bg },
  scroll: { paddingHorizontal: spacing.md, paddingBottom: spacing.xl },
  sub: { fontFamily: fonts.body, fontSize: 14, color: colors.muted, marginTop: spacing.sm },
  card: { backgroundColor: colors.surface, borderRadius: radius.md, padding: spacing.md, marginBottom: spacing.md },
  line: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 8 },
  label: { fontFamily: fonts.body, fontSize: 14, color: colors.muted },
  value: { fontFamily: fonts.bodySemi, fontSize: 13, color: colors.text, flex: 1, marginLeft: spacing.md, textAlign: 'right' },
  qtyBox: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.bg,
    borderRadius: radius.sm,
    borderWidth: 1,
    borderColor: colors.border,
  },
  qtyMinus: { paddingHorizontal: 16, paddingVertical: 6, fontSize: 18, color: colors.text },
  qtyVal: { fontFamily: fonts.heading, fontSize: 16, color: colors.text, minWidth: 30, textAlign: 'center' },
  qtyPlus: { paddingHorizontal: 16, paddingVertical: 6, fontSize: 18, color: colors.text },
  hint: { fontFamily: fonts.body, fontSize: 14, color: colors.body, lineHeight: 22 },
});