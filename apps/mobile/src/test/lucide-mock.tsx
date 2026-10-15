import { View } from 'react-native';

/** Icons render as empty views in tests (the real package ships untranspiled ESM). */
const Icon = () => <View />;

module.exports = new Proxy(
  { __esModule: true },
  { get: (target, name) => (name in target ? target[name as keyof typeof target] : Icon) },
);
