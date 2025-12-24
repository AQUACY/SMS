import { boot } from 'quasar/wrappers';
import api from 'src/services/api';

export default boot(({ app }) => {
  // Make API instance available globally
  app.config.globalProperties.$api = api;
});

