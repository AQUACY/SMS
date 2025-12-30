<template>
  <div class="notification-list-container">
    <div v-if="notifications && notifications.length > 0" class="notifications-grid">
      <q-card
        v-for="notification in notifications"
        :key="notification?.id || Math.random()"
        class="notification-card-modern"
        :class="{ 'notification-unread': !notification?.is_read }"
        @click="handleClick(notification)"
      >
        <q-card-section class="notification-card-content">
          <div class="notification-icon-wrapper" :style="{ backgroundColor: getIconBg(notification?.type) }">
            <q-icon
              :name="getNotificationIcon(notification?.type)"
              :color="getNotificationColor(notification?.type)"
              size="24px"
            />
          </div>
          <div class="notification-content">
            <div class="notification-header">
              <div class="notification-title">{{ notification?.title || '' }}</div>
              <q-badge v-if="!notification?.is_read" color="primary" rounded class="unread-badge" />
            </div>
            <div class="notification-message">{{ notification?.message || '' }}</div>
            <div class="notification-footer">
              <div class="notification-time">{{ formatTime(notification?.created_at) }}</div>
              <q-btn
                flat
                dense
                round
                icon="close"
                size="sm"
                class="close-btn"
                @click.stop="markAsRead(notification?.id)"
              />
            </div>
          </div>
        </q-card-section>
      </q-card>
    </div>

    <div v-else class="text-center q-pa-xl text-grey-6">
      <q-icon name="notifications_off" size="64px" class="q-mb-md" />
      <div class="text-h6">No notifications</div>
      <div class="text-body2 q-mt-sm">You're all caught up!</div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';

const props = defineProps({
  notifications: {
    type: Array,
    required: false,
    default: () => [],
  },
});

const emit = defineEmits(['mark-read']);

const router = useRouter();

function getNotificationIcon(type) {
  const icons = {
    payment: 'payment',
    subscription: 'subscriptions',
    result: 'assignment',
    attendance: 'event',
    announcement: 'campaign',
    general: 'notifications',
  };
  return icons[type] || 'notifications';
}

function getNotificationColor(type) {
  const colors = {
    payment: 'green',
    subscription: 'blue',
    result: 'purple',
    attendance: 'orange',
    announcement: 'red',
    general: 'grey',
  };
  return colors[type] || 'grey';
}

function getIconBg(type) {
  const bgMap = {
    payment: 'rgba(76, 175, 80, 0.1)',
    subscription: 'rgba(33, 150, 243, 0.1)',
    result: 'rgba(156, 39, 176, 0.1)',
    attendance: 'rgba(255, 152, 0, 0.1)',
    announcement: 'rgba(244, 67, 54, 0.1)',
    general: 'rgba(158, 158, 158, 0.1)',
  };
  return bgMap[type] || 'rgba(158, 158, 158, 0.1)';
}

function formatTime(dateString) {
  if (!dateString) return '';
  try {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffSecs = Math.floor(diffMs / 1000);
    const diffMins = Math.floor(diffSecs / 60);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);
    
    if (diffSecs < 60) return 'just now';
    if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    return date.toLocaleDateString();
  } catch {
    return '';
  }
}

function handleClick(notification) {
  if (!notification || !notification.id) return;
  
  if (!notification.is_read) {
    emit('mark-read', notification.id);
  }
  
  // Navigate based on notification type
  if (notification.data) {
    if (notification.data.payment_id) {
      router.push(`/app/payments/${notification.data.payment_id}`);
    } else if (notification.data.student_id) {
      router.push(`/app/students/${notification.data.student_id}`);
    }
  }
}

function markAsRead(notificationId) {
  if (notificationId) {
    emit('mark-read', notificationId);
  }
}
</script>

<style lang="scss" scoped>
.notification-list-container {
  width: 100%;
}

.notifications-grid {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.notification-card-modern {
  border-radius: var(--radius-lg);
  border: none;
  box-shadow: var(--shadow-sm);
  transition: all var(--transition-base);
  background: var(--bg-card);
  overflow: hidden;
  cursor: pointer;
  
  &:active {
    transform: scale(0.98);
  }
  
  @media (min-width: 768px) {
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md);
    
    &:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }
  }
  
  &.notification-unread {
    background: rgba(25, 118, 210, 0.03);
    border-left: 3px solid #1976d2;
    
    @media (max-width: 599px) {
      border-left-width: 2px;
    }
  }
}

.notification-card-content {
  padding: var(--spacing-md);
  display: flex;
  align-items: flex-start;
  gap: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.notification-icon-wrapper {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  
  @media (min-width: 768px) {
    width: 56px;
    height: 56px;
    border-radius: var(--radius-lg);
  }
}

.notification-content {
  flex: 1;
  min-width: 0;
}

.notification-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--spacing-xs);
  gap: var(--spacing-sm);
}

.notification-title {
  font-size: var(--font-size-base);
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.3;
  flex: 1;
  
  @media (min-width: 768px) {
    font-size: var(--font-size-lg);
  }
}

.unread-badge {
  flex-shrink: 0;
}

.notification-message {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  line-height: 1.5;
  margin-bottom: var(--spacing-sm);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.notification-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: var(--spacing-xs);
}

.notification-time {
  font-size: var(--font-size-xs);
  color: var(--text-tertiary);
}

.close-btn {
  min-width: 32px;
  min-height: 32px;
  width: 32px;
  height: 32px;
  opacity: 0.6;
  
  &:hover {
    opacity: 1;
  }
}
</style>

