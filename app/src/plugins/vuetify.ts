import 'vuetify/styles'
import '@/styles/vuetify-compat.css'
import '@mdi/font/css/materialdesignicons.css'
import { createVuetify } from 'vuetify'
import { de } from 'vuetify/locale'
import { initialTheme } from '@/composables/themeMode'

export default createVuetify({
  // Compact defaults: Vuetify 4 (Material Design 3) is roomy, which wastes space on phones.
  defaults: {
    VToolbar: { density: 'compact' },
    VAppBar: { density: 'compact' },
    VList: { density: 'compact' },
    VListItem: { density: 'compact' },
    VTextField: { density: 'comfortable' },
    VTextarea: { density: 'comfortable' },
    VSelect: { density: 'comfortable' },
    VAutocomplete: { density: 'comfortable' },
    VCombobox: { density: 'comfortable' },
    VCheckbox: { density: 'comfortable' },
    VSwitch: { density: 'comfortable' },
    VDataTable: { density: 'compact' },
    VDataTableServer: { density: 'compact' },
    VChip: { density: 'comfortable' },
    VAlert: { density: 'compact' },
    VTabs: { density: 'comfortable' }
  },
  locale: {
    locale: 'de',
    messages: { de }
  },
  icons: {
    defaultSet: 'mdi'
  },
  theme: {
    defaultTheme: initialTheme(),
    themes: {
      light: {
        colors: {
          primary: '#60cc69',
          secondary: '#efefef',
          accent: '#000000'
        }
      },
      dark: {
        dark: true,
        colors: {
          primary: '#60cc69',
          secondary: '#2a2a2a',
          accent: '#ffffff'
        }
      }
    }
  }
})
