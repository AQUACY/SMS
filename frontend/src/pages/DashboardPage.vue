<template>
  <q-page class="dashboard-page q-pa-lg">
    <!-- Welcome Section -->
    <div class="row q-mb-lg">
      <div class="col-12">
        <div class="welcome-section">
          <div class="text-h3 text-weight-bold q-mb-xs">Welcome.</div>
          <div class="text-h6 text-grey-7">
            Navigate the future of education with SMS.
          </div>
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row q-col-gutter-md q-mb-lg">
      <div class="col-12 col-md-4" v-for="stat in statistics" :key="stat.label">
        <q-card class="stat-card" :class="[stat.colorClass, { 'cursor-pointer': stat.action }]" @click="stat.action ? stat.action() : null">
          <q-card-section class="q-pa-lg">
            <div class="row items-center justify-between">
              <div>
                <div class="text-grey-7 text-caption q-mb-xs">{{ stat.label }}</div>
                <div class="text-h4 text-weight-bold">{{ stat.value }}</div>
              </div>
              <q-icon :name="stat.icon" :size="stat.iconSize" :class="stat.iconClass" />
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Super Admin Quick Actions -->
    <div v-if="authStore.isSuperAdmin" class="row q-col-gutter-md q-mb-lg">
      <div class="col-12">
        <q-card class="widget-card">
          <q-card-section>
            <div class="text-h6 text-weight-bold q-mb-md">Quick Actions</div>
            <div class="row q-gutter-sm">
              <q-btn
                color="primary"
                label="Manage Schools"
                icon="school"
                unelevated
                to="/app/super-admin/schools"
              />
              <q-btn
                color="secondary"
                label="View All Schools"
                icon="list"
                unelevated
                to="/app/super-admin/schools"
              />
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="row q-col-gutter-md">
      <!-- Left Column -->
      <div class="col-12 col-lg-8">
        <!-- Class Routine Widget -->
        <q-card class="widget-card q-mb-md">
          <q-card-section>
            <div class="row items-center justify-between q-mb-md">
              <div class="text-h6 text-weight-bold">Class Routine</div>
              <q-btn flat dense label="View All" color="primary" size="sm" />
            </div>
            
            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6" v-for="routine in routines" :key="routine.month">
                <q-card :class="routine.colorClass" class="routine-card">
                  <q-card-section class="q-pa-md">
                    <div class="row items-center q-mb-sm">
                      <q-icon :name="routine.icon" :size="24" class="q-mr-sm" />
                      <div class="text-weight-bold">{{ routine.month }}</div>
                    </div>
                    <div class="text-body2 text-grey-7 q-mb-md">
                      {{ routine.description }}
                    </div>
                    <q-btn
                      :color="routine.buttonColor"
                      :label="routine.buttonLabel"
                      unelevated
                      size="sm"
                      class="full-width"
                    />
                  </q-card-section>
                </q-card>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Star Students Widget -->
        <q-card class="widget-card">
          <q-card-section>
            <div class="text-h6 text-weight-bold q-mb-md">Star Students</div>
            <q-table
              :rows="starStudents"
              :columns="studentColumns"
              row-key="id"
              flat
              :rows-per-page-options="[0]"
              hide-pagination
            >
              <template v-slot:body-cell-name="props">
                <q-td :props="props">
                  <div class="row items-center">
                    <q-avatar size="32px" class="q-mr-sm bg-primary">
                      <q-icon name="person" color="white" />
                    </q-avatar>
                    <div>{{ props.value }}</div>
                  </div>
                </q-td>
              </template>
              <template v-slot:body-cell-percent="props">
                <q-td :props="props">
                  <q-badge :color="getPercentColor(props.value)" :label="props.value + '%'" />
                </q-td>
              </template>
            </q-table>
          </q-card-section>
        </q-card>
      </div>

      <!-- Right Column -->
      <div class="col-12 col-lg-4">
        <!-- Library Widget -->
        <q-card class="widget-card q-mb-md">
          <q-card-section>
            <div class="row items-center justify-between q-mb-md">
              <div class="text-h6 text-weight-bold">Library</div>
              <q-btn flat dense label="View All" color="primary" size="sm" />
            </div>
            
            <q-list>
              <q-item v-for="subject in librarySubjects" :key="subject.name" class="q-pa-sm">
                <q-item-section avatar>
                  <q-icon :name="subject.icon" :color="subject.color" size="24px" />
                </q-item-section>
                <q-item-section>
                  <q-item-label class="text-weight-medium">{{ subject.name }}</q-item-label>
                  <q-item-label caption>{{ subject.files }} files</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-btn flat dense label="Read now" color="primary" size="sm" />
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>

        <!-- Course Statistics Widget -->
        <q-card class="widget-card q-mb-md">
          <q-card-section>
            <div class="text-h6 text-weight-bold q-mb-md">Course Statistics</div>
            <div class="text-center">
              <div class="text-h5 text-weight-bold q-mb-md">Total 15,000</div>
              <!-- Placeholder for chart - you can integrate a chart library later -->
              <div class="chart-placeholder">
                <q-icon name="pie_chart" size="120px" color="grey-4" />
              </div>
              <div class="row q-mt-md q-gutter-sm justify-center">
                <div v-for="course in courses" :key="course.name" class="text-center">
                  <div class="row items-center q-mb-xs">
                    <div :class="'course-dot bg-' + course.color"></div>
                    <span class="text-caption q-ml-xs">{{ course.name }}</span>
                  </div>
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Total Exams Widget -->
        <q-card class="widget-card">
          <q-card-section>
            <div class="row items-center justify-between q-mb-md">
              <div class="text-h6 text-weight-bold">Total Exams</div>
              <q-badge color="pink" label="↑ 80%" />
            </div>
            <div class="text-h3 text-weight-bold text-primary q-mb-sm">256</div>
            <div class="text-body2 text-grey-7">
              Here is your total exams ratio in this month.
              <q-btn flat dense label="view details" color="primary" size="sm" class="q-ml-xs" />
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from 'src/stores/auth';
import api from 'src/services/api';

const router = useRouter();
const authStore = useAuthStore();

const statistics = computed(() => {
  if (authStore.isSuperAdmin) {
    return [
      {
        label: 'Total Schools',
        value: dashboardStats.value.total_schools || '0',
        icon: 'school',
        colorClass: 'stat-card-purple',
        iconSize: '48px',
        iconClass: 'text-purple',
        action: () => router.push('/app/super-admin/schools'),
      },
      {
        label: 'Active Schools',
        value: dashboardStats.value.active_schools || '0',
        icon: 'check_circle',
        colorClass: 'stat-card-blue',
        iconSize: '48px',
        iconClass: 'text-blue',
      },
      {
        label: 'Total Users',
        value: dashboardStats.value.total_users || '0',
        icon: 'people',
        colorClass: 'stat-card-orange',
        iconSize: '48px',
        iconClass: 'text-orange',
      },
    ];
  }
  
  // Default statistics for other roles
  return [
    {
      label: 'Students',
      value: '15.00K',
      icon: 'school',
      colorClass: 'stat-card-purple',
      iconSize: '48px',
      iconClass: 'text-purple',
    },
    {
      label: 'Teachers',
      value: '200',
      icon: 'person',
      colorClass: 'stat-card-blue',
      iconSize: '48px',
      iconClass: 'text-blue',
    },
    {
      label: 'Awards',
      value: '5.6K',
      icon: 'emoji_events',
      colorClass: 'stat-card-orange',
      iconSize: '48px',
      iconClass: 'text-orange',
    },
  ];
});

const dashboardStats = ref({});

onMounted(() => {
  if (authStore.isSuperAdmin) {
    fetchSuperAdminStats();
  }
});

async function fetchSuperAdminStats() {
  try {
    // TODO: Create super admin dashboard stats endpoint
    // const response = await api.get('/super-admin/dashboard/stats');
    // dashboardStats.value = response.data.data;
    dashboardStats.value = {
      total_schools: 0,
      active_schools: 0,
      total_users: 0,
    };
  } catch (error) {
    console.error('Failed to fetch stats:', error);
  }
}

const routines = ref([
  {
    month: 'October, 2023',
    description: 'Your October class routine is here.',
    icon: 'calendar_today',
    colorClass: 'routine-card-blue',
    buttonColor: 'primary',
    buttonLabel: 'Download routine (pdf)',
  },
  {
    month: 'November, 2023',
    description: 'Your November class routine is here.',
    icon: 'calendar_today',
    colorClass: 'routine-card-orange',
    buttonColor: 'grey-8',
    buttonLabel: 'Download routine (pdf)',
  },
]);

const librarySubjects = ref([
  { name: 'Literature', icon: 'menu_book', color: 'purple', files: 302 },
  { name: 'Mathematics', icon: 'calculate', color: 'blue', files: 1872 },
  { name: 'English', icon: 'menu_book', color: 'green', files: 575 },
  { name: 'Science', icon: 'science', color: 'orange', files: 249 },
]);

const courses = ref([
  { name: 'Math', color: 'orange' },
  { name: 'English', color: 'purple' },
  { name: 'Chemistry', color: 'green' },
  { name: 'Physics', color: 'blue' },
]);

const studentColumns = [
  { name: 'name', label: 'Name', field: 'name', align: 'left' },
  { name: 'id', label: 'ID', field: 'id', align: 'left' },
  { name: 'marks', label: 'Marks', field: 'marks', align: 'left' },
  { name: 'percent', label: 'Percent', field: 'percent', align: 'left' },
];

const starStudents = ref([
  { id: 1, name: 'Evelyn Harper', id_number: 'PRE43178', marks: 1185, percent: 98 },
  { id: 2, name: 'Diana Plenty', id_number: 'PRE43174', marks: 1165, percent: 91 },
  { id: 3, name: 'John Millar', id_number: 'PRE43187', marks: 1175, percent: 92 },
]);

function getPercentColor(percent) {
  if (percent >= 95) return 'green';
  if (percent >= 85) return 'blue';
  if (percent >= 75) return 'orange';
  return 'red';
}
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

.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
  transition: all 0.3s ease;
  
  &:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  }
}

.routine-card {
  border-radius: 12px;
  transition: all 0.3s ease;
  
  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }
}

.routine-card-blue {
  background: linear-gradient(135deg, rgba(33, 150, 243, 0.15) 0%, rgba(33, 150, 243, 0.05) 100%);
  border: 1px solid rgba(33, 150, 243, 0.2);
}

.routine-card-orange {
  background: linear-gradient(135deg, rgba(255, 152, 0, 0.15) 0%, rgba(255, 152, 0, 0.05) 100%);
  border: 1px solid rgba(255, 152, 0, 0.2);
}

.chart-placeholder {
  height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.02);
  border-radius: 12px;
}

.course-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  display: inline-block;
}

:deep(.q-table) {
  thead th {
    background: rgba(0, 0, 0, 0.02);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
  }
  
  tbody tr {
    transition: all 0.2s ease;
    
    &:hover {
      background: rgba(156, 39, 176, 0.05);
    }
  }
}
</style>
