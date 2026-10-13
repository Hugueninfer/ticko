// @ts-check
import js from '@eslint/js';
import { defineConfig, globalIgnores } from 'eslint/config';
import jsxA11y from 'eslint-plugin-jsx-a11y';
import reactHooks from 'eslint-plugin-react-hooks';
import reactRefresh from 'eslint-plugin-react-refresh';
import globals from 'globals';
import tseslint from 'typescript-eslint';

export default defineConfig(
  globalIgnores([
    '**/dist/**',
    '**/coverage/**',
    '**/node_modules/**',
    '**/playwright-report/**',
    '**/test-results/**',
    'apps/api/**',
    'apps/mobile/**',
    'services/**',
    'packages/api-client/src/schema.d.ts',
  ]),
  {
    files: ['**/*.{ts,tsx}'],
    extends: [js.configs.recommended, tseslint.configs.strictTypeChecked, tseslint.configs.stylisticTypeChecked],
    languageOptions: {
      parserOptions: { projectService: true, tsconfigRootDir: import.meta.dirname },
    },
    rules: {
      '@typescript-eslint/restrict-template-expressions': ['error', { allowNumber: true }],
      '@typescript-eslint/no-confusing-void-expression': ['error', { ignoreArrowShorthand: true }],
      '@typescript-eslint/consistent-type-imports': 'error',
    },
  },
  {
    files: ['apps/web/**/*.{ts,tsx}'],
    extends: [reactHooks.configs.flat['recommended-latest'], reactRefresh.configs.vite, jsxA11y.flatConfigs.recommended],
    languageOptions: { globals: globals.browser },
    rules: {
      // User content is never rendered as HTML.
      'no-restricted-syntax': [
        'error',
        {
          selector: "JSXAttribute[name.name='dangerouslySetInnerHTML']",
          message: 'dangerouslySetInnerHTML is forbidden: render user content as text.',
        },
        {
          selector: 'MemberExpression[property.name=/^(innerHTML|outerHTML)$/]',
          message: 'Writing HTML strings is forbidden: render user content as text.',
        },
        {
          selector: "CallExpression[callee.property.name='insertAdjacentHTML']",
          message: 'Writing HTML strings is forbidden: render user content as text.',
        },
      ],
      // Tokens never live in browser storage; only UI preferences may.
      'no-restricted-properties': [
        'error',
        { object: 'sessionStorage', message: 'Do not use sessionStorage (no tokens or session data in the browser).' },
        { object: 'window', property: 'sessionStorage', message: 'Do not use sessionStorage.' },
        { object: 'document', property: 'write', message: 'document.write is forbidden.' },
      ],
      // Custom components take a "role" prop (user roles), not an ARIA role.
      'jsx-a11y/aria-role': ['error', { ignoreNonDOM: true }],
      'no-eval': 'error',
      'no-implied-eval': 'error',
      'react-refresh/only-export-components': ['error', { allowExportNames: ['routes', 'createRouter'] }],
    },
  },
  {
    // Generic primitives: callers pass the label target and heading content.
    files: ['apps/web/src/components/ui/**'],
    rules: { 'jsx-a11y/label-has-associated-control': 'off', 'jsx-a11y/heading-has-content': 'off' },
  },
  {
    files: ['**/*.test.{ts,tsx}', 'apps/web/e2e/**', 'apps/web/src/test/**'],
    rules: {
      '@typescript-eslint/no-non-null-assertion': 'off',
      '@typescript-eslint/unbound-method': 'off',
      // Tests assert that nothing is stored in sessionStorage.
      'no-restricted-properties': 'off',
      'react-refresh/only-export-components': 'off',
    },
  },
  {
    files: ['**/*.js'],
    extends: [tseslint.configs.disableTypeChecked],
  },
);
