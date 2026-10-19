import globals from 'globals'
import pluginVue from 'eslint-plugin-vue'
import vueParser from 'vue-eslint-parser'
import tseslint from 'typescript-eslint'

export default tseslint.config(
  { name: 'app/ignores', ignores: ['dist/**', 'public/**', 'node_modules/**', 'coverage/**'] },
  {
    name: 'app/globals',
    languageOptions: { globals: { ...globals.browser, ...globals.node } },
  },
  ...pluginVue.configs['flat/essential'],
  ...tseslint.configs.recommended,
  {
    // TypeScript inside <script> blocks of single file components
    name: 'app/vue-typescript',
    files: ['**/*.vue'],
    languageOptions: {
      parser: vueParser,
      parserOptions: {
        parser: tseslint.parser,
        extraFileExtensions: ['.vue'],
        sourceType: 'module',
      },
    },
  },
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
