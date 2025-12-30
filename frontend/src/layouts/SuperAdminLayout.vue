<template>
  <q-layout view="hHh lpR fFf" class="bg-grey-1">
    <!-- Sidebar -->
    <q-drawer
      v-model="leftDrawerOpen"
      show-if-above
      bordered
      class="sidebar-drawer"
      :width="280"
    >
      <div class="sidebar-content">
        <!-- Logo Section -->
        <div class="logo-section q-pa-md">
          <div class="row items-center">
            <q-icon name="school" size="32px" color="primary" class="q-mr-sm" />
            <div class="text-h5 text-weight-bold text-primary">SMS</div>
          </div>
          <div class="text-caption text-grey-7 q-mt-xs">Super Admin</div>
        </div>

        <!-- Navigation Menu -->
        <q-list class="nav-menu">
          <q-item
            v-for="item in menuItems"
            :key="item.name"
            clickable
            v-ripple
            :to="item.path"
            :active="item.path === $route.path"
            active-class="active-menu-item"
            class="menu-item"
          >
            <q-item-section avatar>
              <q-icon :name="item.icon" />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ item.label }}</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </div>
    </q-drawer>

    <!-- Header -->
    <q-header elevated class="header-bar bg-white text-dark">
      <!-- Mobile Header -->
      <div class="mobile-header-wrapper">
        <q-toolbar class="mobile-toolbar">
          <q-btn
            flat
            dense
            round
            icon="menu"
            aria-label="Menu"
            @click="toggleLeftDrawer"
            class="header-icon-btn"
          />

          <div class="header-greeting">
            <div class="greeting-text">
              Hello, {{ authStore.user?.first_name || 'Super Admin' }}
            </div>
            <div class="greeting-subtitle">
              Super Admin
            </div>
          </div>

          <q-space />

          <div class="header-actions">
            <!-- Notification Icon - Links to Notifications Page -->
            <q-btn
              flat
              round
              dense
              icon="notifications"
              color="grey-8"
              class="header-icon-btn notification-btn"
              :to="'/app/notifications'"
            >
              <q-badge
                v-if="unreadNotificationCount > 0"
                color="red"
                floating
                rounded
              >
                {{ unreadNotificationCount > 99 ? '99+' : unreadNotificationCount }}
              </q-badge>
            </q-btn>

            <!-- Profile Icon with Dropdown -->
            <q-btn-dropdown
              flat
              round
              dense
              no-icon-animation
              class="header-icon-btn profile-btn"
            >
              <template v-slot:label>
                <q-avatar size="36px" class="bg-primary">
                  <q-icon name="person" color="white" size="20px" />
                </q-avatar>
              </template>

              <q-list>
                <q-item clickable v-close-popup @click="goToProfile">
                  <q-item-section avatar>
                    <q-icon name="person" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>Profile</q-item-label>
                  </q-item-section>
                </q-item>

                <q-item clickable v-close-popup @click="handleLogout">
                  <q-item-section avatar>
                    <q-icon name="logout" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>Logout</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </div>
        </q-toolbar>

        <!-- Desktop Header (hidden on mobile) -->
        <q-toolbar class="desktop-toolbar">
          <q-btn
            flat
            dense
            round
            icon="menu"
            aria-label="Menu"
            @click="toggleLeftDrawer"
            class="q-mr-md"
          />

          <q-space />

          <!-- Right Side Actions -->
          <div class="row items-center q-gutter-sm">
            <!-- Notification Icon - Links to Notifications Page -->
            <q-btn
              flat
              round
              dense
              icon="notifications"
              color="grey-8"
              :to="'/app/notifications'"
            >
              <q-badge
                v-if="unreadNotificationCount > 0"
                color="red"
                floating
                rounded
              >
                {{ unreadNotificationCount > 99 ? '99+' : unreadNotificationCount }}
              </q-badge>
            </q-btn>

            <!-- User Profile -->
            <q-btn-dropdown
              flat
              no-icon-animation
              class="user-profile-btn"
            >
              <template v-slot:label>
                <div class="row items-center q-gutter-sm">
                  <q-avatar size="32px" class="bg-primary">
                    <q-icon name="person" color="white" />
                  </q-avatar>
                  <div class="text-left">
                    <div class="text-weight-medium">{{ authStore.fullName || 'Super Admin' }}</div>
                    <div class="text-caption text-grey-7">Super Admin</div>
                  </div>
                  <q-icon name="keyboard_arrow_down" size="16px" />
                </div>
              </template>

              <q-list>
                <q-item clickable v-close-popup @click="goToProfile">
                  <q-item-section avatar>
                    <q-icon name="person" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>Profile</q-item-label>
                  </q-item-section>
                </q-item>

                <q-item clickable v-close-popup @click="handleLogout">
                  <q-item-section avatar>
                    <q-icon name="logout" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>Logout</q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-btn-dropdown>
          </div>
        </q-toolbar>
      </div>
    </q-header>

    <!-- Main Content -->
    <q-page-container class="main-content">
      <router-view v-slot="{ Component }">
        <transition name="page-fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </q-page-container>

    <!-- Mobile Bottom Navigation -->
    <MobileBottomNav />
  </q-layout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from 'src/stores/auth';
import { useNotificationStore } from 'src/stores/notifications';
import { useQuasar } from 'quasar';
import MobileBottomNav from 'src/components/MobileBottomNav.vue';

const $q = useQuasar();
const router = useRouter();
const authStore = useAuthStore();
const notificationStore = useNotificationStore();

const leftDrawerOpen = ref(false);
const unreadNotificationCount = computed(() => notificationStore.unreadCount || 0);

const menuItems = computed(() => {
  if (!authStore.isSuperAdmin) {
    return [];
  }
  
  return [
    { name: 'dashboard', label: 'Dashboard', icon: 'dashboard', path: '/app/dashboard' },
    { name: 'schools', label: 'Schools', icon: 'school', path: '/app/super-admin/schools' },
    { name: 'subscription-prices', label: 'Subscription Prices', icon: 'attach_money', path: '/app/subscription-prices' },
    { name: 'subscriptions', label: 'Subscriptions', icon: 'card_membership', path: '/app/subscriptions' },
    { name: 'payments', label: 'Payments', icon: 'payment', path: '/app/payments' },
    { name: 'notifications', label: 'Notifications', icon: 'notifications', path: '/app/notifications' },
    { name: 'profile', label: 'Profile', icon: 'person', path: '/app/profile' },
  ];
});

function toggleLeftDrawer() {
  leftDrawerOpen.value = !leftDrawerOpen.value;
}

function goToProfile() {
  router.push('/app/profile');
}

async function handleLogout() {
  $q.dialog({
    title: 'Confirm',
    message: 'Are you sure you want to logout?',
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    await authStore.logout();
  });
}
</script>

<style lang="scss" scoped>
.sidebar-drawer {
  background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
  border-right: 1px solid rgba(0, 0, 0, 0.08);

  @media (max-width: 767px) {
    display: none;
  }
}

.sidebar-content {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.logo-section {
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(10px);
}

.nav-menu {
  flex: 1;
  padding: 8px;
}

.menu-item {
  border-radius: 12px;
  margin: 4px 0;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  
  &:hover {
    background: rgba(156, 39, 176, 0.08);
    transform: translateX(4px);
  }
}

.active-menu-item {
  background: linear-gradient(90deg, rgba(156, 39, 176, 0.15) 0%, rgba(156, 39, 176, 0.05) 100%);
  color: #9c27b0;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(156, 39, 176, 0.2);
  
  .q-icon {
    color: #9c27b0;
  }
}

.header-bar {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  border-bottom: 1px solid var(--border-light);
  backdrop-filter: blur(30px);
  -webkit-backdrop-filter: blur(30px);
  background: rgba(255, 255, 255, 0.95) !important;
}

.mobile-header-wrapper {
  width: 100%;
}

.mobile-toolbar {
  display: flex;
  align-items: center;
  padding: var(--spacing-sm) var(--spacing-md);
  min-height: 64px;
  
  @media (min-width: 768px) {
    display: none;
  }
}

.desktop-toolbar {
  display: none;
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    display: flex;
  }
}

.header-greeting {
  flex: 1;
  margin-left: var(--spacing-sm);
  
  .greeting-text {
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.2;
  }
  
  .greeting-subtitle {
    font-size: var(--font-size-xs);
    color: var(--text-secondary);
    margin-top: 2px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
}

.header-actions {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
}

.header-icon-btn {
  min-width: 40px;
  min-height: 40px;
  width: 40px;
  height: 40px;
}

.notification-btn {
  position: relative;
  
  :deep(.q-badge) {
    font-size: 10px;
    min-width: 18px;
    height: 18px;
    padding: 0 4px;
  }
}

.profile-btn {
  :deep(.q-btn__content) {
    padding: 0;
  }
  
  .q-avatar {
    border: 2px solid rgba(255, 255, 255, 0.3);
  }
}

.user-profile-btn {
  :deep(.q-btn__content) {
    padding: 4px 8px;
    border-radius: 12px;
    transition: all 0.3s ease;
    
    &:hover {
      background: rgba(0, 0, 0, 0.05);
    }
  }
}

.page-fade-enter-active,
.page-fade-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-fade-enter-from {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}

.page-fade-leave-to {
  opacity: 0;
  transform: translateY(-20px) scale(0.95);
}

.main-content {
  @media (max-width: 767px) {
    padding-bottom: 80px !important;
  }
}
</style>

