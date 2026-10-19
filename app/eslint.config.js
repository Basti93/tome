import globals from 'globals'
import pluginVue from 'eslint-plugin-vue'
import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript'

export default defineConfigWithVueTs(
  { name: 'app/ignores', ignores: ['dist/**', 'public/**', 'node_modules/**', 'coverage/**'] },
  { name: 'app/files', files: ['**/*.{ts,mts,tsx,vue,js,mjs,cjs}'] },
  {
    name: 'app/globals',
    languageOptions: { globals: { ...globals.browser, ...globals.node } },
  },
  pluginVue.configs['flat/essential'],
  vueTsConfigs.recommended,
  {
    name: 'app/rules',
    rules: {
      // pre-existing code style; reported as warnings so `lint:check` only fails on real errors
      'vue/multi-word-component-names': 'off',
      'vue/no-mutating-props': 'warn',
      'vue/no-v-for-template-key': 'off',
      'vue/valid-v-slot': 'warn',
      'vue/no-v-for-template-key-on-child': 'warn',
      'vue/no-deprecated-slot-attribute': 'warn',
      'vue/no-deprecated-slot-scope-attribute': 'warn',
      'vue/no-v-text-v-html-on-component': 'warn',
      'vue/no-dupe-keys': 'warn',
      '@typescript-eslint/no-explicit-any': 'off',
      '@typescript-eslint/no-unused-vars': 'warn',
      '@typescript-eslint/no-empty-object-type': 'off',
      '@typescript-eslint/no-unsafe-function-type': 'off',
      '@typescript-eslint/no-wrapper-object-types': 'off',
      '@typescript-eslint/ban-ts-comment': 'off',
      '@typescript-eslint/no-require-imports': 'off',
      '@typescript-eslint/no-unused-expressions': 'warn',
      'no-unused-vars': 'off',
      'no-undef': 'off',
      'prefer-const': 'warn',
      '@typescript-eslint/no-this-alias': 'warn',
      'vue/block-lang': 'off',
    },
  },
)
