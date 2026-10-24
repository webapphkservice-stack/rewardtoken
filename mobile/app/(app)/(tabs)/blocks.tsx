import React from 'react';
import { FlatList, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { TopBar } from '../../../src/components/TopBar';
import { SectionHeading } from '../../../src/components/Cond';
import { Empty, Loader } from '../../../src/components/StateView';
import { useLanguage } from '../../../src/i18n/LanguageContext';
import { useBlocks } from '../../../src/hooks/data';
import type { BlockSummary } from '../../../src/api/types';
import { colors, fonts, radius, spacing } from '../../../src/theme/theme';

function fmtTime(ts?: number, localeFmt = 'zh-CN'): string {
  if (!ts) return '—';
  return new Date(ts * 1000).toLocaleTimeString(localeFmt, { hour12: false });
}

export default function BlocksScreen() {
  const { t, locale } = useLanguage();
  const fmtLocale = locale === 'zh-cn' || locale === 'zh-tw' ? 'zh-CN' : 'en-US';
  const { data, loading, error, refresh, loadMore, loadingMore } = useBlocks(15);

  if (loading) {
    return (
      <SafeAreaView edges={['top', 'left', 'right']} style={styles.safe}>
        <TopBar title={t('latest_blocks', '最新区块')} />
        <Loader />
      </SafeAreaView>
    );
  }
  if (error) {
    return (
      <SafeAreaView edges={['top', 'left', 'right']} style={styles.safe}>
        <TopBar title={t('latest_blocks', '最新区块')} />
        <Empty text={t('load_failed', '加载失败')} hint={error} />
      </SafeAreaView>
    );
  }

  const headerTitle = data?.latest != null
    ? `${t('latest_blocks', '最新区块')} · ${t('current_block', '当前')} ${data.latest}`
    : t('latest_blocks', '最新区块');

  return (
    <SafeAreaView edges={['top', 'left', 'right']} style={styles.safe}>
      <TopBar title={t('latest_blocks', '最新区块')} />
      <FlatList
        data={data?.blocks ?? []}
        keyExtractor={(item) => String(item.number)}
        refreshControl={<RefreshControl refreshing={false} onRefresh={refresh} tintColor={colors.primary} />}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
        onEndReached={loadMore}
        onEndReachedThreshold={0.3}
        renderItem={({ item }) => <BlockCard block={item} t={t} fmtLocale={fmtLocale} />}
        ListHeaderComponent={<SectionHeading title={headerTitle} />}
        ListEmptyComponent={<Empty text={t('no_blocks', '暂无区块')} />}
        ListFooterComponent={loadingMore ? <Loader text={t('loading_more', '加载更多…')} /> : null}
      />
    </SafeAreaView>
  );
}

function BlockCard({
  block,
  t,
  fmtLocale,
}: {
  block: BlockSummary;
  t: (k: string, f?: string) => string;
  fmtLocale: string;
}) {
  return (
    <View style={styles.card}>
      <View style={styles.cardTop}>
        <Text style={styles.number}>{t('block', '区块')} #{block.number}</Text>
        <Text style={styles.hash}>{block.transactions ? `${block.transactions} txs` : '—'}</Text>
      </View>
      <Text style={styles.hash} numberOfLines={1}>{t('hash', '哈希')} {block.hash}</Text>
      <Text style={styles.hash}>{t('miner', '矿工')} {block.miner}</Text>
      <Text style={styles.hash}>{t('time', '时间')} {fmtTime(block.timestamp, fmtLocale)}</Text>
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
