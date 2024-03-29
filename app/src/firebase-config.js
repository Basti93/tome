import firebase from 'firebase/app'
import 'firebase/messaging';

const config = {
    apiKey: process.env.VUE_APP_FIREBASE_API_KEY,
    authDomain: process.env.VUE_APP_FIREBASE_AUTH_DOMAIN,
    databaseURL: process.env.VUE_APP_FIREBASE_DATABASE_URL,
    projectId: process.env.VUE_APP_FIREBASE_PROJECT_ID,
    storageBucket: process.env.VUE_APP_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: process.env.VUE_APP_FIREBASE_MESSAGING_SENDER_ID,
    appId: process.env.VUE_APP_FIREBASE_APP_ID
}

let messaging = null;
if (firebase && firebase.messaging.isSupported() && process.env.NODE_ENV === 'production') {
    console.log("Firebase messaging is supported")
    firebase.initializeApp(config)
    messaging = firebase.messaging()
    messaging.usePublicVapidKey(process.env.VUE_APP_FIREBASE_VAPID_KEY)
}


export default messaging;