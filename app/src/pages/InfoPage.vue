<template>
  <v-container>
    <v-row no-gutters>
      <v-col>
        <v-card color="secondary">
          <v-toolbar flat>
            <v-toolbar-title>Informationen</v-toolbar-title>
          </v-toolbar>
          <v-divider></v-divider>
          <v-card-text>
            <v-card class="mt-2">
              <v-card-title>Was ist T.O.M.E.?</v-card-title>
              <v-card-text>
                <ul class="info-list">
                  <li>Trainingsplanung des SSC Landau: kommende Trainings mit Zeit, Ort und Gruppe auf einen Blick</li>
                  <li>Zum Training an- und abmelden ohne eigenes Konto – einfach unter „Wer bist du?“ den eigenen Namen wählen</li>
                  <li>Längere Abwesenheiten (z. B. Urlaub, Verletzung) unter „Abwesenheit eintragen“ melden</li>
                  <li>Für Trainer: Trainings vorbereiten und nachbereiten, Teilnahme erfassen, Abrechnung als Excel herunterladen</li>
                </ul>
              </v-card-text>
            </v-card>
            <v-card class="mt-2">
              <v-card-title>Tipps</v-card-title>
              <v-card-text>
                <ul class="info-list">
                  <li>Als App installieren: im Browser-Menü „Zum Startbildschirm hinzufügen“ wählen</li>
                  <li>Benachrichtigungen erlauben, um an Trainings erinnert zu werden</li>
                </ul>
              </v-card-text>
            </v-card>
            <v-card class="mt-2">
              <v-card-title>Datenschutz</v-card-title>
              <v-card-text>
                <ul class="info-list">
                  <li>Öffentlich sichtbar sind nur Vorname und Anfangsbuchstabe des Nachnamens</li>
                  <li>Personen, die längere Zeit nicht am Training teilgenommen haben, werden automatisch gelöscht</li>
                  <li>Keine Tracking-Cookies</li>
                </ul>
              </v-card-text>
            </v-card>
            <v-card class="mt-2">
              <v-card-title>Fragen?</v-card-title>
              <v-card-text>
                <ul class="info-list">
                  <li>Bei Fragen oder Problemen wende dich an deinen Trainer</li>
                </ul>
              </v-card-text>
            </v-card>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
</script>

<style scoped>
.info-list {
  padding-left: 1.25rem;
}
.info-list li {
  margin-bottom: 0.4rem;
}
</style>
