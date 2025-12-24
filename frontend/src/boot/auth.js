import { boot } from 'quasar/wrappers';

export default boot(async ({ router }) => {
  // Import store inside boot function to ensure Pinia is initialized
  const { useAuthStore } = await import('src/stores/auth');
  const authStore = useAuthStore();
  
  // Check authentication status on app load
  try {
    await authStore.checkAuth();
  } catch (error) {
    console.error('Auth check failed:', error);
  }
});

