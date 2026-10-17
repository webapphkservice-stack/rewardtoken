import { useRouter } from 'expo-router';
import React from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { BrandHeader } from '../../../src/components/BrandHeader';
import { SectionHeading } from '../../../src/components/Cond';
import { useAuth } from '../../../src/context/auth';
import { colors, fonts, radius, spacing } from '../../../src/theme/theme';

function shortAddr(a?: string | null): string {
  if (!a) return '未登录';
  return `${a.slice(0, 8)}…${a.slice(-6)}`;
}

export default function MineScreen() {
  const router = useRouter();
  const { address, username, isAuthed, logout } = useAuth();

  return (
    <SafeAreaView edges={['top', 'left', 'right']} style={styles.safe}>
      <BrandHeader />
      <ScrollView contentContainerStyle={styles.list} showsVerticalScrollIndicator={false}>
        <SectionHeading title="个人中心" />

        <View style={styles.userCard}>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>{username?.[0]?.toUpperCase() ?? '?'}</Text>
          </View>
          <View style={styles.userInfo}>
            <Text style={styles.username}>{username ?? '未登录'}</Text>
            <Text style={styles.address} numberOfLines={1}>{isAuthed ? shortAddr(address) : '登录后可购买代币与查看订单'}</Text>
          </View>
        </View>

        {!isAuthed ? (
          <Pressable style={styles.row} onPress={() => router.push('/login')}>
            <Text style={styles.rowLabel}>登录</Text>
            <Text style={styles.arrow}>›</Text>
          </Pressable>
        ) : (
          <>
            <Pressable style={styles.row} onPress={() => router.push('/buy')}>
              <Text style={styles.rowLabel}>购买代币</Text>
              <Text style={styles.arrow}>›</Text>
            </Pressable>
            <Pressable style={styles.row} onPress={() => router.push('/orders')}>
              <Text style={styles.rowLabel}>我的订单</Text>
              <Text style={styles.arrow}>›</Text>
            </Pressable>
            <Pressable
              style={styles.row}
              onPress={async () => {
                await logout();
                router.replace('/(app)/(tabs)/index');
              }}
            >
              <Text style={[styles.rowLabel, { color: colors.danger }]}>退出登录</Text>
              <Text style={styles.arrow}>›</Text>
            </Pressable>
          </>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.bg },
  list: { paddingHorizontal: spacing.md, paddingBottom: spacing.xl },
  userCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    padding: spacing.md,
    marginBottom: spacing.md,
  },
  avatar: {
    width: 52,
    height: 52,
    borderRadius: 26,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: { fontFamily: fonts.heading, fontSize: 22, color: '#fff' },
  userInfo: { flex: 1, marginLeft: spacing.md, minWidth: 0 },
  username: { fontFamily: fonts.bodySemi, fontSize: 16, color: colors.text },
  address: { fontFamily: fonts.body, fontSize: 13, color: colors.muted, marginTop: 3 },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    padding: spacing.md,
    marginBottom: spacing.sm,
  },
  rowLabel: { fontFamily: fonts.bodySemi, fontSize: 15, color: colors.text },
  arrow: { fontFamily: fonts.heading, fontSize: 18, color: colors.muted },
});