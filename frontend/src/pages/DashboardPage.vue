<template>
  <q-page class="dashboard-page">
    <!-- Mobile-First Layout -->
    <div class="dashboard-container">
      <!-- Welcome Section (Mobile) -->
      <div class="welcome-section-mobile">
        <div class="welcome-content">
          <div class="welcome-title">
            Welcome, {{ authStore.user?.first_name || 'User' }}!
          </div>
          <div class="welcome-subtitle">
            {{ welcomeMessage }}
          </div>
        </div>
      </div>

      <!-- Noticeboard Card (First Card) - Always visible for all users -->
      <div class="noticeboard-wrapper">
        <NoticeboardCard />
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-skeleton">
        <div class="skeleton-card" v-for="i in 3" :key="i">
          <q-skeleton type="rect" height="120px" />
        </div>
      </div>

      <!-- Statistics Cards -->
      <div v-else class="stats-grid">
        <div
          v-for="stat in statistics"
          :key="stat.label"
          class="stat-card-wrapper"
          @click="stat.action ? stat.action() : null"
        >
          <q-card
            class="stat-card-modern"
            :class="{ 'clickable': stat.action }"
          >
            <q-card-section class="stat-card-content">
              <div class="stat-icon-wrapper" :style="{ backgroundColor: stat.iconBg || 'rgba(156, 39, 176, 0.1)' }">
                <q-icon :name="stat.icon" :size="stat.iconSize || '32px'" :color="stat.iconColor || 'primary'" />
              </div>
              <div class="stat-info">
                <div class="stat-label">{{ stat.label }}</div>
                <div class="stat-value">{{ formatValue(stat.value) }}</div>
                <div v-if="stat.subLabel" class="stat-sublabel">{{ stat.subLabel }}</div>
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Role-Specific Content -->
      <div v-if="!loading" class="role-content">
        <!-- Super Admin Dashboard -->
        <SuperAdminDashboard v-if="authStore.isSuperAdmin" :stats="dashboardStats" />

        <!-- School Admin Dashboard -->
        <SchoolAdminDashboard v-if="authStore.isSchoolAdmin" :stats="dashboardStats" />

        <!-- Accounts Manager Dashboard -->
        <AccountsManagerDashboard v-if="authStore.isAccountsManager" :stats="dashboardStats" />

        <!-- Teacher Dashboard -->
        <TeacherDashboard v-if="authStore.isTeacher" :stats="dashboardStats" />

        <!-- Parent Dashboard -->
        <ParentDashboard v-if="authStore.isParent" :stats="dashboardStats" />
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from 'src/stores/auth';
import { useQuasar } from 'quasar';
import api from 'src/services/api';

// Import dashboard components directly
import SuperAdminDashboard from 'src/components/dashboard/SuperAdminDashboard.vue';
import SchoolAdminDashboard from 'src/components/dashboard/SchoolAdminDashboard.vue';
import AccountsManagerDashboard from 'src/components/dashboard/AccountsManagerDashboard.vue';
import TeacherDashboard from 'src/components/dashboard/TeacherDashboard.vue';
import ParentDashboard from 'src/components/dashboard/ParentDashboard.vue';
import NoticeboardCard from 'src/components/dashboard/NoticeboardCard.vue';

const router = useRouter();
const authStore = useAuthStore();
const $q = useQuasar();

const loading = ref(true);
const dashboardStats = ref({});

const welcomeMessage = computed(() => {
  if (authStore.isSuperAdmin) return 'Manage the entire platform and monitor all schools.';
  if (authStore.isSchoolAdmin) return 'Manage your school operations and monitor performance.';
  if (authStore.isAccountsManager) return 'Manage fee payments and track revenue.';
  if (authStore.isTeacher) return 'Manage your classes and track student progress.';
  if (authStore.isParent) return 'Stay connected with your children\'s education.';
  return 'Navigate the future of education with SMS.';
});

// Helper function to get icon colors
function getIconColors(colorName) {
  const colorMap = {
    purple: { bg: 'rgba(156, 39, 176, 0.1)', color: '#9c27b0' },
    blue: { bg: 'rgba(33, 150, 243, 0.1)', color: '#2196f3' },
    orange: { bg: 'rgba(255, 152, 0, 0.1)', color: '#ff9800' },
    green: { bg: 'rgba(76, 175, 80, 0.1)', color: '#4caf50' },
    teal: { bg: 'rgba(0, 150, 136, 0.1)', color: '#009688' },
    amber: { bg: 'rgba(255, 193, 7, 0.1)', color: '#ffc107' },
    red: { bg: 'rgba(244, 67, 54, 0.1)', color: '#f44336' },
    pink: { bg: 'rgba(233, 30, 99, 0.1)', color: '#e91e63' },
  };
  return colorMap[colorName] || colorMap.purple;
}

const statistics = computed(() => {
  const stats = dashboardStats.value;
  
  if (authStore.isSuperAdmin) {
    return [
      {
        label: 'Total Schools',
        value: stats.total_schools || 0,
        icon: 'school',
        iconSize: '32px',
        ...getIconColors('purple'),
        action: () => router.push('/app/super-admin/schools'),
      },
      {
        label: 'Active Schools',
        value: stats.active_schools || 0,
        icon: 'check_circle',
        iconSize: '32px',
        ...getIconColors('blue'),
      },
      {
        label: 'Total Users',
        value: stats.total_users || 0,
        icon: 'people',
        iconSize: '32px',
        ...getIconColors('orange'),
      },
      {
        label: 'Total Students',
        value: stats.total_students || 0,
        icon: 'person',
        iconSize: '32px',
        ...getIconColors('green'),
      },
      {
        label: 'Total Teachers',
        value: stats.total_teachers || 0,
        icon: 'person_outline',
        iconSize: '32px',
        ...getIconColors('teal'),
      },
      {
        label: 'Subscription Revenue',
        value: stats.total_subscription_revenue || 0,
        icon: 'wallet',
        iconSize: '32px',
        ...getIconColors('amber'),
        subLabel: `${stats.pending_subscription_payments || 0} pending`,
      },
    ];
  }
  
  if (authStore.isSchoolAdmin) {
    return [
      {
        label: 'Total Students',
        value: stats.total_students || 0,
        icon: 'people',
        iconSize: '32px',
        ...getIconColors('purple'),
        action: () => router.push('/app/students'),
      },
      {
        label: 'Active Students',
        value: stats.active_students || 0,
        icon: 'person',
        iconSize: '32px',
        ...getIconColors('blue'),
      },
      {
        label: 'Total Teachers',
        value: stats.total_teachers || 0,
        icon: 'person_outline',
        iconSize: '32px',
        ...getIconColors('orange'),
        action: () => router.push('/app/teachers'),
      },
      {
        label: 'Total Classes',
        value: stats.total_classes || 0,
        icon: 'class',
        iconSize: '32px',
        ...getIconColors('green'),
        action: () => router.push('/app/classes'),
      },
      {
        label: 'Fee Revenue',
        value: stats.total_fee_revenue || 0,
        icon: 'wallet',
        iconSize: '32px',
        ...getIconColors('amber'),
        subLabel: `${stats.pending_fee_payments || 0} pending`,
        action: () => router.push('/app/payments'),
      },
      {
        label: 'Pending Assessments',
        value: stats.pending_assessments || 0,
        icon: 'edit',
        iconSize: '32px',
        ...getIconColors('red'),
        action: () => router.push('/app/assessments'),
      },
    ];
  }
  
  if (authStore.isAccountsManager) {
    return [
      {
        label: 'Total Payments',
        value: stats.total_fee_payments || 0,
        icon: 'payment',
        iconSize: '32px',
        ...getIconColors('purple'),
        action: () => router.push('/app/payments'),
      },
      {
        label: 'Completed',
        value: stats.completed_payments || 0,
        icon: 'check_circle',
        iconSize: '32px',
        ...getIconColors('green'),
      },
      {
        label: 'Pending',
        value: stats.pending_payments || 0,
        icon: 'pending',
        iconSize: '32px',
        ...getIconColors('orange'),
      },
      {
        label: 'Failed',
        value: stats.failed_payments || 0,
        icon: 'error',
        iconSize: '32px',
        ...getIconColors('red'),
      },
      {
        label: 'Total Revenue',
        value: stats.total_revenue || 0,
        icon: 'wallet',
        iconSize: '32px',
        ...getIconColors('blue'),
      },
      {
        label: 'This Month',
        value: stats.monthly_revenue || 0,
        icon: 'calendar_month',
        iconSize: '32px',
        ...getIconColors('teal'),
      },
    ];
  }
  
  if (authStore.isTeacher) {
    return [
      {
        label: 'Assigned Classes',
        value: stats.assigned_classes || 0,
        icon: 'class',
        iconSize: '32px',
        ...getIconColors('purple'),
        action: () => router.push('/app/classes'),
      },
      {
        label: 'Total Students',
        value: stats.total_students || 0,
        icon: 'people',
        iconSize: '32px',
        ...getIconColors('blue'),
      },
      {
        label: 'Total Assessments',
        value: stats.total_assessments || 0,
        icon: 'edit',
        iconSize: '32px',
        ...getIconColors('orange'),
        action: () => router.push('/app/assessments'),
      },
      {
        label: 'Pending Assessments',
        value: stats.pending_assessments || 0,
        icon: 'pending',
        iconSize: '32px',
        ...getIconColors('red'),
      },
      {
        label: 'Finalized',
        value: stats.finalized_assessments || 0,
        icon: 'check_circle',
        iconSize: '32px',
        ...getIconColors('green'),
      },
      {
        label: 'Today\'s Attendance',
        value: stats.today_attendance?.present || 0,
        icon: 'checklist',
        iconSize: '32px',
        ...getIconColors('teal'),
        subLabel: `${stats.today_attendance?.absent || 0} absent`,
        action: () => router.push('/app/attendance'),
      },
    ];
  }
  
  if (authStore.isParent) {
    return [
      {
        label: 'My Children',
        value: stats.total_children || 0,
        icon: 'people',
        iconSize: '32px',
        ...getIconColors('purple'),
        action: () => router.push('/app/parent/children'),
      },
      {
        label: 'Active Subscriptions',
        value: stats.active_subscriptions || 0,
        icon: 'subscriptions',
        iconSize: '32px',
        ...getIconColors('blue'),
      },
      {
        label: 'Total Spent',
        value: stats.total_spent || 0,
        icon: 'wallet',
        iconSize: '32px',
        ...getIconColors('orange'),
      },
    ];
  }
  
  return [];
});

function formatValue(value) {
  if (typeof value === 'number') {
    if (value >= 1000000) {
      return (value / 1000000).toFixed(1) + 'M';
    }
    if (value >= 1000) {
      return (value / 1000).toFixed(1) + 'K';
    }
    return value.toLocaleString();
  }
  return value;
}

async function fetchDashboardStats() {
  loading.value = true;
  try {
    const params = {};
    
    // Add school_id for non-super-admin users
    if (!authStore.isSuperAdmin && !authStore.isParent && authStore.user?.school_id) {
      params.school_id = authStore.user.school_id;
    }
    
    const response = await api.get('/dashboard/statistics', { params });
    dashboardStats.value = response.data.data || {};
  } catch (error) {
    console.error('Failed to fetch dashboard stats:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to load dashboard statistics',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  fetchDashboardStats();
});
</script>

<style lang="scss" scoped>
.dashboard-page {
  background: var(--bg-primary);
  min-height: 100vh;
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.dashboard-container {
  max-width: 1200px;
  margin: 0 auto;
}

.welcome-section-mobile {
  margin-bottom: var(--spacing-lg);
  
  @media (min-width: 768px) {
    display: none;
  }
}

.welcome-content {
  .welcome-title {
    font-size: var(--font-size-2xl);
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: var(--spacing-xs);
    line-height: 1.2;
  }
  
  .welcome-subtitle {
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
    line-height: 1.4;
  }
}

.loading-skeleton {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-md);
  
  @media (min-width: 600px) {
    grid-template-columns: repeat(2, 1fr);
  }
  
  @media (min-width: 960px) {
    grid-template-columns: repeat(3, 1fr);
  }
  
  .skeleton-card {
    border-radius: var(--radius-lg);
    overflow: hidden;
  }
}

.stats-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-lg);
  
  @media (min-width: 600px) {
    grid-template-columns: repeat(2, 1fr);
  }
  
  @media (min-width: 960px) {
    grid-template-columns: repeat(3, 1fr);
  }
}

.stat-card-wrapper {
  width: 100%;
}

.stat-card-modern {
  border-radius: var(--radius-lg);
  border: none;
  box-shadow: var(--shadow-sm);
  transition: all var(--transition-base);
  background: var(--bg-card);
  overflow: hidden;
  
  &.clickable {
    cursor: pointer;
    
    &:active {
      transform: scale(0.98);
    }
    
    @media (min-width: 768px) {
      &:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
      }
    }
  }
  
  @media (min-width: 768px) {
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md);
  }
}

.stat-card-content {
  padding: var(--spacing-md);
  display: flex;
  align-items: flex-start;
  gap: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.stat-icon-wrapper {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  
  @media (min-width: 768px) {
    width: 64px;
    height: 64px;
    border-radius: var(--radius-lg);
  }
}

.stat-info {
  flex: 1;
  min-width: 0;
}

.stat-label {
  font-size: var(--font-size-xs);
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 600;
  margin-bottom: var(--spacing-xs);
}

.stat-value {
  font-size: var(--font-size-2xl);
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.2;
  margin-bottom: var(--spacing-xs);
  
  @media (min-width: 768px) {
    font-size: var(--font-size-3xl);
  }
}

.stat-sublabel {
  font-size: var(--font-size-xs);
  color: var(--text-tertiary);
  margin-top: var(--spacing-xs);
}

.noticeboard-wrapper {
  margin-bottom: var(--spacing-lg);
  width: 100%;
}

.role-content {
  margin-top: var(--spacing-lg);
}
</style>

