import api from './api';

export const notificationService = {
  /**
   * Get all notifications
   */
  async getNotifications(params = {}) {
    try {
      const response = await api.get('/notifications', { params });
      return response.data;
    } catch (error) {
      console.error('Error fetching notifications:', error);
      throw error;
    }
  },

  /**
   * Get unread notifications
   */
  async getUnread() {
    try {
      const response = await api.get('/notifications/unread');
      return response.data;
    } catch (error) {
      console.error('Error fetching unread notifications:', error);
      throw error;
    }
  },

  /**
   * Get announcement notifications
   */
  async getAnnouncements(params = {}) {
    try {
      const response = await api.get('/notifications/announcements', { params });
      return response.data;
    } catch (error) {
      console.error('Error fetching announcements:', error);
      throw error;
    }
  },

  /**
   * Mark notification as read
   */
  async markAsRead(notificationId) {
    try {
      const response = await api.post(`/notifications/${notificationId}/read`);
      return response.data;
    } catch (error) {
      console.error('Error marking notification as read:', error);
      throw error;
    }
  },

  /**
   * Mark all notifications as read
   */
  async markAllAsRead() {
    try {
      const response = await api.post('/notifications/read-all');
      return response.data;
    } catch (error) {
      console.error('Error marking all notifications as read:', error);
      throw error;
    }
  },

  /**
   * Admin: Send notification
   */
  async sendNotification(data) {
    try {
      const response = await api.post('/notifications/send', data);
      return response.data;
    } catch (error) {
      console.error('Error sending notification:', error);
      throw error;
    }
  },

  /**
   * Admin: Get recipients list
   */
  async getRecipients(params = {}) {
    try {
      const response = await api.get('/notifications/recipients', { params });
      return response.data;
    } catch (error) {
      console.error('Error fetching recipients:', error);
      throw error;
    }
  },

  /**
   * Admin: Get sent notifications
   */
  async getSentNotifications() {
    try {
      const response = await api.get('/notifications/sent');
      return response.data;
    } catch (error) {
      console.error('Error fetching sent notifications:', error);
      throw error;
    }
  },

  /**
   * Admin: Update a sent notification
   */
  async updateSentNotification(id, data) {
    try {
      const response = await api.put(`/notifications/sent/${id}`, data);
      return response.data;
    } catch (error) {
      console.error('Error updating notification:', error);
      throw error;
    }
  },

  /**
   * Admin: Delete a sent notification
   */
  async deleteSentNotification(id) {
    try {
      const response = await api.delete(`/notifications/sent/${id}`);
      return response.data;
    } catch (error) {
      console.error('Error deleting notification:', error);
      throw error;
    }
  },
};

