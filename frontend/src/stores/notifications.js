import { defineStore } from 'pinia';
import { notificationService } from 'src/services/notifications';
import { Notify } from 'quasar';

export const useNotificationStore = defineStore('notifications', {
  state: () => ({
    notifications: [],
    announcements: [],
    unreadCount: 0,
    loading: false,
    pollingInterval: null,
  }),

  getters: {
    unreadNotifications: (state) => {
      return state.notifications.filter(n => !n.is_read);
    },
    
    unreadAnnouncements: (state) => {
      return state.announcements.filter(a => !a.is_read);
    },
    
    hasUnread: (state) => {
      return state.unreadCount > 0;
    },
  },

  actions: {
    async fetchNotifications(params = {}) {
      this.loading = true;
      try {
        const response = await notificationService.getNotifications(params);
        if (response.success) {
          this.notifications = response.data.data || [];
          this.unreadCount = this.notifications.filter(n => !n.is_read).length;
        }
      } catch (error) {
        console.error('Failed to fetch notifications:', error);
      } finally {
        this.loading = false;
      }
    },

    async fetchAnnouncements(params = {}) {
      try {
        const response = await notificationService.getAnnouncements(params);
        if (response.success) {
          this.announcements = response.data || [];
        }
      } catch (error) {
        console.error('Failed to fetch announcements:', error);
      }
    },

    async fetchUnread() {
      try {
        const response = await notificationService.getUnread();
        if (response.success) {
          const unread = response.data || [];
          this.unreadCount = unread.length;
          // Merge with existing notifications
          unread.forEach(notification => {
            const index = this.notifications.findIndex(n => n.id === notification.id);
            if (index >= 0) {
              this.notifications[index] = notification;
            } else {
              this.notifications.unshift(notification);
            }
          });
        }
      } catch (error) {
        console.error('Failed to fetch unread notifications:', error);
      }
    },

    async markAsRead(notificationId) {
      try {
        const response = await notificationService.markAsRead(notificationId);
        if (response.success) {
          const index = this.notifications.findIndex(n => n.id === notificationId);
          if (index >= 0) {
            this.notifications[index].is_read = true;
            this.notifications[index].read_at = response.data.read_at;
            this.unreadCount = Math.max(0, this.unreadCount - 1);
          }
          
          // Also update in announcements
          const annIndex = this.announcements.findIndex(a => a.id === notificationId);
          if (annIndex >= 0) {
            this.announcements[annIndex].is_read = true;
            this.announcements[annIndex].read_at = response.data.read_at;
          }
        }
      } catch (error) {
        console.error('Failed to mark notification as read:', error);
        Notify.create({
          type: 'negative',
          message: 'Failed to mark notification as read',
          position: 'top',
        });
      }
    },

    async markAllAsRead() {
      try {
        const response = await notificationService.markAllAsRead();
        if (response.success) {
          this.notifications.forEach(n => {
            n.is_read = true;
            n.read_at = new Date().toISOString();
          });
          this.announcements.forEach(a => {
            a.is_read = true;
            a.read_at = new Date().toISOString();
          });
          this.unreadCount = 0;
        }
      } catch (error) {
        console.error('Failed to mark all notifications as read:', error);
        Notify.create({
          type: 'negative',
          message: 'Failed to mark all notifications as read',
          position: 'top',
        });
      }
    },

    startPolling(interval = 30000) {
      // Poll for new notifications every 30 seconds
      this.pollingInterval = setInterval(() => {
        this.fetchUnread();
        this.fetchAnnouncements();
      }, interval);
    },

    stopPolling() {
      if (this.pollingInterval) {
        clearInterval(this.pollingInterval);
        this.pollingInterval = null;
      }
    },
  },
});

