import api from './api';

export const authService = {
  /**
   * Login user
   */
  async login(credentials) {
    const response = await api.post('/auth/login', credentials);
    return response.data;
  },

  /**
   * Register new user
   */
  async register(data) {
    const response = await api.post('/auth/register', data);
    return response.data;
  },

  /**
   * Logout user
   */
  async logout() {
    await api.post('/auth/logout');
  },

  /**
   * Refresh JWT token
   */
  async refreshToken() {
    const response = await api.post('/auth/refresh');
    return response.data;
  },

  /**
   * Get current authenticated user
   */
  async me() {
    const response = await api.get('/auth/me');
    return response.data;
  },

  /**
   * Request password reset
   */
  async forgotPassword(email) {
    const response = await api.post('/auth/forgot-password', { email });
    return response.data;
  },

  /**
   * Reset password
   */
  async resetPassword(data) {
    const response = await api.post('/auth/reset-password', data);
    return response.data;
  },
};

