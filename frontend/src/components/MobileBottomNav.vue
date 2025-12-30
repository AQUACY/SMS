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
  height: 72px;
  padding-bottom: env(safe-area-inset-bottom);
  backdrop-filter: blur(30px);
  -webkit-backdrop-filter: blur(30px);
  background: rgba(255, 255, 255, 0.9);
  border-top: 1px solid var(--border-light);
  box-shadow: 0 -2px 16px rgba(0, 0, 0, 0.08);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  
  @media (min-width: 768px) {
    display: none;
  }

  .nav-toolbar {
    height: 100%;
    padding: 0 var(--spacing-xs);
    display: flex;
    justify-content: space-around;
    align-items: center;
    background: transparent;
  }

  .nav-item {
    flex: 1;
    flex-direction: column;
    padding: var(--spacing-xs) var(--spacing-xs);
    min-width: 0;
    color: var(--text-secondary);
    transition: all var(--transition-base);
    position: relative;
    border-radius: var(--radius-md);
    margin: 0 var(--spacing-xs);
    max-width: 80px;
    min-height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;

    &:active {
      transform: scale(0.95);
      background: rgba(156, 39, 176, 0.1);
    }

    &.nav-item-active {
      color: var(--primary-color);
      background: rgba(156, 39, 176, 0.08);

      .nav-label {
        font-weight: 700;
        color: var(--primary-color);
      }

      .q-icon {
        color: var(--primary-color);
        transform: scale(1.1);
      }

      &::after {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 32px;
        height: 3px;
        background: var(--primary-gradient);
        border-radius: 0 0 var(--radius-sm) var(--radius-sm);
      }
    }

    .q-icon {
      margin-bottom: 2px;
      transition: all var(--transition-base);
      font-size: 24px;
    }

    .nav-label {
      font-size: 10px;
      line-height: 1.2;
      margin-top: 2px;
      transition: all var(--transition-base);
      font-weight: 500;
      letter-spacing: 0.2px;
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

