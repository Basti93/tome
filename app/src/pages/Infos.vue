<template>
    <v-layout align-top>
        <v-flex xs12 md10 offset-md1 top>
            <v-card>
                <v-toolbar flat>
                    <v-toolbar-title>Informationen</v-toolbar-title>
                </v-toolbar>
                <v-divider></v-divider>
                <v-card-text flat>
                    <h4>Nützliche Dateien</h4>
                    <v-list>
                        <v-list-item
                                v-for="(item, index) in filePaths"
                                :key="index">
                            <v-list-item-content><a :href="serverUrl + '/' + item" download target="_blank">{{item.split('\\').pop().split('/').pop()}}</a></v-list-item-content>
                        </v-list-item>
                    </v-list>
                    <v-divider class="pt-2 pb-2"></v-divider>
                    <v-expansion-panels
                            v-model="openedPanel"
                            focusable
                            accordion>
                        <v-expansion-panel
                            v-for="(item, index) in faqs"
                            :key="index">
                            <v-expansion-panel-header>{{item.headline}}</v-expansion-panel-header>
                            <v-expansion-panel-content v-html="item.content" class="ma-2"></v-expansion-panel-content>
                        </v-expansion-panel>
                    </v-expansion-panels>
                </v-card-text>
            </v-card>
        </v-flex>
    </v-layout>
</template>

<script lang="ts">

    import Vue from "vue";

    export default Vue.extend({
        name: "Infos",
        data: function () {
            return {
                openedPanel: 0,
                faqs: [],
                filePaths: [],
                serverUrl: process.env.VUE_APP_IMAGE_FOLDER_URL,
            }
        },
        created(): void {
            this.fetchFaqs();
        },
        methods: {
            async fetchFaqs() {
                let res = await this.$http.get('/faq/');
                this.faqs = res.data;
                res = await this.$http.get('/faq/files');
                this.filePaths = res.data;

            },
        }
    })
</script>

<style scoped lang="scss">
    .v-expansion-panel-content {
        display: block;
    }
</style>
