import { defineStore } from 'pinia';
import { authService } from 'src/services/auth';
import { LocalStorage } from 'quasar';
import { useRouter } from 'vue-router';

export const useAuthStore = defineStore('auth', {
  state: () => {
    const token = LocalStorage.getItem('token');
    return {
      user: null,
      token: token,
      roles: [],
      // If token exists, we'll check auth status, but don't assume authenticated yet
      isAuthenticated: false,
      impersonating: LocalStorage.getItem('impersonating') === 'true' || false,
      originalUserId: LocalStorage.getItem('original_user_id') || null,
    };
  },

  getters: {
    /**
     * Check if user has a specific role
     */
    hasRole: (state) => (roleName) => {
      return state.roles.includes(roleName);
    },

    /**
     * Check if user has any of the given roles
     */
    hasAnyRole: (state) => (roleNames) => {
      return roleNames.some(role => state.roles.includes(role));
    },

    /**
     * Check if user is super admin
     */
    isSuperAdmin: (state) => {
      return state.roles.includes('super_admin');
    },

    /**
     * Check if user is school admin
     */
    isSchoolAdmin: (state) => {
      return state.roles.includes('school_admin');
    },

    /**
     * Check if user is teacher
     */
    isTeacher: (state) => {
      return state.roles.includes('teacher');
    },

    /**
     * Check if user is parent
     */
    isParent: (state) => {
      return state.roles.includes('parent');
    },

    /**
     * Check if user is accounts manager
     */
    isAccountsManager: (state) => {
      return state.roles.includes('accounts_manager');
    },

    /**
     * Get user's full name
     */
    fullName: (state) => {
      if (!state.user) return '';
      return `${state.user.first_name} ${state.user.last_name}`.trim();
    },
  },

  actions: {
    /**
     * Set authentication data
     */
    setAuth(user, token, roles, impersonating = false, originalUserId = null) {
      this.user = user;
      this.token = token;
      this.roles = roles || [];
      this.isAuthenticated = true;
      this.impersonating = impersonating;
      this.originalUserId = originalUserId;
      
      LocalStorage.set('token', token);
      if (impersonating) {
        LocalStorage.set('impersonating', true);
        LocalStorage.set('original_user_id', originalUserId);
      } else {
        LocalStorage.remove('impersonating');
        LocalStorage.remove('original_user_id');
      }
    },

    /**
     * Login user
     */
    async login(credentials) {
      try {
        const response = await authService.login(credentials);
        
        if (response.success && response.data) {
          const { user, token, roles } = response.data;
          this.setAuth(user, token, roles);
          return { success: true };
        }
        
        return { success: false, message: response.message || 'Login failed' };
      } catch (error) {
        return {
          success: false,
          message: error.response?.data?.message || 'Login failed. Please try again.',
        };
      }
    },

    /**
     * Register new user
     */
    async register(data) {
      try {
        const response = await authService.register(data);
        
        if (response.success && response.data) {
          const { user, token, roles } = response.data;
          this.setAuth(user, token, roles);
          return { success: true };
        }
        
        return { success: false, message: response.message || 'Registration failed' };
      } catch (error) {
        return {
          success: false,
          message: error.response?.data?.message || 'Registration failed. Please try again.',
        };
      }
    },

    /**
     * Clear authentication state (without API call)
     */
    clearAuth() {
      this.user = null;
      this.token = null;
      this.roles = [];
      this.isAuthenticated = false;
      this.impersonating = false;
      this.originalUserId = null;
      
      LocalStorage.remove('token');
      LocalStorage.remove('impersonating');
      LocalStorage.remove('original_user_id');
      
      // Don't redirect here - let the component/router handle it
      // This prevents issues with token expiration handling
    },

    /**
     * Logout user
     */
    async logout() {
      // Save token from localStorage before clearing (since clearAuth removes it)
      const token = LocalStorage.getItem('token');
      
      // Clear auth state first to prevent interceptor loops
      this.clearAuth();
      
      // Try to call logout API if we had a token (but don't wait for it)
      // This is best-effort only, errors are ignored to prevent loops
      if (token) {
        try {
          // Use a separate axios instance to avoid interceptor
          const axios = (await import('axios')).default;
          const { API_BASE_URL } = await import('src/config/api');
          const logoutApi = axios.create({
            baseURL: API_BASE_URL,
            timeout: 5000,
          });
          
          logoutApi.defaults.headers.common['Authorization'] = `Bearer ${token}`;
          logoutApi.post('/auth/logout').catch(() => {
            // Ignore errors - we're already logged out locally
          });
        } catch (error) {
          // Ignore errors - we're already logged out locally
        }
      }
    },

    /**
     * Refresh user data
     */
    async fetchUser() {
      try {
        const response = await authService.me();
        
        if (response.success && response.data) {
          this.user = response.data.user;
          this.roles = response.data.roles || [];
          this.isAuthenticated = true;
          return { success: true };
        }
        
        return { success: false };
      } catch (error) {
        // Don't logout here - let the API interceptor handle 401 errors
        // This prevents premature logout during page refresh
        if (error.response?.status === 401) {
          // Token is invalid, but don't logout yet - let interceptor handle it
          return { success: false };
        }
        return { success: false };
      }
    },

    /**
     * Check authentication status
     */
    async checkAuth() {
      if (!this.token) {
        return false;
      }

      // If user data is not loaded, fetch it
      if (!this.user) {
        const result = await this.fetchUser();
        return result.success;
      }

      return this.isAuthenticated;
    },

    /**
     * Refresh JWT token
     * Note: This method is called from the API interceptor
     * The actual refresh is handled there to avoid circular dependencies
     */
    async refreshToken() {
      // This method is kept for compatibility but the actual refresh
      // is handled in the API interceptor to avoid circular dependencies
      return { success: false, message: 'Use API interceptor for token refresh' };
    },
  },
});

