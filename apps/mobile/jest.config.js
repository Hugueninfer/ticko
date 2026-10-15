/** @type {import('jest').Config} */
module.exports = {
  preset: 'jest-expo',
  setupFilesAfterEnv: ['<rootDir>/src/test/setup.ts'],
  // pnpm keeps packages under node_modules/.pnpm; transpile the ones shipped as ESM / untranspiled.
  transformIgnorePatterns: [
    'node_modules/(?!(?:\\.pnpm/)?((jest-)?react-native|@react-native(-community)?|expo(nent)?|@expo(nent)?/.*|@expo-google-fonts/.*|react-navigation|@react-navigation/.*|react-native-svg|laravel-echo|pusher-js|openapi-fetch))',
  ],
  moduleNameMapper: { '^@/(.*)$': '<rootDir>/src/$1', '^lucide-react-native$': '<rootDir>/src/test/lucide-mock.tsx' },
  collectCoverageFrom: ['src/**/*.{ts,tsx}', '!src/**/*.test.{ts,tsx}', '!src/test/**', '!src/app/**', '!src/**/*.d.ts'],
  coverageThreshold: { global: { lines: 80, statements: 80, functions: 75, branches: 70 } },
  coverageReporters: ['text-summary', 'lcov'],
};
