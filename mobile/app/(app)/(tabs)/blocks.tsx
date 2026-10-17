import React from 'react';
import { FlatList, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { BrandHeader } from '../../../src/components/BrandHeader';
import { SectionHeading } from '../../../src/components/Cond';
import { Empty, Loader } from '../../../src/components/StateView';
import { useBlocks } from '../../../src/hooks/data';
import type { BlockSummary } from '../../../src/api/types';
import { colors, fonts, radius, spacing } from '../../../src/theme/theme';

function fmtTime(ts?: number): string {
  if (!ts) return '—';
  return new Date(ts * 1000).toLocaleTimeString('zh-CN', { hour12: false });
}

export default function BlocksScreen() {
  const { data, loading, error, refresh, loadMore, loadingMore } = useBlocks(15);

  if (loading) {
    return (
      <SafeAreaView edges={['top', 'left', 'right']} style={styles.safe}>
        <BrandHeader />
        <Loader />
      </SafeAreaView>
    );
  }
  if (error) {
    return (
      <SafeAreaView edges={['top', 'left', 'right']} style={styles.safe}>
        <BrandHeader />
        <Empty text="加载失败" hint={error} />
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView edges={['top', 'left', 'right']} style={styles.safe}>
      <BrandHeader />
      <FlatList
        data={data?.blocks ?? []}
        keyExtractor={(item) => String(item.number)}
        refreshControl={<RefreshControl refreshing={false} onRefresh={refresh} tintColor={colors.primary} />}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        onEndReached={loadMore}
        onEndReachedThreshold={0.3}
        renderItem={({ item }) => <BlockCard block={item} />}
        ListHeaderComponent={
          <View>
            <SectionHeading title={`最新区块${data?.latest != null ? ` · 当前 ${data.latest}` : ''}`} />
          </View>
        }
        ListEmptyComponent={<Empty text="暂无区块" />}
        ListFooterComponent={loadingMore ? <Loader text="加载更多…" /> : null}
      />
    </SafeAreaView>
  );
}

function BlockCard({ block }: { block: BlockSummary }) {
  return (
    <View style={styles.card}>
      <View style={styles.cardTop}>
        <Text style={styles.number}>区块 #{block.number}</Text>
        <Text style={styles.hash}>{block.transactions} txs</Text>
      </View>
      <Text style={styles.hash} numberOfLines={1}>哈希 {block.hash}</Text>
      <Text style={styles.hash}>矿工 {block.miner}</Text>
      <Text style={styles.hash}>时间 {fmtTime(block.timestamp)}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.bg },
  list: { paddingHorizontal: spacing.md, paddingBottom: spacing.xl },
  card: {
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    padding: spacing.md,
    marginBottom: spacing.sm,
  },
  cardTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 6 },
  number: { fontFamily: fonts.heading, fontSize: 16, color: colors.text },
  hash: { fontFamily: fonts.body, fontSize: 12, color: colors.muted, marginTop: 2 },
});