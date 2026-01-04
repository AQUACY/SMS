/* eslint-env node */

/*
 * This file runs in a Node context (it's NOT transpiled by Babel), so use only
 * the ES6 features that are supported by your Node version. https://node.green/
 */

// Configuration for your app
// https://v2.quasar.dev/quasar-cli-vite/quasar-config-js

const { configure } = require('quasar/wrappers');

module.exports = configure(function (ctx) {
  return {
    // https://v2.quasar.dev/quasar-cli-vite/prefetch-feature
    // preFetch: true,

    // app boot file (/src/boot)
    // https://v2.quasar.dev/quasar-cli-vite/boot-files
    boot: [
      'pinia',
      'axios',
      'router',
      'auth',
    ],

    // https://v2.quasar.dev/quasar-cli-vite/quasar-config-js#css
    css: [
      'app.scss'
    ],

    // https://github.com/quasarframework/quasar/tree/dev/extras
    extras: [
      // 'ionicons-v4',
      // 'mdi-v7',
      // 'fontawesome-v6',
      // 'eva-icons',
      // 'themify',
      // 'line-awesome',
      // 'roboto-font-latin-ext', // this or either 'roboto-font', NEVER both!

      'roboto-font', // optional, you are not bound to it
      'material-icons', // optional, you are not bound to it
    ],

    // Full list of options: https://v2.quasar.dev/quasar-cli-vite/quasar-config-js#build
    build: {
      target: {
        browser: [ 'es2019', 'edge88', 'firefox78', 'chrome87', 'safari13.1' ],
        node: 'node20'
      },

      vueRouterMode: 'history', // available values: 'hash', 'history'
      // vueRouterBase,
      // vueDevtools,
      // vueOptionsAPI: false,

      // rebuildCache: true, // rebuilds Vite/linter/etc cache on startup

      // publicPath: '/',
      // analyze: true,
      // env: {},
      rawDefine: {
        // Inject API_URL from environment variable during build
        'process.env.API_URL': JSON.stringify(
          process.env.API_URL || 
          process.env.VITE_API_URL || 
          'http://localhost:8000/api'
        )
      },
      // ignorePublicFolder: true,
      // minify: false,
      // polyfillModulePreload: true,
      // distDir

      extendViteConf (viteConf) {
        // Explicitly configure path aliases
        viteConf.resolve = viteConf.resolve || {};
        viteConf.resolve.alias = viteConf.resolve.alias || {};
        
        const path = require('path');
        viteConf.resolve.alias['src'] = path.resolve(__dirname, './src');
        viteConf.resolve.alias['@'] = path.resolve(__dirname, './src');
        
        // Define API_URL for Vite build - this ensures it's replaced in the code
        const apiUrl = process.env.API_URL || process.env.VITE_API_URL || 'http://localhost:8000/api';
        
        // Debug: Log the API URL being used (only in build mode)
        if (ctx.mode === 'spa' && ctx.dev === false) {
          console.log('🔧 Building with API_URL:', apiUrl);
        }
        
        viteConf.define = viteConf.define || {};
        viteConf.define['process.env.API_URL'] = JSON.stringify(apiUrl);
        
        // Also define it as a global constant for Vite
        viteConf.define['import.meta.env.VITE_API_URL'] = JSON.stringify(apiUrl);
      },
      // viteVuePluginOptions: {},

      // vitePlugins: [
      //   [ 'package-name', { /* package options */ } ]
      // ]
    },

    // Full list of options: https://v2.quasar.dev/quasar-cli-vite/quasar-config-js#devServer
    devServer: {
      // https: true
      open: true // opens browser window automatically
    },

    // https://v2.quasar.dev/quasar-cli-vite/quasar-config-js#framework
    framework: {
      config: {
        brand: {
          primary: '#9c27b0',
          secondary: '#2196f3',
          accent: '#ff9800',
          dark: '#1d1d1d',
          positive: '#4caf50',
          negative: '#f44336',
          info: '#2196f3',
          warning: '#ff9800',
        },
      },

      // iconSet: 'material-icons', // Quasar icon set
      // lang: 'en-US', // Quasar language pack

      // For special cases outside of where the auto-import strategy can have an effect
      // (like functional components as one of the examples),
      // you can manually specify Quasar components/directives to be available everywhere:
      //
      // components: [],
      // directives: [],

      // Quasar plugins
      plugins: [
        'Notify',
        'Loading',
        'Dialog',
        'LocalStorage',
        'SessionStorage',
      ]
    },

    // animations: 'all', // --- includes all animations
    // https://v2.quasar.dev/options/animations
    animations: [],

    // https://v2.quasar.dev/quasar-cli-vite/quasar-config-js#sourcefiles
    // sourceFiles: {
    //   rootComponent: 'src/App.vue',
    //   router: 'src/router/index',
    //   store: 'src/stores/index',
    //   registerServiceWorker: 'src-pwa/register-service-worker',
    //   serviceWorker: 'src-pwa/custom-service-worker',
    //   pwaManifestFile: 'src-pwa/manifest.json',
    //   electronMain: 'src-electron/electron-main',
    //   electronPreload: 'src-electron/electron-preload'
    // },

    // https://v2.quasar.dev/quasar-cli-vite/developing-ssr/configuring-ssr
    // ssr: {
    //   // ssrPwaHtmlFilename: 'index.html', // do NOT use index.html as name!
    //   // will mess up SSR
    //   //
    //   // extendSSRWebserverConf (esbuildConf) {},
    //   // extendSSRViteConf (viteConf) {},
    //   //
    //   // manualPostHydrationTrigger: true,
    //   // manualStoreHydration: true,
    //   //
    //   // prodPort: 3000, // The default port that the production server should use
    //   // (gets superseded if process.env.PORT is specified at runtime)
    //   //
    //   // middlewares: [
    //   //   'render' // keep this as last one
    //   // ]
    // },

    // https://v2.quasar.dev/quasar-cli-vite/developing-pwa/configuring-pwa
    // pwa: {
    //   workboxMode: 'generateSW', // or 'injectManifest'
    //   injectPwaMetaTags: true,
    //   swFilename: 'sw.js',
    //   manifestFilename: 'manifest.json',
    //   useCredentialsForManifestTag: false,
    //   // useFilenameHashes: true,
    //   // extendGenerateSWOptions (cfg) {}
    //   // extendInjectManifestOptions (cfg) {}
    //   // extendManifestJson (json) {}
    //   // extendPWACustomSWConf (esbuildConf) {}
    //   // extendServiceWorkerConf (esbuildConf) {}
    //   // swSrc: 'src-pwa/custom-service-worker',
    //   // swDest: 'src-pwa/sw.js',
    //   // extendInjectManifestOptions (cfg) {}
    // },

    // Full list of options: https://v2.quasar.dev/quasar-cli-vite/developing-cordova-apps/configuring-cordova
    // cordova: {
    //   // noIosLegacyBuildFlag: true, // uncomment only if you know what you are doing
    // },

    // Full list of options: https://v2.quasar.dev/quasar-cli-vite/developing-capacitor-apps/configuring-capacitor
    capacitor: {
      hideSplashscreen: true
    },

    // Full list of options: https://v2.quasar.dev/quasar-cli-vite/developing-electron-apps/configuring-electron
    // electron: {
    //   // extendElectronMainConf (esbuildConf)
    //   // extendElectronPreloadConf (esbuildConf)
    //   //
    //   // specify the debugging port to use for the Electron app when running in development mode
    //   // inspectPort: 5858,
    //   //
    //   bundler: 'packager', // 'packager' or 'builder'
    //   //
    //   packager: {
    //     // https://github.com/electron-userland/electron-packager/blob/master/docs/api.md#options
    //     //
    //     // OS X / Mac App Store
    //     // appBundleId: '',
    //     // appCategoryType: '',
    //     // osxSign: '',
    //     // protocol: 'myapp://path',
    //     //
    //     // Windows only
    //     // win32metadata: { ... }
    //   },
    //   //
    //   // builder: {
    //   //   https://www.electron.build/configuration/configuration
    //   // }
    // },

    // Full list of options: https://v2.quasar.dev/quasar-cli-vite/developing-browser-extensions/configuring-bex
    // bex: {
    //   contentScripts: [
    //     'my-content-script'
    //   ],
    // }
  }
});

