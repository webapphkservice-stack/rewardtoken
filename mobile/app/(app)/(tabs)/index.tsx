import { useRouter } from 'expo-router';
import React from 'react';
import { Pressable, RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { BrandHeader } from '../../../src/components/BrandHeader';
import { SectionHeading } from '../../../src/components/Cond';
import { Empty, Loader } from '../../../src/components/StateView';
import { useLanguage } from '../../../src/i18n/LanguageContext';
import { useStatus } from '../../../src/hooks/data';
import { colors, fonts, radius, spacing, typography } from '../../../src/theme/theme';

function fmtBlockTime(ts: number | null | undefined, localeFmt: string = 'zh-CN'): string {
  if (!ts) return '—';
  return new Date(ts * 1000).toLocaleString(localeFmt, { hour12: false });
}

export default function HomeScreen() {
  const router = useRouter();
  const { t, locale } = useLanguage();
  const fmtLocale = locale === 'zh-cn' || locale === 'zh-tw' ? 'zh-CN' : 'en-US';
  const { data, loading, error, refresh } = useStatus();

  const symbol = data?.symbol ?? 'IRT';
  const chainDesc = t('home_chain_desc', '基于 Geth 的三级推荐奖励系统');

  return (
    <SafeAreaView edges={['top', 'left', 'right']} style={styles.safe}>
      <BrandHeader />
      <ScrollView
        refreshControl={<RefreshControl refreshing={false} onRefresh={refresh} tintColor={colors.primary} />}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.hero}>
          <Text style={typography.huge}>{data?.name ?? t('site_name', '邀请奖励代币')}</Text>
          <View style={styles.accentBar} />
          <Text style={styles.heroDesc}>
            {data?.symbol ? t('token_symbol_label', '代币符号：{symbol}', { symbol }) : chainDesc}
          </Text>
          <Pressable style={styles.buyBtn} onPress={() => router.push('/buy')}>
            <Text style={styles.buyBtnText}>{t('buy_token', '购买代币')}</Text>
          </Pressable>
        </View>

        {loading ? (
          <Loader />
        ) : error ? (
          <Empty text={t('load_failed', '加载失败')} hint={error} />
        ) : data ? (
          <>
            <View style={styles.statsRow}>
              <View style={styles.stat}>
                <Text style={styles.statValue}>{data.total_supply ?? '—'}</Text>
                <Text style={styles.statLabel}>{t('total_supply', '总供应量')}</Text>
              </View>
              <View style={styles.statDivider} />
              <View style={styles.stat}>
                <Text style={styles.statValue}>{data.invite_reward ?? '—'}</Text>
                <Text style={styles.statLabel}>{t('invite_reward', '邀请奖励')}</Text>
              </View>
              <View style={styles.statDivider} />
              <View style={styles.stat}>
                <Text style={styles.statValue}>{data.wallet_count ?? '—'}</Text>
                <Text style={styles.statLabel}>{t('registered_wallets', '注册钱包')}</Text>
              </View>
            </View>

            <SectionHeading title={t('reward_ratio', '推荐奖励比例')} />
            <View style={styles.levelRow}>
              {Array.isArray(data.referral_levels)
                ? data.referral_levels.map((v, i) => (
                    <View key={i} style={styles.level}>
                      <Text style={styles.levelValue}>{v}</Text>
                      <Text style={styles.levelLabel}>{t('level_badge', 'L{level}', { level: i + 1 })}</Text>
                    </View>
                  ))
                : null}
            </View>

            <SectionHeading title={t('latest_blocks', '最新区块')} />
            <View style={styles.blockCard}>
              <View style={styles.blockLine}>
                <Text style={styles.blockLabel}>{t('block_height', '高度')}</Text>
                <Text style={styles.blockValue}>#{data.block?.number}</Text>
              </View>
              <View style={styles.blockLine}>
                <Text style={styles.blockLabel}>{t('transactions', '交易数')}</Text>
                <Text style={styles.blockValue}>{data.block?.transactions ? data.block?.transactions : '—'}</Text>
              </View>
              <View style={styles.blockLine}>
                <Text style={styles.blockLabel}>{t('gas_used_label', 'Gas 使用')}</Text>
                <Text style={styles.blockValue}>{data.block?.gas_used ? data.block?.gas_used?.toLocaleString() : '—'}</Text>
              </View>
              <View style={styles.blockLine}>
                <Text style={styles.blockLabel}>{t('time', '时间')}</Text>
                <Text style={styles.blockValue}>{fmtBlockTime(data.block?.timestamp, fmtLocale)}</Text>
              </View>
              <View style={styles.blockLine}>
                <Text style={styles.blockLabel}>{t('miner', '矿工')}</Text>
                <Text style={[styles.blockValue, styles.hash]} numberOfLines={1}>
                  {data.block?.miner}
                </Text>
              </View>
            </View>
          </>
        ) : null}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.bg },
  list: { paddingHorizontal: spacing.md, paddingBottom: spacing.xl },
  hero: { marginTop: spacing.sm, marginBottom: spacing.lg },
  accentBar: { width: 60, height: 4, backgroundColor: colors.primary, marginTop: 10, borderRadius: 2 },
  heroDesc: { fontFamily: fonts.body, fontSize: 14, color: colors.muted, marginTop: spacing.sm },
  buyBtn: {
    backgroundColor: colors.primary,
    borderRadius: radius.md,
    paddingVertical: 12,
    alignItems: 'center',
    marginTop: spacing.md,
  },
  buyBtnText: { fontFamily: fonts.bodySemi, fontSize: 15, color: '#fff' },
  statsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    paddingVertical: 14,
    marginBottom: spacing.lg,
  },
  stat: { flex: 1, alignItems: 'center' },
  statValue: { fontFamily: fonts.heading, fontSize: 18, color: colors.primary },
  statLabel: { fontFamily: fonts.body, fontSize: 11, color: colors.muted, marginTop: 2 },
  statDivider: { width: 1, height: 28, backgroundColor: colors.border },
  levelRow: { flexDirection: 'row', gap: spacing.md, marginBottom: spacing.lg },
  level: {
    flex: 1,
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    paddingVertical: 16,
  },
  levelValue: { fontFamily: fonts.heading, fontSize: 20, color: colors.primary },
  levelLabel: { fontFamily: fonts.body, fontSize: 12, color: colors.muted, marginTop: 4 },
  blockCard: {
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    padding: spacing.md,
  },
  blockLine: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 6 },
  blockLabel: { fontFamily: fonts.body, fontSize: 13, color: colors.muted },
  blockValue: { fontFamily: fonts.bodySemi, fontSize: 13, color: colors.text },
  hash: { flex: 1, marginLeft: spacing.md, textAlign: 'right' },
});
