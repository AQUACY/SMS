<template>
  <!-- Select layout based on user role -->
  <ParentLayout v-if="authStore.isParent && authStore.isAuthenticated && authStore.token" />
  <TeacherLayout v-else-if="authStore.isTeacher && authStore.isAuthenticated && authStore.token" />
  <SuperAdminLayout v-else-if="authStore.isSuperAdmin && authStore.isAuthenticated && authStore.token" />
  <AdminLayout v-else-if="(authStore.isSchoolAdmin || authStore.isAccountsManager) && authStore.isAuthenticated && authStore.token" />
  <div v-else-if="authStore.token && !authStore.isAuthenticated" class="loading-container">
    <q-spinner color="primary" size="3em" />
    <div class="text-body1 q-mt-md">Loading...</div>
  </div>
  <div v-else class="loading-container">
    <q-icon name="lock" size="64px" color="grey-5" />
    <div class="text-h6 q-mt-md">Please login to continue</div>
    <q-btn
      color="primary"
      label="Go to Login"
      unelevated
      @click="router.push('/login')"
      class="q-mt-md"
    />
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from 'src/stores/auth';
import ParentLayout from './ParentLayout.vue';
import TeacherLayout from './TeacherLayout.vue';
import AdminLayout from './AdminLayout.vue';
import SuperAdminLayout from './SuperAdminLayout.vue';

const router = useRouter();
const authStore = useAuthStore();

// Check authentication on mount
onMounted(async () => {
  // If no token, redirect to login
  if (!authStore.token) {
    router.push('/login');
    return;
  }

  // If token exists but user data is not loaded, try to fetch it
  // Don't redirect immediately - let the router guard handle it
  if (!authStore.user || !authStore.isAuthenticated) {
    // Silently check auth - don't redirect here, let router guard handle it
    await authStore.checkAuth();
  }
});
</script>

<style lang="scss" scoped>
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100vh;
  width: 100vw;
}
</style>

<style lang="scss" scoped>
.sidebar-drawer {
  background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
  border-right: 1px solid rgba(0, 0, 0, 0.08);

  // Hide sidebar on mobile, show bottom nav instead
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

.upgrade-card {
  background: linear-gradient(135deg, rgba(156, 39, 176, 0.1) 0%, rgba(156, 39, 176, 0.05) 100%);
  border: 1px solid rgba(156, 39, 176, 0.2);
  border-radius: 16px;
  padding: 16px;
  backdrop-filter: blur(10px);
  box-shadow: 0 4px 12px rgba(156, 39, 176, 0.1);
}

.header-bar {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(20px);
  background: rgba(255, 255, 255, 0.9) !important;

  &.mobile-header {
    // Adjust header for mobile
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

// Page transition animations
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

// Mobile content padding for bottom nav
.main-content {
  @media (max-width: 767px) {
    padding-bottom: 80px !important;
  }
}
</style>
