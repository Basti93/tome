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
        themes: {
            options: {customProperties: true},
            light: {
                primary: '#60cc69',
                secondary: '#efefef',
                accent: '#000000',

            }
        }
    }
});
