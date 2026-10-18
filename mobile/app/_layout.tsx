import { RobotoSlab_600SemiBold, RobotoSlab_700Bold, useFonts as useRoboto } from '@expo-google-fonts/roboto-slab';
import { OpenSans_400Regular, OpenSans_600SemiBold, useFonts as useOpen } from '@expo-google-fonts/open-sans';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import React from 'react';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { AuthProvider } from '../src/context/auth';
import { LanguageProvider } from '../src/i18n/LanguageContext';

export default function RootLayout() {
  const [roboto] = useRoboto({ RobotoSlab_600SemiBold, RobotoSlab_700Bold });
  const [open] = useOpen({ OpenSans_400Regular, OpenSans_600SemiBold });

  if (!roboto || !open) {
    return null;
  }

  return (
    <SafeAreaProvider>
      <LanguageProvider>
        <AuthProvider>
          <StatusBar style="dark" />
          <Stack
            screenOptions={{
              headerShown: false,
              contentStyle: { backgroundColor: '#ffffff' },
            }}
          >
            <Stack.Screen name="(app)" options={{ headerShown: false }} />
          </Stack>
        </AuthProvider>
      </LanguageProvider>
    </SafeAreaProvider>
  );
}