<template>
  <q-page class="parent-page">
    <!-- Mobile Header -->
    <div class="parent-header q-pa-md">
      <div class="row items-center">
        <q-btn
          flat
          round
          icon="arrow_back"
          @click="router.back()"
          class="q-mr-sm"
          size="md"
        />
        <div class="col">
          <div class="text-h6 text-weight-bold">Attendance</div>
          <div class="text-caption text-grey-7">{{ childName }}</div>
        </div>
      </div>
    </div>

    <!-- Content Area -->
    <div class="parent-content q-pa-md">
      <!-- Term Selection -->
      <q-card class="info-card q-mb-md" v-if="availableTerms.length > 0">
        <q-card-section class="q-pa-md">
          <div class="text-body1 text-weight-medium q-mb-sm">Select Term</div>
          <q-select
            v-model="selectedTermId"
            :options="availableTerms"
            option-label="label"
            option-value="value"
            emit-value
            map-options
            outlined
            dense
            @update:model-value="fetchAttendance"
            class="q-mb-sm"
          />
          <div v-if="selectedTerm" class="text-caption text-grey-7">
            {{ selectedTerm.academic_year?.name }} - {{ selectedTerm.name }}
          </div>
        </q-card-section>
      </q-card>

      <!-- Skeleton Loading -->
      <div v-if="loading" class="q-gutter-md">
        <q-card class="info-card">
          <q-card-section>
            <q-skeleton type="rect" height="100px" class="q-mb-md" />
            <q-skeleton type="text" width="60%" />
            <q-skeleton type="text" width="40%" />
          </q-card-section>
        </q-card>
        <q-card class="info-card">
          <q-card-section>
            <q-skeleton type="list" />
          </q-card-section>
        </q-card>
      </div>

      <!-- Statistics Card -->
      <q-card v-else-if="attendanceStats" class="info-card q-mb-md">
        <q-card-section class="q-pa-md">
          <div class="text-h6 q-mb-md">Attendance Summary</div>
          <div class="row q-gutter-md">
            <div class="col-6">
              <div class="stat-item">
                <div class="text-caption text-grey-7">Total Days</div>
                <div class="text-h6 text-weight-bold">{{ attendanceStats.total_days }}</div>
              </div>
            </div>
            <div class="col-6">
              <div class="stat-item">
                <div class="text-caption text-grey-7">Present</div>
                <div class="text-h6 text-weight-bold text-positive">{{ attendanceStats.present }}</div>
              </div>
            </div>
            <div class="col-6">
              <div class="stat-item">
                <div class="text-caption text-grey-7">Absent</div>
                <div class="text-h6 text-weight-bold text-negative">{{ attendanceStats.absent }}</div>
              </div>
            </div>
            <div class="col-6">
              <div class="stat-item">
                <div class="text-caption text-grey-7">Late</div>
                <div class="text-h6 text-weight-bold text-warning">{{ attendanceStats.late }}</div>
              </div>
            </div>
            <div class="col-6" v-if="attendanceStats.excused > 0">
              <div class="stat-item">
                <div class="text-caption text-grey-7">Excused</div>
                <div class="text-h6 text-weight-bold text-info">{{ attendanceStats.excused }}</div>
              </div>
            </div>
            <div class="col-6" v-if="attendanceStats.total_days > 0">
              <div class="stat-item">
                <div class="text-caption text-grey-7">Attendance Rate</div>
                <div class="text-h6 text-weight-bold">
                  {{ Math.round((attendanceStats.present / attendanceStats.total_days) * 100) }}%
                </div>
              </div>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <!-- Attendance Records -->
      <q-card v-if="attendanceRecords.length > 0" class="info-card">
        <q-card-section class="q-pa-md">
          <div class="text-h6 q-mb-md">Attendance Records</div>
          <div class="q-gutter-sm">
            <q-item
              v-for="record in attendanceRecords"
              :key="record.id"
              class="attendance-item q-pa-md q-mb-sm"
              :class="getStatusClass(record.status)"
            >
              <q-item-section avatar>
                <q-icon :name="getStatusIcon(record.status)" :color="getStatusColor(record.status)" size="24px" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-medium">{{ formatDate(record.date) }}</q-item-label>
                <q-item-label caption>
                  <q-badge :color="getStatusColor(record.status)" :label="record.status.toUpperCase()" />
                  <span v-if="record.remarks" class="q-ml-sm text-grey-7">{{ record.remarks }}</span>
                </q-item-label>
              </q-item-section>
            </q-item>
          </div>
        </q-card-section>
      </q-card>

      <!-- Empty State -->
      <q-card v-else-if="!loading && selectedTermId" class="info-card">
        <q-card-section class="text-center q-pa-xl">
          <q-icon name="event_busy" size="64px" color="grey-5" class="q-mb-md" />
          <div class="text-h6 text-grey-7 q-mb-sm">No Attendance Records</div>
          <div class="text-body2 text-grey-6">
            No attendance records found for this term.
          </div>
        </q-card-section>
      </q-card>

      <!-- Subscription Required -->
      <q-banner
        v-if="!loading && !selectedTermId && availableTerms.length === 0"
        dense
        rounded
        class="bg-warning text-white q-ma-md"
      >
        <template v-slot:avatar>
          <q-icon name="lock" color="white" />
        </template>
        <div class="text-body2">
          No subscription found for any term. Please subscribe to view attendance records.
        </div>
      </q-banner>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import api from 'src/services/api';

const route = useRoute();
const router = useRouter();
const $q = useQuasar();

const studentId = computed(() => route.params.id);
const childName = ref('');
const loading = ref(false);
const attendanceRecords = ref([]);
const attendanceStats = ref(null);
const selectedTermId = ref(null);
const availableTerms = ref([]);
const selectedTerm = ref(null);

onMounted(async () => {
  await fetchChildInfo();
  await fetchAvailableTerms();
  if (availableTerms.value.length > 0) {
    selectedTermId.value = availableTerms.value[0].value;
    await fetchAttendance();
  }
});

async function fetchChildInfo() {
  try {
    const response = await api.get('/parent/children');
    if (response.data.success) {
      const children = response.data.data || [];
      const child = children.find(c => c.id === parseInt(studentId.value));
      if (child) {
        childName.value = child.full_name || `${child.first_name} ${child.last_name}`;
      }
    }
  } catch (error) {
    console.error('Failed to fetch child info:', error);
  }
}

async function fetchAvailableTerms() {
  try {
    // Fetch both subscriptions and payments
    const [subscriptionsResponse, paymentsResponse] = await Promise.all([
      api.get('/parent/subscriptions'),
      api.get('/parent/payments'),
    ]);
    
    const termMap = new Map();
    
    // Add terms from subscriptions
    if (subscriptionsResponse.data.success) {
      const subscriptions = subscriptionsResponse.data.data || [];
      subscriptions.forEach(sub => {
        if (sub.student_id === parseInt(studentId.value) && sub.term) {
          const term = sub.term;
          if (!termMap.has(term.id)) {
            termMap.set(term.id, {
              value: term.id,
              label: `${term.academic_year?.name || ''} - ${term.name}`,
              term: term,
            });
          }
        }
      });
    }
    
    // Add terms from completed subscription payments
    if (paymentsResponse.data.success) {
      const payments = paymentsResponse.data.data || [];
      payments.forEach(payment => {
        if (
          payment.student_id === parseInt(studentId.value) &&
          payment.payment_type === 'subscription_payment' &&
          payment.status === 'completed' &&
          payment.term
        ) {
          const term = payment.term;
          if (!termMap.has(term.id)) {
            termMap.set(term.id, {
              value: term.id,
              label: `${term.academic_year?.name || ''} - ${term.name}`,
              term: term,
            });
          }
        }
      });
    }

    // Also check for active term from child's enrollment
    const childrenResponse = await api.get('/parent/children');
    if (childrenResponse.data.success) {
      const children = childrenResponse.data.data || [];
      const child = children.find(c => c.id === parseInt(studentId.value));
      if (child?.active_enrollment?.class?.academic_year?.active_term) {
        const activeTerm = child.active_enrollment.class.academic_year.active_term;
        if (!termMap.has(activeTerm.id)) {
          termMap.set(activeTerm.id, {
            value: activeTerm.id,
            label: `${activeTerm.academic_year?.name || ''} - ${activeTerm.name} (Current)`,
            term: activeTerm,
          });
        }
      }
    }

    availableTerms.value = Array.from(termMap.values());
    
    // Sort by term start date (newest first)
    availableTerms.value.sort((a, b) => {
      const dateA = new Date(a.term.start_date || 0);
      const dateB = new Date(b.term.start_date || 0);
      return dateB - dateA;
    });
  } catch (error) {
    console.error('Failed to fetch available terms:', error);
  }
}

async function fetchAttendance() {
  if (!selectedTermId.value) return;
  
  loading.value = true;
  try {
    const response = await api.get(`/attendance/student/${studentId.value}`, {
      params: {
        term_id: selectedTermId.value,
      },
    });
    
    if (response.data.success) {
      attendanceRecords.value = response.data.data.records || [];
      attendanceStats.value = response.data.data.statistics || null;
      
      // Find selected term details
      const termOption = availableTerms.value.find(t => t.value === selectedTermId.value);
      selectedTerm.value = termOption?.term || null;
    }
  } catch (error) {
    console.error('Failed to fetch attendance:', error);
    if (error.response?.status === 403) {
      $q.notify({
        type: 'warning',
        message: 'Subscription required to view attendance for this term',
        position: 'top',
      });
    } else {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to fetch attendance records',
        position: 'top',
      });
    }
  } finally {
    loading.value = false;
  }
}

function formatDate(date) {
  if (!date) return 'N/A';
  try {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    if (isNaN(dateObj.getTime())) return 'N/A';
    
    return new Date(dateObj).toLocaleDateString('en-GB', {
      weekday: 'short',
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    });
  } catch (error) {
    return 'N/A';
  }
}

function getStatusClass(status) {
  const classes = {
    present: 'bg-positive-1',
    absent: 'bg-negative-1',
    late: 'bg-warning-1',
    excused: 'bg-info-1',
  };
  return classes[status] || '';
}

function getStatusIcon(status) {
  const icons = {
    present: 'check_circle',
    absent: 'cancel',
    late: 'schedule',
    excused: 'info',
  };
  return icons[status] || 'help';
}

function getStatusColor(status) {
  const colors = {
    present: 'positive',
    absent: 'negative',
    late: 'warning',
    excused: 'info',
  };
  return colors[status] || 'grey';
}
</script>

<style lang="scss" scoped>
.parent-page {
  background: #f5f5f5;
  min-height: 100vh;
}

.parent-header {
  background: white;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  position: sticky;
  top: 0;
  z-index: 100;
}

.parent-content {
  max-width: 1200px;
  margin: 0 auto;
}

.info-card {
  border-radius: 16px;
  border: none;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  background: white;
}

.stat-item {
  padding: 12px;
  background: rgba(0, 0, 0, 0.02);
  border-radius: 12px;
  text-align: center;
}

.attendance-item {
  border-radius: 12px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  background: white;
  transition: all 0.2s ease;

  &:active {
    transform: scale(0.98);
  }
}

// Mobile optimizations
@media (max-width: 600px) {
  .parent-header {
    padding: 12px 16px;
  }

  .parent-content {
    padding: 12px;
  }

  .stat-item {
    padding: 10px;
  }
}
</style>

