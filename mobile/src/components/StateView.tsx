import React from 'react';
import { ActivityIndicator, StyleSheet, Text, View } from 'react-native';
import { colors, fonts } from '../theme/theme';

export function Loader({ text = 'Loading…' }: { text?: string }) {
  return (
    <View style={styles.wrap}>
      <ActivityIndicator size="large" color={colors.primary} />
      <Text style={styles.text}>{text}</Text>
    </View>
  );
}

export function Empty({ text = 'No data', hint }: { text?: string; hint?: string }) {
  return (
    <View style={styles.wrap}>
      <View style={styles.bar} />
      <Text style={styles.text}>{text}</Text>
      {hint ? <Text style={styles.hint}>{hint}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingVertical: 64, paddingHorizontal: 24 },
  bar: { width: 46, height: 3, backgroundColor: colors.primary, marginBottom: 16 },
  text: { fontFamily: fonts.body, fontSize: 15, color: colors.body },
  hint: { fontFamily: fonts.body, fontSize: 13, color: colors.muted, marginTop: 8, textAlign: 'center' },
});