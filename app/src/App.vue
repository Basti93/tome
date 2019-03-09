<template>
  <div id="app">
    <v-app id="tome" dark>
      <Navigation/>
      <v-content
        :class="{'pt-0': $vuetify.breakpoint.smAndDown, 'pt-4': $vuetify.breakpoint.mdAndUp}">
        <v-container
          fluid
          fill-height
          :class="{'pa-0': $vuetify.breakpoint.smAndDown, 'pa-2': $vuetify.breakpoint.mdAndUp}">
          <SnackbarStore/>
          <v-snackbar
            bottom
            right
            :text="text"
            :timeout="timeout"
            :color="color"
            v-model="snackbar"
          >
            {{text}}
          </v-snackbar>
          <v-slide-y-transition mode="out-in">
            <router-view v-on:showSnackbar="showSnackbar"/>
          </v-slide-y-transition>
        </v-container>
      </v-content>
      <v-footer app fixed>
        <span>&copy; T.O.M.E - 2019</span>
      </v-footer>
    </v-app>
  </div>
</template>

<script>
  import {mapGetters} from 'vuex'
  import Navigation from "@/components/Navigation";
  import SnackbarStore from '@/components/SnackbarStore'

  export default {
    name: 'App',
    components: {Navigation, SnackbarStore},
    data() {
      return {
        snackbar: false,
        text: "",
        timeout: null,
        color: null,
      }
    },
    computed: {
      ...mapGetters({loggedInUser: 'loggedInUser'}),
    },
    created() {
      this.init();
    },
    methods: {
      init: function () {
        this.moment.locale('de');
      },
      showSnackbar(text, color = "info", timeout = 3000) {
        this.text = text
        this.timeout = timeout
        this.color = color
        this.snackbar = true
      },
    }

  }
</script>

<style>
  #app {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    text-align: center;
    margin-top: 60px;
  }
</style>