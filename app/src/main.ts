import Vue from 'vue'
import App from './App.vue'
import vuetify from './plugins/vuetify';
import * as moment from 'moment'
import axios from './axios'
import store from "./store";
import router from './router'
import VueApexCharts from 'vue-apexcharts'
import './registerServiceWorker'
import Branch from "./models/Branch";
import Group from "./models/Group";
import TrainingSeries from "./models/TrainingSeries";
import SimpleUser from "./models/SimpleUser";
import {ApolloClient} from 'apollo-client'
import {createHttpLink} from 'apollo-link-http'
import {InMemoryCache} from 'apollo-cache-inmemory'
import VueApollo from 'vue-apollo'
import gql from 'graphql-tag'

// HTTP connection to the API
const httpLink = createHttpLink({
    // You should use an absolute URL here
    uri: 'http://localhost:8000/graphql',
})

// Cache implementation
const cache = new InMemoryCache()

// Create the apollo client
const apolloClient = new ApolloClient({
    link: httpLink,
    cache,
})

Vue.use(VueApollo)

const apolloProvider = new VueApollo({
    defaultClient: apolloClient,
})

Vue.prototype.moment = moment

Vue.use(VueApexCharts)
// eslint-disable-next-line
Vue.component('apexchart', VueApexCharts)

Vue.config.productionTip = false

const init = async () => {
    try {
        apolloProvider.defaultClient.cache.reset();
        const usersPromise = await apolloProvider.defaultClient.query({
            query: gql`{
                publicUsers {
                id
                firstName
                familyName
                groups {
                    id
                }
              }
            }`
        });

        const branchsPromise = axios.get('/branch');
        const groupsPromise = axios.get('/group');
        const locationsPromise = axios.get('/location');
        const contentsPromise = axios.get('/content');
        const trainersPromise = axios.get('/simpleuser/trainers');
        const trainingSeriesPromise = axios.get('/trainingSeries');

        const [resBranches, resGroups, locations, contents, trainers, resUsers, resTrainingSeries] = await Promise.all([branchsPromise, groupsPromise, locationsPromise, contentsPromise, trainersPromise, usersPromise, trainingSeriesPromise]);

        const branches = resBranches.data.data.map(b => new Branch(b.id, b.name, b.shortName, b.colorHex));
        store.commit('masterData/setBranches', branches);
        store.commit('masterData/setGroups', resGroups.data.data.map(g => new Group(g.id, g.name, g.branchId, branches.filter(b => b.id == g.branchId)[0], g.userIds)));
        store.commit('masterData/setLocations', locations.data.data);
        store.commit('masterData/setContents', contents.data);
        store.commit('masterData/setSimpleTrainers', trainers.data.data);
        store.commit('masterData/setSimpleUsers', resUsers.data.publicUsers.map(u => new SimpleUser(u.id, u.firstName, u.familyName, u.groupIds)));
        store.commit('masterData/setTrainingSeries', resTrainingSeries.data.data.map(ts => TrainingSeries.from(ts)));
    } catch (e) {
        console.error("Could not load initial data", e)
        Vue.prototype.$isOffline = true;
    } finally {
        // @ts-ignore

        new Vue({
            vuetify,
            axios,
            apolloProvider,
            router,
            store,
            render: h => h(App),
        }).$mount('#app')
    }
};

init();
