<template>
  <q-footer
    v-if="isVisible"
    elevated
    class="mobile-bottom-nav"
  >
    <q-toolbar class="nav-toolbar">
      <q-btn
        v-for="item in menuItems"
        :key="item.name"
        flat
        no-caps
        :to="item.path"
        :class="{ 'nav-item-active': $route.path === item.path }"
        class="nav-item"
      >
        <q-icon :name="item.icon" size="24px" />
        <div class="nav-label">{{ item.label }}</div>
      </q-btn>
    </q-toolbar>
  </q-footer>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from 'src/stores/auth';
import { useQuasar } from 'quasar';

const route = useRoute();
const authStore = useAuthStore();
const $q = useQuasar();

const isVisible = computed(() => {
  return $q.screen.lt.md; // Show only on mobile (less than medium breakpoint)
});

const menuItems = computed(() => {
  // Parent-specific menu
  if (authStore.isParent) {
    return [
      { name: 'dashboard', label: 'Home', icon: 'dashboard', path: '/app/dashboard' },
      { name: 'children', label: 'Children', icon: 'child_care', path: '/app/parent/children' },
      { name: 'results', label: 'Results', icon: 'assessment', path: '/app/results' },
      { name: 'subscriptions', label: 'Subs', icon: 'card_membership', path: '/app/parent/subscriptions' },
      { name: 'notifications', label: 'Alerts', icon: 'notifications', path: '/app/notifications' },
    ];
  }

  // Teacher menu
  if (authStore.isTeacher) {
    return [
      { name: 'dashboard', label: 'Home', icon: 'dashboard', path: '/app/dashboard' },
      { name: 'students', label: 'Students', icon: 'people', path: '/app/students' },
      { name: 'attendance', label: 'Attendance', icon: 'checklist', path: '/app/attendance' },
      { name: 'exams', label: 'Exams', icon: 'quiz', path: '/app/exams' },
      { name: 'notifications', label: 'Alerts', icon: 'notifications', path: '/app/notifications' },
    ];
  }

  // Super Admin menu
  if (authStore.isSuperAdmin) {
    return [
      { name: 'dashboard', label: 'Home', icon: 'dashboard', path: '/app/dashboard' },
      { name: 'schools', label: 'Schools', icon: 'school', path: '/app/super-admin/schools' },
      { name: 'notifications', label: 'Alerts', icon: 'notifications', path: '/app/notifications' },
      { name: 'profile', label: 'Profile', icon: 'person', path: '/app/profile' },
    ];
  }

  // School Admin menu (default)
  return [
    { name: 'dashboard', label: 'Home', icon: 'dashboard', path: '/app/dashboard' },
    { name: 'students', label: 'Students', icon: 'people', path: '/app/students' },
    { name: 'attendance', label: 'Attendance', icon: 'checklist', path: '/app/attendance' },
    { name: 'results', label: 'Results', icon: 'assessment', path: '/app/results' },
    { name: 'notifications', label: 'Alerts', icon: 'notifications', path: '/app/notifications' },
  ];
});
</script>

<style lang="scss" scoped>
.mobile-bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  height: 70px;
  backdrop-filter: blur(20px);
  background: rgba(255, 255, 255, 0.85);
  border-top: 1px solid rgba(0, 0, 0, 0.08);
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  
  @media (min-width: 768px) {
    display: none;
  }

  .nav-toolbar {
    height: 100%;
    padding: 0;
    display: flex;
    justify-content: space-around;
    align-items: center;
    background: transparent;
  }

  .nav-item {
    flex: 1;
    flex-direction: column;
    padding: 8px 4px;
    min-width: 0;
    color: rgba(0, 0, 0, 0.6);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    border-radius: 12px;
    margin: 0 2px;
    max-width: 80px;

    &:hover {
      background: rgba(156, 39, 176, 0.08);
      color: rgba(156, 39, 176, 0.9);
    }

    &.nav-item-active {
      color: #9c27b0;
      background: rgba(156, 39, 176, 0.12);

      .nav-label {
        font-weight: 600;
      }

      &::before {
        content: '';
        position: absolute;
        top: -2px;
        left: 50%;
        transform: translateX(-50%);
        width: 40%;
        height: 3px;
        background: linear-gradient(90deg, #9c27b0, #ba68c8);
        border-radius: 0 0 3px 3px;
      }
    }

    .q-icon {
      margin-bottom: 4px;
      transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    &:active .q-icon {
      transform: scale(0.9);
    }

    .nav-label {
      font-size: 11px;
      line-height: 1.2;
      margin-top: 2px;
      transition: all 0.3s ease;
    }
  }
}

// Animation for nav items
@keyframes navItemPulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.15);
  }
}

.nav-item-active .q-icon {
  animation: navItemPulse 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

// Smooth entrance animation
@keyframes slideUp {
  from {
    transform: translateY(100%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.mobile-bottom-nav {
  animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>

