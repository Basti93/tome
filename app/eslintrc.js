module.exports = {
    extends: [
        'plugin:vue/base',
    ],
    plugins: [
        'vuetify'
    ],
    rules: {
        'vuetify/no-deprecated-classes': 'off',
        'vuetify/grid-unknown-attributes': 'off',
        'vuetify/no-legacy-grid': 'off',
        'vue/no-deprecated-slot-attribute': 'off',
        'vue/no-deprecated-v-on-native-modifier': 'off',
        'vue/no-deprecated-v-bind-sync': 'off',
        'vue/no-use-computed-property-like-method': 'off',
        'vue/multi-word-component-names': 'off',
        'vue/no-v-text-v-html-on-component': 'off'
    }

}
