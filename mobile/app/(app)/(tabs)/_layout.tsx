import { Tabs } from 'expo-router';
import React from 'react';
import { StyleSheet, Text, View, type ColorValue } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { colors, fonts } from '../../../src/theme/theme';

export default function TabsLayout() {
  const insets = useSafeAreaInsets();
  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.muted,
        tabBarStyle: {
          backgroundColor: colors.bg,
          borderTopColor: colors.border,
          height: 60 + insets.bottom,
          paddingBottom: 6 + insets.bottom,
        },
        tabBarLabelStyle: { fontFamily: fonts.bodySemi, fontSize: 11 },
      }}
    >
      <Tabs.Screen
        name="index"
        options={{ title: '首页', tabBarIcon: ({ color }) => <Icon name="首页" color={color} /> }}
      />
      <Tabs.Screen
        name="wallet"
        options={{ title: '钱包', tabBarIcon: ({ color }) => <Icon name="钱包" color={color} /> }}
      />
      <Tabs.Screen
        name="rewards"
        options={{ title: '推荐', tabBarIcon: ({ color }) => <Icon name="推荐" color={color} /> }}
      />
      <Tabs.Screen
        name="blocks"
        options={{ title: '区块', tabBarIcon: ({ color }) => <Icon name="区块" color={color} /> }}
      />
      <Tabs.Screen
        name="mine"
        options={{ title: '我的', tabBarIcon: ({ color }) => <Icon name="我的" color={color} /> }}
      />
    </Tabs>
  );
}

// 轻量文本图标（Editorial 极简风格，避免引入图标库）
function Icon({ name, color }: { name: string; color: ColorValue }) {
  const glyph =
    name === '首页' ? '⌂' : name === '钱包' ? '◈' : name === '推荐' ? '★' : name === '区块' ? '▩' : '●';
  return (
    <View style={styles.iconWrap}>
      <Text style={[styles.iconText, { color }]}>{glyph}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  iconWrap: { alignItems: 'center', justifyContent: 'center' },
  iconText: { fontSize: 18, lineHeight: 22 },
});