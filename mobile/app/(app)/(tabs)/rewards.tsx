import React, { useCallback } from 'react';
import { RefreshControl, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useFocusEffect } from 'expo-router';
import { useAuth } from '../../../src/context/auth';
import { useLanguage } from '../../../src/i18n/LanguageContext';
import { BrandHeader } from '../../../src/components/BrandHeader';
import { SectionHeading } from '../../../src/components/Cond';
import { Empty, Loader } from '../../../src/components/StateView';
import { useRewards } from '../../../src/hooks/data';
import { colors, fonts, radius, spacing } from '../../../src/theme/theme';

function shortHash(h?: string): string {
  if (!h) return '—';
  // 完整显示以太坊哈希（66字符），比短截断更便于链上追踪
  return h;
}

export default function RewardsScreen() {
  const { address } = useAuth();
  const { t } = useLanguage();
  const { data, loading, error, refresh } = useRewards(address);

  // 每次切到推荐页自动刷新奖励明细
  useFocusEffect(
    useCallback(() => {
      if (address) refresh();
    }, [address, refresh])
  );

  return (
    <SafeAreaView edges={['top', 'left', 'right']} style={styles.safe}>
      <BrandHeader />
      <ScrollView
        refreshControl={<RefreshControl refreshing={false} onRefresh={refresh} tintColor={colors.primary} />}
        contentContainerStyle={styles.list}
        showsVerticalScrollIndicator={false}
      >
        <SectionHeading title={t('reward_detail', '推荐奖励明细')} />
        {!address ? (
          <Empty text={t('login_required', '请先登录')} hint={t('login_to_buy', '登录后可查看您的邀请奖励记录')} />
        ) : loading ? (
          <Loader />
        ) : error ? (
          <Empty text={t('load_failed', '加载失败')} hint={error} />
        ) : data && data.length === 0 ? (
          <Empty text={t('no_rewards', '暂无奖励记录')} hint={t('invite_hint', '邀请好友注册即可获得奖励')} />
        ) : (
          data?.map((r, i) => (
            <View key={i} style={styles.row}>
              <View style={styles.rowTop}>
                <View style={styles.levelBadge}>
                  <Text style={styles.levelBadgeText}>{t('level_badge', 'L{level} 推荐', { level: r.level })}</Text>
                </View>
                <Text style={styles.amount}>+{r.amount}</Text>
              </View>
              <Text style={styles.hash}>{t('block', '区块')} #{r.block_number}</Text>
              <Text style={styles.hash} selectable numberOfLines={1}>{t('tx_hash', '交易')} {shortHash(r.tx_hash)}</Text>
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
