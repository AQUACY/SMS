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
          <div class="text-caption text-grey-7 q-mt-xs">Admin Portal</div>
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
      <q-toolbar class="q-px-md">
        <q-btn
          flat
          dense
          round
          icon="menu"
          aria-label="Menu"
          @click="toggleLeftDrawer"
          class="q-mr-md"
        />

        <!-- Search Bar -->
        <q-input
          v-model="searchQuery"
          placeholder="Search anything here"
          dense
          outlined
          class="search-input"
          :style="{ width: '400px' }"
        >
          <template v-slot:append>
            <q-icon name="search" />
          </template>
        </q-input>

        <q-space />

        <!-- Right Side Actions -->
        <div class="row items-center q-gutter-sm">
          <q-btn flat round dense icon="notifications" color="grey-8">
            <q-badge color="red" floating>3</q-badge>
          </q-btn>
          
          <q-btn flat round dense icon="chat" color="grey-8" />

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
                  <div class="text-weight-medium">{{ authStore.fullName || 'Admin' }}</div>
                  <div class="text-caption text-grey-7">School Admin</div>
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
import { useQuasar } from 'quasar';
import MobileBottomNav from 'src/components/MobileBottomNav.vue';

const $q = useQuasar();
const router = useRouter();
const authStore = useAuthStore();

const leftDrawerOpen = ref(false);
const searchQuery = ref('');

const menuItems = computed(() => {
  if (!authStore.isSchoolAdmin && !authStore.isAccountsManager) {
    return [];
  }
  
  // Accounts managers have limited access - only payments and fees
  if (authStore.isAccountsManager && !authStore.isSchoolAdmin) {
    return [
      { name: 'dashboard', label: 'Dashboard', icon: 'dashboard', path: '/app/dashboard' },
      { name: 'payments', label: 'Fee Payments', icon: 'payment', path: '/app/payments' },
      { name: 'fees', label: 'Fees', icon: 'attach_money', path: '/app/fees' },
      { name: 'notifications', label: 'Notifications', icon: 'notifications', path: '/app/notifications' },
    ];
  }
  
  // School admins have full access
  return [
    { name: 'dashboard', label: 'Dashboard', icon: 'dashboard', path: '/app/dashboard' },
    { name: 'students', label: 'Students', icon: 'people', path: '/app/students' },
    { name: 'teachers', label: 'Teachers', icon: 'person', path: '/app/teachers' },
    { name: 'classes', label: 'Classes', icon: 'class', path: '/app/classes' },
    { name: 'subjects', label: 'Subjects', icon: 'menu_book', path: '/app/subjects' },
    { name: 'attendance', label: 'Attendance', icon: 'checklist', path: '/app/attendance' },
    { name: 'exams', label: 'Exams', icon: 'quiz', path: '/app/exams' },
    { name: 'assessments', label: 'Assessments', icon: 'edit', path: '/app/assessments' },
    { name: 'results', label: 'Results', icon: 'assessment', path: '/app/results' },
    { name: 'report-cards', label: 'Report Cards', icon: 'description', path: '/app/report-cards' },
    { name: 'academic-years', label: 'Academic Years', icon: 'calendar_view_year', path: '/app/academic-years' },
    { name: 'terms', label: 'Terms', icon: 'calendar_today', path: '/app/terms' },
    { name: 'payments', label: 'Fee Payments', icon: 'payment', path: '/app/payments' },
    { name: 'fees', label: 'Fees', icon: 'attach_money', path: '/app/fees' },
    { name: 'users', label: 'Users', icon: 'people_outline', path: '/app/users' },
    { name: 'grading-scales', label: 'Grading Scales', icon: 'grade', path: '/app/settings/grading-scales' },
    { name: 'notifications', label: 'Notifications', icon: 'notifications', path: '/app/notifications' },
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
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(20px);
  background: rgba(255, 255, 255, 0.9) !important;

  &.mobile-header {
    .search-input {
      display: none;
    }
  }
}

.search-input {
  :deep(.q-field__control) {
    border-radius: 12px;
    background: rgba(0, 0, 0, 0.03);
    transition: all 0.3s ease;
    
    &:hover {
      background: rgba(0, 0, 0, 0.05);
    }
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

