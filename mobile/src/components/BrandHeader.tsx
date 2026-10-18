import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { colors, fonts, spacing, typography } from '../theme/theme';

export function BrandHeader({ right }: { right?: React.ReactNode }) {
  return (
    <View style={styles.brandRow}>
      <View>
        <Text style={styles.brandName}>Invite Reward Token</Text>
        <View style={styles.brandBar} />
      </View>
      {right ? <View>{right}</View> : null}
    </View>
  );
}

export function BackBtn({ onPress }: { onPress: () => void }) {
  return (
    <Pressable onPress={onPress} hitSlop={12} style={styles.backBtn}>
      <Text style={styles.backText}>‹</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  brandRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.md,
  },
  brandName: { fontFamily: fonts.heading, fontSize: 22, color: colors.text },
  brandBar: { width: 28, height: 4, backgroundColor: colors.primary, marginTop: 6, borderRadius: 2 },
  backBtn: { width: 36, height: 36, borderRadius: 18, alignItems: 'center', justifyContent: 'center' },
  backText: { fontSize: 26, fontFamily: fonts.heading, color: colors.text, lineHeight: 28 },
});

export function Kicker() {
  return (
    <View style={{ marginTop: spacing.xs }}>
      <Text style={typography.label}>基于 Geth 的三级推荐奖励系统</Text>
    </View>
  );
}