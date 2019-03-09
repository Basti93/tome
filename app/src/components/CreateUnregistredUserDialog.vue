<template>
  <v-dialog v-model="show" max-width="1000px" :fullscreen="$vuetify.breakpoint.xsOnly">
    <v-card>
      <v-card-title>
        <span class="title">Vorläufigen Benutzer Anlegen</span>
      </v-card-title>

      <v-card-text>
        <v-alert
          v-bind:value="true"
          type="info"
          class="text-small"
          pa-0
          ma-0
          outline
        >
          Vorläufige Benutzer können sich für Trainings anmelden aber nicht einloggen. Erst nachdem sie sich selber dazu entschieden haben, sich zu registrieren können sie unter "Benutzer Freischalten" von einem Trainer freigeschaltet werden. Außerdem hat der Trainer die Möglichkeit den neu registrierten Benutzer einem Vorläufigen Benutzer zuzuweißen und so die Daten des Vorläufigen Benutzer auf den registrierten Benutzer zu übertragen.
        </v-alert>
        <v-container grid-list-md>
          <v-layout wrap>
            <v-flex xs12 sm6>
              <v-text-field
                v-model="firstName"
                label="Vorname"
              ></v-text-field>
            </v-flex>
            <v-flex xs12 sm6>
              <v-text-field
                v-model="familyName"
                label="Nachname"
              ></v-text-field>
            </v-flex>
            <v-flex xs12 md6>
              <v-autocomplete
                v-bind:items="trainerGroups"
                v-model="groupId"
                item-text="name"
                item-value="id"
                clearable
                label="Gruppe"
                prepend-icon="group"
              >
                <template
                  slot="no-data"
                >
                  Keine Gruppen verfügbar! Müssen in den Profileinstellungen ausgewählt werden.
                </template>
              </v-autocomplete>
            </v-flex>
          </v-layout>
        </v-container>
      </v-card-text>

      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn color="primary">Abbrechen</v-btn>
        <v-btn color="primary" @click="createUser">Speichern</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script>
  import {mapGetters} from 'vuex'
    export default {
        name: "CreateUnregistredUserDialog",
      props: ['showCreateDialog'],
      data: function () {
        return {
          firstName: null,
          familyName: null,
          groupId: null,
          editGroups: [],
        }
      },
      methods: {
        createUser() {
          let self = this;
          var data = {firstName: this.firstName, familyName: this.familyName, groupId: this.groupId};
          this.$http.post('/user/unregistered', data)
            .then(function (res) {
              if (!res.data.error) {
                self.$emit("showSnackbar", "Benutzer angelegt", "success")
                self.$emit("userCreated")
              } else {
                self.$emit("showSnackbar", "Benutzer konnte nicht angelegt werden", "error")
              }
            })
            .catch(function (err) {
              console.log(err);
              self.$emit("showSnackbar", "Benutzer konnte nicht angelegt werden", "error")
            })
        },
      },
      computed: {
        ...mapGetters({loggedInUser: 'loggedInUser'}),
        ...mapGetters('masterData', {getGroupsByIds: 'getGroupsByIds'}),
        show: {
          get() {
            return this.showCreateDialog;
          },
          set(value) {
            if (!value) {
              this.$emit('close')
            }
          }
        },
        trainerGroupIds() {
          return this.loggedInUser.trainerGroupIds
        },
        trainerGroups() {
          return this.getGroupsByIds(this.loggedInUser.trainerGroupIds)
        },
      },
      watch: {

      },
    }
</script>

<style scoped>

</style>
