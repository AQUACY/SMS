<template>
  <q-page class="notifications-page">
    <div class="page-header">
      <div class="header-content">
        <div class="header-title">
          <div class="text-h5 text-weight-bold">Notifications</div>
          <div class="text-body2 text-grey-7">View and manage your notifications</div>
        </div>
        <div class="header-actions">
          <q-btn
            v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
            color="positive"
            :label="$q.screen.gt.xs ? 'Send Notification' : ''"
            icon="send"
            unelevated
            @click="$router.push('/app/notifications/send')"
            class="action-btn"
          />
          <q-btn
            v-if="unreadCount > 0"
            color="primary"
            :label="$q.screen.gt.xs ? 'Mark All Read' : ''"
            icon="done_all"
            unelevated
            @click="markAllAsRead"
            class="action-btn"
          />
        </div>
      </div>
    </div>

    <q-card class="widget-card">
      <q-card-section class="card-section">
        <q-tabs
          v-model="activeTab"
          class="notification-tabs"
          active-color="primary"
          indicator-color="primary"
          :breakpoint="0"
          align="left"
        >
          <q-tab name="all" label="All" />
          <q-tab name="unread" label="Unread" :badge="unreadCount > 0 ? unreadCount : null" />
          <q-tab name="announcements" label="Announcements" />
          <q-tab
            v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
            name="sent"
            label="Sent"
          />
        </q-tabs>

        <q-tab-panels v-model="activeTab" animated>
          <q-tab-panel name="all">
            <NotificationList :notifications="notifications" @mark-read="handleMarkRead" />
          </q-tab-panel>

          <q-tab-panel name="unread">
            <NotificationList :notifications="unreadNotifications" @mark-read="handleMarkRead" />
          </q-tab-panel>

          <q-tab-panel name="announcements">
            <NotificationList :notifications="announcements" @mark-read="handleMarkRead" />
          </q-tab-panel>

          <q-tab-panel v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin" name="sent">
            <SentNotificationsList v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin" />
          </q-tab-panel>
        </q-tab-panels>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useNotificationStore } from 'src/stores/notifications';
import { useAuthStore } from 'src/stores/auth';
import NotificationList from 'src/components/notifications/NotificationList.vue';
import SentNotificationsList from 'src/components/notifications/SentNotificationsList.vue';

const notificationStore = useNotificationStore();
const authStore = useAuthStore();
const activeTab = ref('all');

const notifications = computed(() => notificationStore.notifications || []);
const unreadNotifications = computed(() => notificationStore.unreadNotifications || []);
const announcements = computed(() => notificationStore.announcements || []);
const unreadCount = computed(() => notificationStore.unreadCount || 0);

async function handleMarkRead(notificationId) {
  await notificationStore.markAsRead(notificationId);
}

async function markAllAsRead() {
  await notificationStore.markAllAsRead();
}

onMounted(() => {
  notificationStore.fetchNotifications();
  notificationStore.fetchAnnouncements();
});
</script>

<style lang="scss" scoped>
.notifications-page {
  padding: 12px;
  
  @media (min-width: 600px) {
    padding: 24px;
  }
}

.page-header {
  margin-bottom: 16px;
  
  @media (min-width: 600px) {
    margin-bottom: 24px;
  }
}

.header-content {
  display: flex;
  flex-direction: column;
  gap: 12px;
  
  @media (min-width: 600px) {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }
}

.header-title {
  flex: 1;
}

.header-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  
  @media (max-width: 599px) {
    width: 100%;
    
    .action-btn {
      flex: 1;
      min-width: 0;
    }
  }
}

.widget-card {
  border-radius: 12px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
  
  @media (min-width: 600px) {
    border-radius: 16px;
  }
}

.card-section {
  padding: 8px;
  
  @media (min-width: 600px) {
    padding: 16px;
  }
}

.notification-tabs {
  :deep(.q-tabs__content) {
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    -ms-overflow-style: none;
    
    &::-webkit-scrollbar {
      display: none;
    }
  }
  
  :deep(.q-tab) {
    min-width: auto;
    padding: 8px 12px;
    font-size: 14px;
    
    @media (min-width: 600px) {
      padding: 12px 16px;
      font-size: 16px;
    }
  }
}
</style>
