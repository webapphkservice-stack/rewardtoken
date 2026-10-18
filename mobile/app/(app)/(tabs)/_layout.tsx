import { Tabs } from 'expo-router';
import React from 'react';
import { StyleSheet, Text, View, type ColorValue } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useLanguage } from '../../../src/i18n/LanguageContext';
import { colors, fonts } from '../../../src/theme/theme';

export default function TabsLayout() {
  const insets = useSafeAreaInsets();
  const { t } = useLanguage();

  // 每次 locale 变化，TabsLayout 会重渲染，options 中的 tabBarLabel 也会更新
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
        options={{
          title: t('home', '首页'),
          tabBarLabel: t('home', '首页'),
          tabBarIcon: ({ color }) => <Icon name="home" color={color} />,
        }}
      />
      <Tabs.Screen
        name="wallet"
        options={{
          title: t('my_wallet', '钱包'),
          tabBarLabel: t('my_wallet', '钱包'),
          tabBarIcon: ({ color }) => <Icon name="wallet" color={color} />,
        }}
      />
      <Tabs.Screen
        name="rewards"
        options={{
          title: t('reward_detail', '推荐'),
          tabBarLabel: t('reward_detail', '推荐'),
          tabBarIcon: ({ color }) => <Icon name="reward" color={color} />,
        }}
      />
      <Tabs.Screen
        name="blocks"
        options={{
          title: t('latest_blocks', '区块'),
          tabBarLabel: t('latest_blocks', '区块'),
          tabBarIcon: ({ color }) => <Icon name="blocks" color={color} />,
        }}
      />
      <Tabs.Screen
        name="mine"
        options={{
          title: t('my', '我的'),
          tabBarLabel: t('my', '我的'),
          tabBarIcon: ({ color }) => <Icon name="mine" color={color} />,
        }}
      />
    </Tabs>
  );
}

// 轻量文本图标（Editorial 极简风格，避免引入图标库）
function Icon({ name, color }: { name: string; color: ColorValue }) {
  const glyph =
    name === 'home' ? '⌂' : name === 'wallet' ? '◈' : name === 'reward' ? '★' : name === 'blocks' ? '▩' : '●';
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
