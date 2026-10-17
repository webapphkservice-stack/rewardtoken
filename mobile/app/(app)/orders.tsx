import { useRouter } from 'expo-router';
import React, { useCallback, useEffect, useState } from 'react';
import { Alert, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { apiFetch, errMessage } from '../../src/api/apiFetch';
import type { OrderItem } from '../../src/api/types';
import { BackBtn, BrandHeader } from '../../src/components/BrandHeader';
import { Empty, Loader } from '../../src/components/StateView';
import { colors, fonts, radius, spacing, typography } from '../../src/theme/theme';

function fmtTime(ts?: number): string {
  if (!ts) return '—';
  return new Date(ts * 1000).toLocaleString('zh-CN', { hour12: false });
}

export default function OrdersScreen() {
  const router = useRouter();
  const [orders, setOrders] = useState<OrderItem[] | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await apiFetch<{ success: boolean; message?: string; data?: OrderItem[] }>('/api/orders', {
        method: 'POST',
      });
      if (!res.success) throw new Error(res.message || '加载失败');
      setOrders(res.data ?? []);
    } catch (e) {
      setError(errMessage(e));
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  return (
    <SafeAreaView edges={['top', 'left', 'right']} style={styles.safe}>
      <BrandHeader right={<BackBtn onPress={() => router.back()} />} />
      <ScrollView
        refreshControl={<RefreshControl refreshing={false} onRefresh={load} tintColor={colors.primary} />}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
      >
        <Text style={typography.title}>我的订单</Text>

        {loading ? (
          <Loader />
        ) : error ? (
          <Empty text="加载失败" hint={error} />
        ) : orders && orders.length === 0 ? (
          <Empty text="暂无订单" hint="在“购买代币”页创建订单后在此查看" />
        ) : (
          orders?.map((o) => (
            <View style={styles.card}>
              <View style={styles.cardTop}>
                <Text style={styles.oid} numberOfLines={1}>{o.order_id}</Text>
                <Text style={styles.status}>{o.status ?? (o.token_amount ? '已支付' : '处理中')}</Text>
              </View>
              <View style={styles.line}>
                <Text style={styles.label}>支付金额(USD)</Text>
                <Text style={styles.value}>{o.total_usdt ?? '—'}</Text>
              </View>
              <View style={styles.line}>
                <Text style={styles.label}>代币数量</Text>
                <Text style={styles.value}>{o.token_amount ?? o.amount ?? '—'}</Text>
              </View>
              <View style={styles.line}>
                <Text style={styles.label}>数量</Text>
                <Text style={styles.value}>{o.qty ?? '—'}</Text>
              </View>
              <View style={styles.line}>
                <Text style={styles.label}>时间</Text>
                <Text style={styles.value}>{fmtTime(typeof o.created_at === 'number' ? o.created_at : undefined)}</Text>
              </View>
            </View>
          ))
        )}
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
    marginTop: spacing.md,
  },
  cardTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 },
  oid: { fontFamily: fonts.bodySemi, fontSize: 13, color: colors.text, flex: 1 },
  status: {
    fontFamily: fonts.bodySemi,
    fontSize: 12,
    color: colors.success,
    marginLeft: spacing.sm,
  },
  line: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 4 },
  label: { fontFamily: fonts.body, fontSize: 13, color: colors.muted },
  value: { fontFamily: fonts.body, fontSize: 13, color: colors.text },
});