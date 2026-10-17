import React from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useAuth } from '../../../src/context/auth';
import { BrandHeader } from '../../../src/components/BrandHeader';
import { SectionHeading } from '../../../src/components/Cond';
import { Empty, Loader } from '../../../src/components/StateView';
import { useRewards } from '../../../src/hooks/data';
import { colors, fonts, radius, spacing } from '../../../src/theme/theme';

function shortHash(h?: string): string {
  if (!h) return '—';
  return `${h.slice(0, 6)}…${h.slice(-4)}`;
}

export default function RewardsScreen() {
  const { address } = useAuth();
  const { data, loading, error, refresh } = useRewards(address);

  return (
    <SafeAreaView edges={['top', 'left', 'right']} style={styles.safe}>
      <BrandHeader />
      <ScrollView
        refreshControl={<RefreshControl refreshing={false} onRefresh={refresh} tintColor={colors.primary} />}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
      >
        <SectionHeading title="推荐奖励明细" />
        {!address ? (
          <Empty text="请先登录" hint="登录后可查看您的邀请奖励记录" />
        ) : loading ? (
          <Loader />
        ) : error ? (
          <Empty text="加载失败" hint={error} />
        ) : data && data.length === 0 ? (
          <Empty text="暂无奖励记录" hint="邀请好友注册即可获得奖励" />
        ) : (
          data?.map((r, i) => (
            <View key={i} style={styles.row}>
              <View style={styles.rowTop}>
                <View style={styles.levelBadge}>
                  <Text style={styles.levelBadgeText}>Lv{r.level}</Text>
                </View>
                <Text style={styles.amount}>+{r.amount}</Text>
              </View>
              <Text style={styles.hash}>区块 #{r.block_number}</Text>
              <Text style={styles.hash}>交易 {shortHash(r.tx_hash)}</Text>
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
  row: {
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    padding: spacing.md,
    marginBottom: spacing.sm,
  },
  rowTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 6 },
  levelBadge: {
    backgroundColor: colors.primarySoft,
    borderRadius: radius.sm,
    paddingHorizontal: 8,
    paddingVertical: 2,
  },
  levelBadgeText: { fontFamily: fonts.bodySemi, fontSize: 12, color: colors.primary },
  amount: { fontFamily: fonts.heading, fontSize: 18, color: colors.primary },
  hash: { fontFamily: fonts.body, fontSize: 12, color: colors.muted, marginTop: 2 },
});