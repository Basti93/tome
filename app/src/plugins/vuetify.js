import Vue from 'vue';
import Vuetify from 'vuetify';
import 'material-design-icons-iconfont/dist/material-design-icons.css'
import 'vuetify/dist/vuetify.min.css';
import de from "vuetify/src/locale/de";

Vue.use(Vuetify);

export default new Vuetify({
  lang: {
    locales: {de},
    current: 'de'
  },
  icons: {
    iconfont: 'md'
  },
  theme: {
    dark: true,
    themes: {
      dark: {
        primary: '#60cc69',
      }
    }
  }
});
