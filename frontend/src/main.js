import { createApp } from 'vue';
import { Quasar, Notify, Loading, Dialog, LocalStorage, SessionStorage } from 'quasar';

// Import icon libraries
import '@quasar/extras/material-icons/material-icons.css';

// Import Quasar css
import 'quasar/src/css/index.sass';

// Assumes your root component is App.vue
// and placed in same folder as main.js
import App from './App.vue';

const app = createApp(App);

app.use(Quasar, {
  plugins: {
    Notify,
    Loading,
    Dialog,
    LocalStorage,
    SessionStorage,
  },
  config: {
    notify: {
      position: 'top',
      timeout: 3000,
    },
  },
});

// Pinia is set up via boot/pinia.js
// Router is set up via boot/router.js
// No need to import them here

app.mount('#q-app');

