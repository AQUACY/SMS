<template>
  <q-card class="noticeboard-card" @click="goToNotifications">
    <q-card-section class="noticeboard-header">
      <div class="header-content">
        <div class="header-icon-wrapper" :style="{ backgroundColor: 'rgba(156, 39, 176, 0.1)' }">
          <q-icon name="campaign" size="24px" color="primary" />
        </div>
        <div class="header-text">
          <div class="header-title">Noticeboard</div>
          <div class="header-subtitle">Announcements & Updates</div>
        </div>
        <q-badge
          v-if="unreadCount > 0"
          color="red"
          :label="unreadCount"
          class="header-badge"
        />
      </div>
    </q-card-section>

    <q-card-section v-if="loading" class="noticeboard-content">
      <q-skeleton type="text" width="100%" height="20px" class="q-mb-sm" />
      <q-skeleton type="text" width="80%" height="20px" />
    </q-card-section>

    <q-card-section v-else-if="announcements.length === 0" class="noticeboard-content">
      <div class="empty-state">
        <q-icon name="check_circle" size="48px" color="positive" />
        <div class="empty-text">All caught up!</div>
        <div class="empty-subtext">No unread announcements</div>
      </div>
    </q-card-section>

    <q-card-section v-else class="noticeboard-content">
      <div class="announcements-list">
        <div
          v-for="announcement in displayAnnouncements"
          :key="announcement.id"
          class="announcement-item unread"
          @click.stop="handleAnnouncementClick(announcement)"
        >
          <div class="announcement-priority" :class="getPriorityClass(announcement.priority)"></div>
          <div class="announcement-content">
            <div class="announcement-title-row">
              <div class="announcement-title">{{ announcement.title }}</div>
              <q-badge
                color="red"
                size="sm"
                class="unread-badge"
                label="NEW"
              />
            </div>
            <div class="announcement-message">{{ truncateMessage(announcement.message) }}</div>
            <div class="announcement-meta">
              <q-icon name="schedule" size="12px" color="grey-6" />
              <span class="announcement-time">{{ formatTime(announcement.created_at) }}</span>
              <q-icon v-if="announcement.type === 'payment'" name="payment" size="12px" color="primary" class="q-ml-xs" />
            </div>
          </div>
        </div>
      </div>
      
      <div v-if="announcements.length > maxDisplay" class="view-all">
        <q-btn
          flat
          dense
          :label="`View All (${unreadCount} unread)`"
          icon-right="arrow_forward"
          color="primary"
          class="full-width"
          @click.stop="goToNotifications"
        />
      </div>
    </q-card-section>
  </q-card>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useNotificationStore } from 'src/stores/notifications';
import { useAuthStore } from 'src/stores/auth';
import { useQuasar } from 'quasar';
import { notificationService } from 'src/services/notifications';
import api from 'src/services/api';

const router = useRouter();
const notificationStore = useNotificationStore();
const authStore = useAuthStore();
const $q = useQuasar();

const loading = ref(false);
const allAnnouncements = ref([]);
const paymentNotifications = ref([]);
const maxDisplay = ref(3);
const refreshInterval = ref(null);

const announcements = computed(() => {
  // Only use unread announcements from our fetch
  const unread = allAnnouncements.value.length > 0 ? allAnnouncements.value : [];
  
  // For school admins and accounts managers, also include unread payment notifications
  if (authStore.isSchoolAdmin || authStore.isAccountsManager) {
    return [...unread, ...paymentNotifications.value];
  }
  
  return unread;
});

const unreadCount = computed(() => {
  return announcements.value.length;
});

const displayAnnouncements = computed(() => {
  // Show only unread announcements, sorted by priority then date
  const sorted = [...announcements.value].sort((a, b) => {
    const priorityOrder = { urgent: 4, high: 3, normal: 2, low: 1 };
    const priorityDiff = (priorityOrder[b.priority] || 2) - (priorityOrder[a.priority] || 2);
    if (priorityDiff !== 0) return priorityDiff;
    return new Date(b.created_at) - new Date(a.created_at);
  });
  
  return sorted.slice(0, maxDisplay.value);
});

function getPriorityClass(priority) {
  const classes = {
    urgent: 'priority-urgent',
    high: 'priority-high',
    normal: 'priority-normal',
    low: 'priority-low',
  };
  return classes[priority] || 'priority-normal';
}

function truncateMessage(message) {
  if (!message) return '';
  return message.length > 100 ? message.substring(0, 100) + '...' : message;
}

function formatTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);

  if (diffMins < 1) return 'Just now';
  if (diffMins < 60) return `${diffMins}m ago`;
  if (diffHours < 24) return `${diffHours}h ago`;
  if (diffDays < 7) return `${diffDays}d ago`;
  
  return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
}

async function fetchAllAnnouncements() {
  loading.value = true;
  try {
    // Fetch ONLY unread announcements for the noticeboard
    const unreadResponse = await api.get('/notifications/announcements', {
      params: { include_read: false } // Only unread
    });
    
    if (unreadResponse.data.success) {
      allAnnouncements.value = unreadResponse.data.data || [];
    }
    
    // For school admins and accounts managers, also fetch unread payment notifications
    if (authStore.isSchoolAdmin || authStore.isAccountsManager) {
      try {
        const paymentResponse = await api.get('/notifications', {
          params: { 
            type: 'payment',
            per_page: 5,
            is_read: false // Only show unread payment notifications
          }
        });
        if (paymentResponse.data.success) {
          paymentNotifications.value = paymentResponse.data.data?.data || [];
        }
      } catch (error) {
        console.error('Failed to fetch payment notifications:', error);
      }
    }
  } catch (error) {
    console.error('Failed to fetch announcements:', error);
  } finally {
    loading.value = false;
  }
}

function handleAnnouncementClick(announcement) {
  if (!announcement.is_read) {
    notificationStore.markAsRead(announcement.id);
  }
  router.push('/app/notifications?tab=announcements');
}

function goToNotifications() {
  router.push('/app/notifications?tab=announcements');
}

onMounted(() => {
  fetchAllAnnouncements();
  // Also fetch from store
  notificationStore.fetchAnnouncements();
  
  // Refresh every 30 seconds
  refreshInterval.value = setInterval(() => {
    fetchAllAnnouncements();
    notificationStore.fetchAnnouncements();
  }, 30000);
});

onUnmounted(() => {
  if (refreshInterval.value) {
    clearInterval(refreshInterval.value);
  }
});
</script>

<style lang="scss" scoped>
.noticeboard-card {
  border-radius: var(--radius-lg);
  border: none;
  box-shadow: var(--shadow-md);
  transition: all var(--transition-base);
  background: var(--bg-card);
  cursor: pointer;
  margin-bottom: var(--spacing-md);
  grid-column: 1 / -1; // Span full width
  
  &:active {
    transform: scale(0.98);
  }
  
  @media (min-width: 768px) {
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
    
    &:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-xl);
    }
  }
}

.noticeboard-header {
  padding: var(--spacing-md);
  border-bottom: 1px solid var(--border-light);
  background: linear-gradient(135deg, rgba(156, 39, 176, 0.05) 0%, rgba(156, 39, 176, 0.02) 100%);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.header-content {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
}

.header-icon-wrapper {
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

.header-text {
  flex: 1;
  min-width: 0;
}

.header-title {
  font-size: var(--font-size-lg);
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.2;
  margin-bottom: 2px;
  
  @media (min-width: 768px) {
    font-size: var(--font-size-xl);
  }
}

.header-subtitle {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  line-height: 1.3;
}

.header-badge {
  flex-shrink: 0;
}

.noticeboard-content {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-xl);
  text-align: center;
}

.empty-text {
  font-size: var(--font-size-base);
  font-weight: 600;
  color: var(--text-primary);
  margin-top: var(--spacing-md);
}

.empty-subtext {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  margin-top: var(--spacing-xs);
}

.announcements-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

.announcement-item {
  display: flex;
  gap: var(--spacing-md);
  padding: var(--spacing-md);
  border-radius: var(--radius-md);
  background: var(--bg-secondary);
  border-left: 3px solid transparent;
  transition: all var(--transition-base);
  cursor: pointer;
  
  &.unread {
    background: rgba(156, 39, 176, 0.08);
    border-left-color: var(--q-primary);
    border-left-width: 4px;
    box-shadow: 0 2px 4px rgba(156, 39, 176, 0.1);
  }
  
  &.read {
    opacity: 0.7;
    background: var(--bg-secondary);
  }
  
  &:hover {
    background: var(--bg-hover);
    transform: translateX(4px);
    
    &.unread {
      background: rgba(156, 39, 176, 0.12);
      box-shadow: 0 4px 8px rgba(156, 39, 176, 0.15);
    }
  }
  
  @media (min-width: 768px) {
    padding: var(--spacing-md) var(--spacing-lg);
  }
}

.announcement-priority {
  width: 4px;
  border-radius: 2px;
  flex-shrink: 0;
  
  &.priority-urgent {
    background: #f44336;
  }
  
  &.priority-high {
    background: #ff9800;
  }
  
  &.priority-normal {
    background: #2196f3;
  }
  
  &.priority-low {
    background: #9e9e9e;
  }
}

.announcement-content {
  flex: 1;
  min-width: 0;
}

.announcement-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-xs);
}

.announcement-title {
  font-size: var(--font-size-base);
  font-weight: 600;
  color: var(--text-primary);
  line-height: 1.3;
  flex: 1;
  min-width: 0;
}

.unread-badge {
  flex-shrink: 0;
}

.announcement-message {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  line-height: 1.5;
  margin-bottom: var(--spacing-xs);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.announcement-meta {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  font-size: var(--font-size-xs);
  color: var(--text-tertiary);
}

.announcement-time {
  font-size: var(--font-size-xs);
  color: var(--text-tertiary);
}

.all-read-banner {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-sm) var(--spacing-md);
  background: rgba(76, 175, 80, 0.1);
  border-radius: var(--radius-md);
  margin-bottom: var(--spacing-md);
}

.all-read-text {
  font-size: var(--font-size-sm);
  color: #4caf50;
  font-weight: 500;
}

.view-all {
  margin-top: var(--spacing-md);
  padding-top: var(--spacing-md);
  border-top: 1px solid var(--border-light);
}
</style>

