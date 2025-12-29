<template>
  <div>
    <q-list v-if="notifications && notifications.length > 0" class="notification-list">
      <q-item
        v-for="notification in notifications"
        :key="notification?.id || Math.random()"
        clickable
        v-ripple
        :class="{ 'notification-unread': !notification?.is_read }"
        @click="handleClick(notification)"
      >
        <q-item-section avatar>
          <q-icon
            :name="getNotificationIcon(notification?.type)"
            :color="getNotificationColor(notification?.type)"
            size="24px"
          />
        </q-item-section>
        <q-item-section>
          <q-item-label class="text-weight-medium">{{ notification?.title || '' }}</q-item-label>
          <q-item-label caption lines="2">{{ notification?.message || '' }}</q-item-label>
          <q-item-label caption class="text-grey-6 q-mt-xs">
            {{ formatTime(notification?.created_at) }}
          </q-item-label>
        </q-item-section>
        <q-item-section side>
          <div class="column items-end q-gutter-xs">
            <q-badge v-if="!notification?.is_read" color="primary" rounded />
            <q-btn
              flat
              dense
              round
              icon="close"
              size="sm"
              @click.stop="markAsRead(notification?.id)"
            />
          </div>
        </q-item-section>
      </q-item>
    </q-list>

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
.notification-list {
  :deep(.q-item) {
    padding: 12px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    min-height: auto;
    
    @media (min-width: 600px) {
      padding: 16px;
    }
    
    &:hover {
      background: rgba(0, 0, 0, 0.02);
    }
    
    .q-item__section--avatar {
      min-width: 40px;
      padding-right: 12px;
      
      @media (min-width: 600px) {
        min-width: 48px;
        padding-right: 16px;
      }
    }
    
    .q-item__section--side {
      padding-left: 8px;
      
      @media (min-width: 600px) {
        padding-left: 16px;
      }
    }
    
    .q-btn {
      min-width: 36px;
      min-height: 36px;
      width: 36px;
      height: 36px;
      
      @media (min-width: 600px) {
        min-width: 32px;
        min-height: 32px;
        width: 32px;
        height: 32px;
      }
    }
  }
}

.notification-unread {
  background: rgba(25, 118, 210, 0.05);
  border-left: 3px solid #1976d2;
  
  @media (max-width: 599px) {
    border-left-width: 2px;
  }
}
</style>

