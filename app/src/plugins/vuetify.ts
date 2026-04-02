import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { de } from 'vuetify/locale'
import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/dist/vuetify.min.css'

export default createVuetify({
  components,
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
