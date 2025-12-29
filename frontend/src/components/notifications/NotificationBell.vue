<template>
  <q-btn flat round dense icon="notifications" color="grey-8" @click="showNotifications = true">
    <q-badge v-if="unreadCount > 0" color="red" floating>{{ unreadCount > 99 ? '99+' : unreadCount }}</q-badge>
    
    <q-menu
      v-model="showNotifications"
      anchor="bottom right"
      self="top right"
      :offset="[0, 8]"
      class="notification-menu"
      :max-height="'400px'"
      :width="'350px'"
    >
      <q-toolbar class="bg-primary text-white">
        <q-toolbar-title>Notifications</q-toolbar-title>
        <q-btn
          flat
          dense
          round
          icon="close"
          @click="showNotifications = false"
        />
      </q-toolbar>

      <q-list v-if="notifications.length > 0" class="notification-list">
        <q-item
          v-for="notification in notifications"
          :key="notification.id"
          clickable
          v-ripple
          :class="{ 'notification-unread': !notification.is_read }"
          @click="handleNotificationClick(notification)"
        >
          <q-item-section avatar>
            <q-icon
              :name="getNotificationIcon(notification.type)"
              :color="getNotificationColor(notification.type)"
            />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-weight-medium">{{ notification.title }}</q-item-label>
            <q-item-label caption lines="2">{{ notification.message }}</q-item-label>
            <q-item-label caption class="text-grey-6 q-mt-xs">
              {{ formatTime(notification.created_at) }}
            </q-item-label>
          </q-item-section>
          <q-item-section side v-if="!notification.is_read">
            <q-badge color="primary" rounded />
          </q-item-section>
        </q-item>
      </q-list>

      <div v-else class="text-center q-pa-lg text-grey-6">
        <q-icon name="notifications_off" size="48px" class="q-mb-md" />
        <div>No notifications</div>
      </div>

      <q-separator />

      <q-card-actions align="right">
        <q-btn
          flat
          dense
          label="View All"
          color="primary"
          @click="goToNotifications"
          class="q-mr-sm"
        />
        <q-btn
          v-if="unreadCount > 0"
          flat
          dense
          label="Mark All Read"
          color="grey-7"
          @click="markAllAsRead"
        />
      </q-card-actions>
    </q-menu>
  </q-btn>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useNotificationStore } from 'src/stores/notifications';

const router = useRouter();
const notificationStore = useNotificationStore();

const showNotifications = ref(false);
const notifications = computed(() => notificationStore.notifications.slice(0, 10));
const unreadCount = computed(() => notificationStore.unreadCount);

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

async function handleNotificationClick(notification) {
  if (!notification.is_read) {
    await notificationStore.markAsRead(notification.id);
  }
  
  // Navigate based on notification type
  if (notification.data) {
    if (notification.data.payment_id) {
      router.push(`/app/payments/${notification.data.payment_id}`);
    } else if (notification.data.student_id) {
      router.push(`/app/students/${notification.data.student_id}`);
    }
  }
  
  showNotifications.value = false;
}

function goToNotifications() {
  router.push('/app/notifications');
  showNotifications.value = false;
}

async function markAllAsRead() {
  await notificationStore.markAllAsRead();
}

onMounted(() => {
  notificationStore.fetchNotifications({ per_page: 10 });
  notificationStore.startPolling();
});

onUnmounted(() => {
  notificationStore.stopPolling();
});
</script>

<style lang="scss" scoped>
.notification-menu {
  border-radius: 12px;
  overflow: hidden;
}

.notification-list {
  max-height: 400px;
  overflow-y: auto;
}

.notification-unread {
  background: rgba(25, 118, 210, 0.05);
  border-left: 3px solid #1976d2;
}

:deep(.q-item) {
  padding: 12px 16px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  
  &:hover {
    background: rgba(0, 0, 0, 0.02);
  }
}
</style>

