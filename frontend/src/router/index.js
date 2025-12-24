import { route } from 'quasar/wrappers';
import {
  createRouter,
  createMemoryHistory,
  createWebHistory,
  createWebHashHistory,
} from 'vue-router';
import routes from './routes';

/*
 * If not building with SSR mode, you can
 * directly export the Router instantiation;
 *
 * The function below can be async too; either use
 * async/await or return a Promise which resolves
 * with the Router instance.
 */

export default route(function (/* { store, ssrContext } */) {
  const createHistory = process.env.SERVER
    ? createMemoryHistory
    : (process.env.VUE_ROUTER_MODE === 'history' ? createWebHistory : createWebHashHistory);

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,

    // Leave this as is and make changes in quasar.config.js instead!
    // quasar.config.js -> build -> vueRouterMode
    // quasar.config.js -> build -> publicPath
    history: createHistory(process.env.VUE_ROUTER_MODE === 'ssr' ? void 0 : process.env.VUE_ROUTER_BASE),
  });

  // Navigation guard - Check authentication and roles
  Router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token');
    const isAuthRequired = to.matched.some(record => record.meta.requiresAuth);
    const isGuestOnly = to.matched.some(record => record.meta.guestOnly);
    const requiredRoles = to.matched
      .map(record => record.meta.roles)
      .filter(roles => roles && Array.isArray(roles))
      .flat();

    // If route requires auth and no token, redirect to login
    if (isAuthRequired && !token) {
      next({ path: '/login', query: { redirect: to.fullPath } });
      return;
    }

    // If route is guest-only and user is authenticated, redirect to dashboard
    if (isGuestOnly && token) {
      next({ path: '/app/dashboard' });
      return;
    }

    // Check role-based access (if roles are specified)
    if (token && requiredRoles.length > 0) {
      // Get user roles from token or store
      // For now, we'll allow access - proper role checking will be done in components
      // TODO: Implement proper role checking from JWT token or auth store
    }

    next();
  });

  return Router;
});

