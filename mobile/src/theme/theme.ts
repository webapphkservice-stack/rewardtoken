import { Platform, StyleSheet } from 'react-native';

/**
 * Editorial 视觉基调，取自 MyWeb3 首页 home.blade.php 的 CSS。
 */
export const colors = {
  // Editorial 品牌
  primary: '#f56a6a', // 主红
  primaryDark: '#e64c4c', // 深红
  primarySoft: '#fdeeee', // 浅红背景

  text: '#33343a', // 标题
  body: '#5d665a', // 正文灰绿
  muted: '#8a8f88',

  bg: '#ffffff', // 页面背景
  surface: '#f5f6f8', // 侧栏 / 卡片背景
  border: '#e6e6e6', // 细边框

  success: '#2ecc71',
  warning: '#d97706',
  danger: '#dc2626',
};

export const fonts = {
  heading: Platform.select({
    ios: 'RobotoSlab_600SemiBold',
    android: 'RobotoSlab_600SemiBold',
    default: 'RobotoSlab_600SemiBold',
  }),
  headingBold: Platform.select({
    ios: 'RobotoSlab_700Bold',
    android: 'RobotoSlab_700Bold',
    default: 'RobotoSlab_700Bold',
  }),
  body: Platform.select({
    ios: 'OpenSans_400Regular',
    android: 'OpenSans_400Regular',
    default: 'OpenSans_400Regular',
  }),
  bodySemi: Platform.select({
    ios: 'OpenSans_600SemiBold',
    android: 'OpenSans_600SemiBold',
    default: 'OpenSans_600SemiBold',
  }),
};

export const spacing = {
  xs: 4,
  sm: 8,
  md: 16,
  lg: 24,
  xl: 32,
};

export const radius = {
  sm: 6,
  md: 10,
  lg: 14,
};

export const typography = {
  huge: { fontSize: 32, lineHeight: 40, fontFamily: fonts.headingBold, color: colors.text },
  title: { fontSize: 22, lineHeight: 30, fontFamily: fonts.heading, color: colors.text },
  subtitle: { fontSize: 17, lineHeight: 26, fontFamily: fonts.heading, color: colors.text },
  body: { fontSize: 15, lineHeight: 24, fontFamily: fonts.body, color: colors.body },
  small: { fontSize: 13, lineHeight: 20, fontFamily: fonts.body, color: colors.muted },
  label: { fontSize: 12, fontFamily: fonts.bodySemi, color: colors.muted, letterSpacing: 0.4 },
};

export const shadows = {
  card: Platform.select({
    ios: {
      shadowColor: '#000',
      shadowOpacity: 0.05,
      shadowRadius: 8,
      shadowOffset: { width: 0, height: 2 },
    },
    android: { elevation: 2 },
    default: {},
  }),
};

export const globalStyles = StyleSheet.create({
  flex1: { flex: 1 },
  container: { flex: 1, backgroundColor: colors.bg },
  screen: { flex: 1, backgroundColor: colors.bg, padding: spacing.md },
  row: { flexDirection: 'row', alignItems: 'center' },
  rowBetween: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  center: { alignItems: 'center', justifyContent: 'center' },
});