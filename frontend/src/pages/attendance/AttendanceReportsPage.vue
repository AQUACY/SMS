<template>
  <q-page class="q-pa-lg">
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">Attendance Reports</div>
        <div class="text-body2 text-grey-7">View attendance statistics and reports</div>
      </div>
    </div>

    <!-- Filters -->
    <q-card class="widget-card q-mb-md">
      <q-card-section>
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
      </q-card-section>
    </q-card>

    <!-- Overall Statistics -->
    <div v-if="reports.overall" class="row q-col-gutter-md q-mb-md">
      <div class="col-12 col-md-3">
        <q-card class="stat-card bg-positive text-white">
          <q-card-section>
            <div class="text-h6">Total Days</div>
            <div class="text-h4">{{ reports.overall.total_days }}</div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-3">
        <q-card class="stat-card bg-positive text-white">
          <q-card-section>
            <div class="text-h6">Present</div>
            <div class="text-h4">{{ reports.overall.present }}</div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-3">
        <q-card class="stat-card bg-negative text-white">
          <q-card-section>
            <div class="text-h6">Absent</div>
            <div class="text-h4">{{ reports.overall.absent }}</div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-3">
        <q-card class="stat-card bg-info text-white">
          <q-card-section>
            <div class="text-h6">Attendance Rate</div>
            <div class="text-h4">{{ reports.overall.attendance_rate }}%</div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- By Class -->
    <q-card v-if="reports.by_class && reports.by_class.length > 0" class="widget-card q-mb-md">
      <q-card-section>
        <div class="text-h6 q-mb-md">Attendance by Class</div>
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
      </q-card-section>
    </q-card>

    <!-- By Date -->
    <q-card v-if="reports.by_date && reports.by_date.length > 0" class="widget-card">
      <q-card-section>
        <div class="text-h6 q-mb-md">Attendance by Date</div>
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
      </q-card-section>
    </q-card>

    <q-card v-if="loading" class="widget-card">
      <q-card-section>
        <div class="row justify-center q-pa-xl">
          <q-spinner color="primary" size="3em" />
        </div>
      </q-card-section>
    </q-card>

    <q-card v-else-if="!reports.overall" class="widget-card">
      <q-card-section>
        <div class="text-center q-pa-lg">
          <q-icon name="bar_chart" size="48px" color="grey-6" />
          <div class="text-h6 q-mt-md">No Reports Available</div>
          <div class="text-body2 text-grey-7">Select filters and click "Generate Report" to view attendance statistics.</div>
        </div>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useQuasar } from 'quasar';
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
.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}

.stat-card {
  border-radius: 16px;
}
</style>
