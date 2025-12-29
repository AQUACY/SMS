<template>
  <div v-if="unreadAnnouncements.length > 0" class="announcement-marquee-container">
    <div
      v-for="announcement in unreadAnnouncements"
      :key="announcement.id"
      :class="['announcement-marquee', getPriorityClass(announcement.priority)]"
    >
      <div class="marquee-content">
        <q-icon name="campaign" size="20px" class="q-mr-sm marquee-icon" />
        <div class="marquee-wrapper">
          <div class="marquee-text">
            <span class="text-weight-bold q-mr-md">{{ announcement.title }}:</span>
            <span>{{ announcement.message }}</span>
            <span class="marquee-spacer"> • </span>
            <span class="text-weight-bold q-mr-md">{{ announcement.title }}:</span>
            <span>{{ announcement.message }}</span>
          </div>
        </div>
      </div>
      <q-btn
        flat
        dense
        round
        icon="close"
        size="sm"
        @click="markAsRead(announcement.id)"
        class="marquee-close-btn"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
import { useNotificationStore } from 'src/stores/notifications';

const notificationStore = useNotificationStore();

const unreadAnnouncements = computed(() => {
  return notificationStore.unreadAnnouncements;
});

function getPriorityClass(priority) {
  const classes = {
    urgent: 'bg-red-1 text-red-8 border-left-red',
    high: 'bg-orange-1 text-orange-8 border-left-orange',
    normal: 'bg-blue-1 text-blue-8 border-left-blue',
    low: 'bg-grey-2 text-grey-8 border-left-grey',
  };
  return classes[priority] || classes.normal;
}

async function markAsRead(notificationId) {
  await notificationStore.markAsRead(notificationId);
}

onMounted(() => {
  notificationStore.fetchAnnouncements();
  // Refresh announcements every 30 seconds
  const interval = setInterval(() => {
    notificationStore.fetchAnnouncements();
  }, 30000);
  
  onUnmounted(() => {
    clearInterval(interval);
  });
});
</script>

<style lang="scss" scoped>
.announcement-marquee-container {
  position: sticky;
  top: 0;
  z-index: 1000;
  width: 100%;
  overflow: hidden;
}

.announcement-marquee {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 16px;
  width: 100%;
  overflow: hidden;
  position: relative;
  animation: slideDown 0.3s ease-out;
  border-bottom: 2px solid;
}

.marquee-content {
  display: flex;
  align-items: center;
  flex: 1;
  overflow: hidden;
  position: relative;
}

.marquee-icon {
  flex-shrink: 0;
}

.marquee-wrapper {
  flex: 1;
  overflow: hidden;
  white-space: nowrap;
}

.marquee-text {
  display: inline-block;
  white-space: nowrap;
  animation: scroll 20s linear infinite;
}

.marquee-spacer {
  margin: 0 20px;
}

.marquee-close-btn {
  flex-shrink: 0;
  margin-left: 16px;
}

.bg-red-1 {
  background-color: #fff5f5;
  color: #c92a2a;
  border-bottom-color: #c92a2a;
}

.bg-orange-1 {
  background-color: #fff4e6;
  color: #fd7e14;
  border-bottom-color: #fd7e14;
}

.bg-blue-1 {
  background-color: #e7f5ff;
  color: #0d6efd;
  border-bottom-color: #0d6efd;
}

.bg-grey-2 {
  background-color: #f8f9fa;
  color: #6c757d;
  border-bottom-color: #6c757d;
}

@keyframes scroll {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(calc(-50% - 10px));
  }
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

// Pause animation on hover
.announcement-marquee:hover .marquee-text {
  animation-play-state: paused;
}
</style>

