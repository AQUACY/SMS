<template>
  <q-page class="attendance-reports-page">
    <MobilePageHeader
      title="Attendance Reports"
      subtitle="View attendance statistics and reports"
    />

    <div class="page-content">
      <!-- Filters -->
      <MobileCard variant="default" padding="md" class="q-mb-md">
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-3">
            <q-select
              v-model="filterClass"
              :options="classOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              label="Filter by Class"
              clearable
              @update:model-value="fetchReports"
            />
          </div>
          <div class="col-12 col-md-3">
            <q-select
              v-model="filterTerm"
              :options="termOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              label="Filter by Term"
              clearable
              @update:model-value="fetchReports"
            />
          </div>
          <div class="col-12 col-md-3">
            <q-input
              v-model="startDate"
              label="Start Date"
              type="date"
              clearable
              @update:model-value="fetchReports"
            />
          </div>
          <div class="col-12 col-md-3">
            <q-input
              v-model="endDate"
              label="End Date"
              type="date"
              clearable
              @update:model-value="fetchReports"
            />
          </div>
        </div>
      </MobileCard>

      <!-- Overall Statistics -->
      <div v-if="reports.overall" class="stats-grid q-mb-md">
        <MobileCard variant="default" padding="md" class="stat-card-positive">
          <div class="stat-label">Total Days</div>
          <div class="stat-value-large">{{ reports.overall.total_days }}</div>
        </MobileCard>
        <MobileCard variant="default" padding="md" class="stat-card-positive">
          <div class="stat-label">Present</div>
          <div class="stat-value-large">{{ reports.overall.present }}</div>
        </MobileCard>
        <MobileCard variant="default" padding="md" class="stat-card-negative">
          <div class="stat-label">Absent</div>
          <div class="stat-value-large">{{ reports.overall.absent }}</div>
        </MobileCard>
        <MobileCard variant="default" padding="md" class="stat-card-info">
          <div class="stat-label">Attendance Rate</div>
          <div class="stat-value-large">{{ reports.overall.attendance_rate }}%</div>
        </MobileCard>
      </div>

      <!-- By Class -->
      <MobileCard v-if="reports.by_class && reports.by_class.length > 0" variant="default" padding="md" class="q-mb-md">
        <div class="card-title">Attendance by Class</div>
        
        <!-- Mobile View: Card List -->
        <div class="mobile-only">
          <div class="class-reports-list">
            <MobileListCard
              v-for="classReport in reports.by_class"
              :key="classReport.class_id"
              :title="classReport.class_name"
              :subtitle="`Total: ${classReport.total}`"
              :description="`Present: ${classReport.present} | Absent: ${classReport.absent} | Late: ${classReport.late} | Excused: ${classReport.excused}`"
              icon="class"
              :badge="`${classReport.attendance_rate}%`"
              :badge-color="classReport.attendance_rate >= 80 ? 'positive' : classReport.attendance_rate >= 60 ? 'warning' : 'negative'"
            >
              <template #extra>
                <q-linear-progress
                  :value="classReport.attendance_rate / 100"
                  :color="classReport.attendance_rate >= 80 ? 'positive' : classReport.attendance_rate >= 60 ? 'warning' : 'negative'"
                  class="q-mt-sm"
                  style="width: 100px;"
                />
              </template>
            </MobileListCard>
          </div>
        </div>

        <!-- Desktop View: Table -->
        <div class="desktop-only">
          <q-table
          :rows="reports.by_class"
          :columns="classColumns"
          flat
          row-key="class_id"
        >
          <template v-slot:body-cell-attendance_rate="props">
            <q-td :props="props">
              <q-linear-progress
                :value="props.value / 100"
                :color="props.value >= 80 ? 'positive' : props.value >= 60 ? 'warning' : 'negative'"
                class="q-mt-sm"
              />
              <div class="text-caption q-mt-xs">{{ props.value }}%</div>
            </q-td>
          </template>
          </q-table>
        </div>
      </MobileCard>

      <!-- By Date -->
      <MobileCard v-if="reports.by_date && reports.by_date.length > 0" variant="default" padding="md">
        <div class="card-title">Attendance by Date</div>
        
        <!-- Mobile View: Card List -->
        <div class="mobile-only">
          <div class="date-reports-list">
            <MobileListCard
              v-for="dateReport in reports.by_date"
              :key="dateReport.date"
              :title="formatDate(dateReport.date)"
              :subtitle="`Total: ${dateReport.total}`"
              :description="`Present: ${dateReport.present} | Absent: ${dateReport.absent} | Late: ${dateReport.late} | Excused: ${dateReport.excused}`"
              icon="event"
            />
          </div>
        </div>

        <!-- Desktop View: Table -->
        <div class="desktop-only">
          <q-table
          :rows="reports.by_date"
          :columns="dateColumns"
          flat
          row-key="date"
        >
          <template v-slot:body-cell-date="props">
            <q-td :props="props">
              {{ formatDate(props.value) }}
            </q-td>
          </template>
          </q-table>
        </div>
      </MobileCard>

      <MobileCard v-if="loading" variant="default" padding="lg">
        <div class="loading-center">
          <q-spinner color="primary" size="3em" />
        </div>
      </MobileCard>

      <MobileCard v-else-if="!reports.overall" variant="default" padding="lg">
        <div class="empty-state">
          <q-icon name="bar_chart" size="48px" color="grey-6" />
          <div class="empty-text">No Reports Available</div>
          <div class="empty-subtext">Select filters to view attendance statistics.</div>
        </div>
      </MobileCard>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useQuasar } from 'quasar';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import MobileListCard from 'src/components/mobile/MobileListCard.vue';
import api from 'src/services/api';

const $q = useQuasar();

const loading = ref(false);
const reports = ref({});
const classes = ref([]);
const terms = ref([]);
const filterClass = ref(null);
const filterTerm = ref(null);
const startDate = ref(null);
const endDate = ref(null);

const classColumns = [
  {
    name: 'class_name',
    label: 'Class',
    field: 'class_name',
    align: 'left',
    sortable: true,
  },
  {
    name: 'total',
    label: 'Total',
    field: 'total',
    align: 'center',
    sortable: true,
  },
  {
    name: 'present',
    label: 'Present',
    field: 'present',
    align: 'center',
    sortable: true,
  },
  {
    name: 'absent',
    label: 'Absent',
    field: 'absent',
    align: 'center',
    sortable: true,
  },
  {
    name: 'late',
    label: 'Late',
    field: 'late',
    align: 'center',
    sortable: true,
  },
  {
    name: 'excused',
    label: 'Excused',
    field: 'excused',
    align: 'center',
    sortable: true,
  },
  {
    name: 'attendance_rate',
    label: 'Attendance Rate',
    field: 'attendance_rate',
    align: 'left',
  },
];

const dateColumns = [
  {
    name: 'date',
    label: 'Date',
    field: 'date',
    align: 'left',
    sortable: true,
  },
  {
    name: 'total',
    label: 'Total',
    field: 'total',
    align: 'center',
    sortable: true,
  },
  {
    name: 'present',
    label: 'Present',
    field: 'present',
    align: 'center',
    sortable: true,
  },
  {
    name: 'absent',
    label: 'Absent',
    field: 'absent',
    align: 'center',
    sortable: true,
  },
  {
    name: 'late',
    label: 'Late',
    field: 'late',
    align: 'center',
    sortable: true,
  },
  {
    name: 'excused',
    label: 'Excused',
    field: 'excused',
    align: 'center',
    sortable: true,
  },
];

const classOptions = computed(() => {
  return classes.value.map((cls) => ({
    label: cls.name,
    value: cls.id,
  }));
});

const termOptions = computed(() => {
  return terms.value.map((term) => ({
    label: term.name,
    value: term.id,
  }));
});

const formatDate = (date) => {
  if (!date) return 'N/A';
  const d = new Date(date);
  return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
};

const fetchClasses = async () => {
  try {
    const response = await api.get('/classes', { params: { per_page: 100 } });
    if (response.data.success) {
      classes.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch classes:', error);
  }
};

const fetchTerms = async () => {
  try {
    const response = await api.get('/terms', { params: { per_page: 100 } });
    if (response.data.success) {
      terms.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch terms:', error);
  }
};

const fetchReports = async () => {
  loading.value = true;
  try {
    const params = {};

    if (filterClass.value) {
      params.class_id = filterClass.value;
    }

    if (filterTerm.value) {
      params.term_id = filterTerm.value;
    }

    if (startDate.value) {
      params.start_date = startDate.value;
    }

    if (endDate.value) {
      params.end_date = endDate.value;
    }

    const response = await api.get('/attendance/reports', { params });

    if (response.data.success && response.data.data) {
      reports.value = response.data.data;
    } else {
      reports.value = {};
    }
  } catch (error) {
    console.error('Failed to fetch reports:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to load attendance reports. Please try again.',
      position: 'top',
    });
    reports.value = {};
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchClasses();
  fetchTerms();
  fetchReports();
});
</script>

<script>
export default {
  name: 'AttendanceReportsPage',
};
</script>

<style lang="scss" scoped>
.attendance-reports-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.page-content {
  max-width: 1400px;
  margin: 0 auto;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-md);
  
  @media (min-width: 768px) {
    grid-template-columns: repeat(4, 1fr);
  }
}

.stat-card-positive {
  background: linear-gradient(135deg, var(--q-positive) 0%, var(--q-positive-dark) 100%);
  color: white;
}

.stat-card-negative {
  background: linear-gradient(135deg, var(--q-negative) 0%, var(--q-negative-dark) 100%);
  color: white;
}

.stat-card-info {
  background: linear-gradient(135deg, var(--q-info) 0%, var(--q-info-dark) 100%);
  color: white;
}

.stat-label {
  font-size: var(--font-size-sm);
  opacity: 0.9;
  margin-bottom: var(--spacing-xs);
}

.stat-value-large {
  font-size: var(--font-size-2xl);
  font-weight: 700;
}

.card-title {
  font-size: var(--font-size-lg);
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: var(--spacing-md);
}

.mobile-only {
  display: block;
  
  @media (min-width: 768px) {
    display: none;
  }
}

.desktop-only {
  display: none;
  
  @media (min-width: 768px) {
    display: block;
  }
}

.class-reports-list,
.date-reports-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.loading-center {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-xl);
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-2xl);
  text-align: center;
}

.empty-text {
  font-size: var(--font-size-lg);
  font-weight: 600;
  color: var(--text-primary);
  margin-top: var(--spacing-md);
}

.empty-subtext {
  font-size: var(--font-size-base);
  color: var(--text-secondary);
  margin-top: var(--spacing-sm);
}
</style>
