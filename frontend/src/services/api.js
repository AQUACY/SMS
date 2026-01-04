import axios from 'axios';
import { useAuthStore } from 'src/stores/auth';
import { Notify, LocalStorage } from 'quasar';
import { API_BASE_URL } from 'src/config/api';

// Create axios instance
// API_URL is injected at build time via quasar.config.js rawDefine
const api = axios.create({
  baseURL: API_BASE_URL,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Flag to prevent multiple simultaneous refresh attempts
let isRefreshing = false;
let failedQueue = [];

const processQueue = (error, token = null) => {
  failedQueue.forEach(prom => {
    if (error) {
      prom.reject(error);
    } else {
      prom.resolve(token);
    }
  });
  
  failedQueue = [];
};

// Request interceptor - Add JWT token to requests
api.interceptors.request.use(
  (config) => {
    const authStore = useAuthStore();
    // Get token from store first, fallback to localStorage for page refresh scenarios
    const token = authStore.token || (typeof window !== 'undefined' ? localStorage.getItem('token') : null);

    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
      // Update store token if it was missing but found in localStorage
      if (!authStore.token && token) {
        authStore.token = token;
      }
    }

    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor - Handle errors globally
api.interceptors.response.use(
  (response) => {
    // Don't process blob responses - let them pass through
    if (response.config.responseType === 'blob') {
      return response;
    }
    return response;
  },
  async (error) => {
    const authStore = useAuthStore();
    const originalRequest = error.config;

    if (error.response) {
      const { status, data } = error.response;

      // Handle 401 Unauthorized - Token expired or invalid
      if (status === 401) {
        const requestUrl = (originalRequest && originalRequest.url) || '';
        
        // Don't retry refresh token or logout endpoints - these should fail gracefully
        if (requestUrl.includes('/auth/refresh') || requestUrl.includes('/auth/logout')) {
          // These endpoints returning 401 means token is invalid, just clear auth state
          processQueue(error, null);
          authStore.clearAuth();
          Notify.create({
            type: 'negative',
            message: 'Session expired. Please login again.',
            position: 'top',
          });
          // Redirect to login
          if (typeof window !== 'undefined' && !window.location.pathname.includes('/login')) {
            window.location.href = '/login';
          }
          return Promise.reject(error);
        }

        // Prevent retry loops - if we already tried to refresh, don't try again
        if (originalRequest._retry) {
          // Already tried to refresh, clear auth
          processQueue(error, null);
          authStore.clearAuth();
          Notify.create({
            type: 'negative',
            message: 'Session expired. Please login again.',
            position: 'top',
          });
          // Redirect to login
          if (typeof window !== 'undefined' && !window.location.pathname.includes('/login')) {
            window.location.href = '/login';
          }
          return Promise.reject(error);
        }

        // If we're already refreshing, queue this request
        if (isRefreshing) {
          return new Promise((resolve, reject) => {
            failedQueue.push({ resolve, reject });
          })
            .then(token => {
              originalRequest.headers.Authorization = `Bearer ${token}`;
              return api(originalRequest);
            })
            .catch(err => {
              return Promise.reject(err);
            });
        }

        // Start token refresh
        isRefreshing = true;
        originalRequest._retry = true;

        try {
          // Get the current token (even if expired) for refresh request
          const currentToken = authStore.token;
          
          if (!currentToken) {
            // No token to refresh, logout
            processQueue(error, null);
            authStore.clearAuth();
            return Promise.reject(error);
          }
          
          // Create a temporary axios instance for refresh to bypass interceptor
          const refreshApi = axios.create({
            baseURL: API_BASE_URL,
            timeout: 30000,
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            },
          });
          
          // Add current token to refresh request
          refreshApi.defaults.headers.common['Authorization'] = `Bearer ${currentToken}`;
          
          // Attempt to refresh token
          const refreshResponse = await refreshApi.post('/auth/refresh');
          
          if (refreshResponse.data.success && refreshResponse.data.data) {
            const newToken = refreshResponse.data.data.token;
            
            // Update token in store
            authStore.token = newToken;
            LocalStorage.set('token', newToken);
            
            // Update the token in the original request
            originalRequest.headers.Authorization = `Bearer ${newToken}`;
            
            // Process queued requests
            processQueue(null, newToken);
            
            // Retry the original request
            return api(originalRequest);
          } else {
            // Refresh failed, logout
            processQueue(error, null);
            authStore.clearAuth();
            Notify.create({
              type: 'negative',
              message: 'Session expired. Please login again.',
              position: 'top',
            });
            // Redirect to login
            if (typeof window !== 'undefined' && !window.location.pathname.includes('/login')) {
              window.location.href = '/login';
            }
            return Promise.reject(error);
          }
        } catch (refreshError) {
          // Refresh failed, logout
          processQueue(refreshError, null);
          authStore.clearAuth();
          Notify.create({
            type: 'negative',
            message: 'Session expired. Please login again.',
            position: 'top',
          });
          // Redirect to login
          if (typeof window !== 'undefined' && !window.location.pathname.includes('/login')) {
            window.location.href = '/login';
          }
          return Promise.reject(refreshError);
        } finally {
          isRefreshing = false;
        }
      }
      // Handle 403 Forbidden - Insufficient permissions
      else if (status === 403) {
        Notify.create({
          type: 'warning',
          message: data.message || 'You do not have permission to perform this action.',
          position: 'top',
        });
      }
      // Handle 422 Validation errors
      else if (status === 422) {
        const errors = data.errors || {};
        const firstError = Object.values(errors)[0];
        Notify.create({
          type: 'negative',
          message: Array.isArray(firstError) ? firstError[0] : firstError || 'Validation error',
          position: 'top',
        });
      }
      // Handle other errors
      else if (status >= 500) {
        Notify.create({
          type: 'negative',
          message: 'Server error. Please try again later.',
          position: 'top',
        });
      }
      // Handle subscription required
      else if (data.requires_payment) {
        // This will be handled by the component
        return Promise.reject(error);
      }
    } else if (error.request) {
      // Network error - but skip notification for blob responses
      if (originalRequest?.responseType !== 'blob') {
        Notify.create({
          type: 'negative',
          message: 'Network error. Please check your connection.',
          position: 'top',
        });
      }
    }

    return Promise.reject(error);
  }
);

export default api;

