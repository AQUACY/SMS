<template>
  <q-page class="dashboard-page q-pa-lg">
    <!-- Welcome Section -->
    <div class="row q-mb-lg">
      <div class="col-12">
        <div class="welcome-section">
          <div class="text-h3 text-weight-bold q-mb-xs">
            Welcome, {{ authStore.user?.first_name || 'User' }}!
          </div>
          <div class="text-h6 text-grey-7">
            {{ welcomeMessage }}
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="row q-col-gutter-md">
      <div class="col-12 col-md-4" v-for="i in 3" :key="i">
        <q-card>
          <q-card-section>
            <q-skeleton type="rect" height="100px" />
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div v-else class="row q-col-gutter-md q-mb-lg">
      <div class="col-12 col-sm-6 col-md-4" v-for="stat in statistics" :key="stat.label">
        <q-card 
          class="stat-card" 
          :class="[stat.colorClass, { 'cursor-pointer': stat.action }]" 
          @click="stat.action ? stat.action() : null"
        >
          <q-card-section class="q-pa-lg">
            <div class="row items-center justify-between">
              <div class="col">
                <div class="text-grey-7 text-caption q-mb-xs">{{ stat.label }}</div>
                <div class="text-h4 text-weight-bold">{{ formatValue(stat.value) }}</div>
                <div v-if="stat.subLabel" class="text-caption text-grey-6 q-mt-xs">{{ stat.subLabel }}</div>
              </div>
              <q-icon :name="stat.icon" :size="stat.iconSize || '48px'" :class="stat.iconClass" />
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Role-Specific Content -->
    <div v-if="!loading">
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

const statistics = computed(() => {
  const stats = dashboardStats.value;
  
  if (authStore.isSuperAdmin) {
    return [
      {
        label: 'Total Schools',
        value: stats.total_schools || 0,
        icon: 'school',
        colorClass: 'stat-card-purple',
        iconClass: 'text-purple',
        action: () => router.push('/app/super-admin/schools'),
      },
      {
        label: 'Active Schools',
        value: stats.active_schools || 0,
        icon: 'check_circle',
        colorClass: 'stat-card-blue',
        iconClass: 'text-blue',
      },
      {
        label: 'Total Users',
        value: stats.total_users || 0,
        icon: 'people',
        colorClass: 'stat-card-orange',
        iconClass: 'text-orange',
      },
      {
        label: 'Total Students',
        value: stats.total_students || 0,
        icon: 'person',
        colorClass: 'stat-card-green',
        iconClass: 'text-green',
      },
      {
        label: 'Total Teachers',
        value: stats.total_teachers || 0,
        icon: 'person_outline',
        colorClass: 'stat-card-teal',
        iconClass: 'text-teal',
      },
      {
        label: 'Subscription Revenue',
        value: stats.total_subscription_revenue || 0,
        icon: 'wallet',
        colorClass: 'stat-card-amber',
        iconClass: 'text-amber',
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
        colorClass: 'stat-card-purple',
        iconClass: 'text-purple',
        action: () => router.push('/app/students'),
      },
      {
        label: 'Active Students',
        value: stats.active_students || 0,
        icon: 'person',
        colorClass: 'stat-card-blue',
        iconClass: 'text-blue',
      },
      {
        label: 'Total Teachers',
        value: stats.total_teachers || 0,
        icon: 'person_outline',
        colorClass: 'stat-card-orange',
        iconClass: 'text-orange',
        action: () => router.push('/app/teachers'),
      },
      {
        label: 'Total Classes',
        value: stats.total_classes || 0,
        icon: 'class',
        colorClass: 'stat-card-green',
        iconClass: 'text-green',
        action: () => router.push('/app/classes'),
      },
      {
        label: 'Fee Revenue',
        value: stats.total_fee_revenue || 0,
        icon: 'wallet',
        colorClass: 'stat-card-amber',
        iconClass: 'text-amber',
        subLabel: `${stats.pending_fee_payments || 0} pending`,
        action: () => router.push('/app/payments'),
      },
      {
        label: 'Pending Assessments',
        value: stats.pending_assessments || 0,
        icon: 'edit',
        colorClass: 'stat-card-red',
        iconClass: 'text-red',
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
        colorClass: 'stat-card-purple',
        iconClass: 'text-purple',
        action: () => router.push('/app/payments'),
      },
      {
        label: 'Completed',
        value: stats.completed_payments || 0,
        icon: 'check_circle',
        colorClass: 'stat-card-green',
        iconClass: 'text-green',
      },
      {
        label: 'Pending',
        value: stats.pending_payments || 0,
        icon: 'pending',
        colorClass: 'stat-card-orange',
        iconClass: 'text-orange',
      },
      {
        label: 'Failed',
        value: stats.failed_payments || 0,
        icon: 'error',
        colorClass: 'stat-card-red',
        iconClass: 'text-red',
      },
      {
        label: 'Total Revenue',
        value: stats.total_revenue || 0,
        icon: 'wallet',
        colorClass: 'stat-card-blue',
        iconClass: 'text-blue',
      },
      {
        label: 'This Month',
        value: stats.monthly_revenue || 0,
        icon: 'calendar_month',
        colorClass: 'stat-card-teal',
        iconClass: 'text-teal',
      },
    ];
  }
  
  if (authStore.isTeacher) {
    return [
      {
        label: 'Assigned Classes',
        value: stats.assigned_classes || 0,
        icon: 'class',
        colorClass: 'stat-card-purple',
        iconClass: 'text-purple',
        action: () => router.push('/app/classes'),
      },
      {
        label: 'Total Students',
        value: stats.total_students || 0,
        icon: 'people',
        colorClass: 'stat-card-blue',
        iconClass: 'text-blue',
      },
      {
        label: 'Total Assessments',
        value: stats.total_assessments || 0,
        icon: 'edit',
        colorClass: 'stat-card-orange',
        iconClass: 'text-orange',
        action: () => router.push('/app/assessments'),
      },
      {
        label: 'Pending Assessments',
        value: stats.pending_assessments || 0,
        icon: 'pending',
        colorClass: 'stat-card-red',
        iconClass: 'text-red',
      },
      {
        label: 'Finalized',
        value: stats.finalized_assessments || 0,
        icon: 'check_circle',
        colorClass: 'stat-card-green',
        iconClass: 'text-green',
      },
      {
        label: 'Today\'s Attendance',
        value: stats.today_attendance?.present || 0,
        icon: 'checklist',
        colorClass: 'stat-card-teal',
        iconClass: 'text-teal',
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
        colorClass: 'stat-card-purple',
        iconClass: 'text-purple',
        action: () => router.push('/app/parent/children'),
      },
      {
        label: 'Active Subscriptions',
        value: stats.active_subscriptions || 0,
        icon: 'subscriptions',
        colorClass: 'stat-card-blue',
        iconClass: 'text-blue',
      },
      {
        label: 'Total Spent',
        value: stats.total_spent || 0,
        icon: 'wallet',
        colorClass: 'stat-card-orange',
        iconClass: 'text-orange',
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
  background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
  min-height: 100vh;
}

.welcome-section {
  padding: 24px;
  background: linear-gradient(135deg, rgba(156, 39, 176, 0.05) 0%, rgba(156, 39, 176, 0.02) 100%);
  border-radius: 16px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(156, 39, 176, 0.1);
}

.stat-card {
  border-radius: 16px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  
  &:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  }
}

.stat-card-purple {
  background: linear-gradient(135deg, rgba(156, 39, 176, 0.1) 0%, rgba(156, 39, 176, 0.05) 100%);
}

.stat-card-blue {
  background: linear-gradient(135deg, rgba(33, 150, 243, 0.1) 0%, rgba(33, 150, 243, 0.05) 100%);
}

.stat-card-orange {
  background: linear-gradient(135deg, rgba(255, 152, 0, 0.1) 0%, rgba(255, 152, 0, 0.05) 100%);
}

.stat-card-green {
  background: linear-gradient(135deg, rgba(76, 175, 80, 0.1) 0%, rgba(76, 175, 80, 0.05) 100%);
}

.stat-card-teal {
  background: linear-gradient(135deg, rgba(0, 150, 136, 0.1) 0%, rgba(0, 150, 136, 0.05) 100%);
}

.stat-card-amber {
  background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 193, 7, 0.05) 100%);
}

.stat-card-red {
  background: linear-gradient(135deg, rgba(244, 67, 54, 0.1) 0%, rgba(244, 67, 54, 0.05) 100%);
}
</style>
