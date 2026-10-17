import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { colors, fonts, spacing } from '../theme/theme';

export function Cond({ children, text }: { children?: React.ReactNode; text?: string }) {
  return (
    <View style={styles.wrap}>
      <View style={styles.bar} />
      <Text style={styles.text}>{text ?? children}</Text>
    </View>
  );
}

/** 带左侧短重直线的 Editorial 小节标题 */
export function SectionHeading({ title, style }: { title: string; style?: object }) {
  return (
    <View style={[styles.headingRow, style]}>
      <View style={styles.headingBar} />
      <Text style={styles.headingText}>{title}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { flexGrow: 1, justifyContent: 'center', alignItems: 'center', paddingVertical: spacing.xl },
  bar: { width: 46, height: 3, backgroundColor: colors.primary, marginBottom: spacing.md },
  text: { fontFamily: fonts.body, fontSize: 15, color: colors.muted },
  headingRow: { flexDirection: 'row', alignItems: 'center', gap: 12, marginBottom: spacing.md },
  headingBar: { width: 20, height: 4, backgroundColor: colors.primary },
  headingText: { fontFamily: fonts.heading, fontSize: 15, color: colors.text },
});