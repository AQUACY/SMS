import { boot } from 'quasar/wrappers';
// Router is set up in router/index.js
// Quasar automatically handles it via the route() wrapper
export default boot(({ app, router }) => {
  // Router is already registered by Quasar
  // Any router-specific boot logic can go here
});

