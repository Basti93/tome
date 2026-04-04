import 'vuetify/styles'
import '@mdi/font/css/materialdesignicons.css'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { de } from 'vuetify/locale'
import { VIcon } from 'vuetify/components'
import { h } from 'vue'

// Override VIcon to handle underscore to hyphen conversion
const CustomVIcon = {
  ...VIcon,
  setup(props: any, ctx: any) {
    const slots = ctx.slots.default?.()
    let icon = props.icon || ''

    if (slots && slots.length > 0) {
      icon = slots[0].children || ''
    }

    // Convert underscore to hyphen and ensure mdi- prefix
    const iconStr = String(icon || '')
    if (iconStr && !iconStr.startsWith('mdi-')) {
      const mdiName = iconStr.replace(/_/g, '-')
      ctx.slots.default = () => [h('span', `mdi-${mdiName}`)]
    }

    return VIcon.setup?.(props, ctx) || (() => null)
  }
}

export default createVuetify({
  components: {
    ...components,
    VIcon: CustomVIcon
  },
  directives,
  locale: {
    locale: 'de',
    messages: { de }
  },
  icons: {
    defaultSet: 'mdi'
  },
  theme: {
    themes: {
      light: {
        colors: {
          primary: '#60cc69',
          secondary: '#efefef',
          accent: '#000000'
        }
      }
    }
  }
})
