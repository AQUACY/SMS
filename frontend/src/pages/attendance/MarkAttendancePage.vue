<template>
  <q-page class="mark-attendance-page">
    <MobilePageHeader
      title="Mark Attendance"
      subtitle="Mark attendance for a class on a specific date"
      :show-back="true"
      @back="router.push('/app/attendance')"
    />

    <div class="page-content">
      <MobileCard variant="default" padding="md" class="q-mb-md">
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-4">
            <q-select
              v-model="form.class_id"
              :options="classOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              label="Class *"
              :loading="loadingClasses"
              :disable="loadingClasses"
              @update:model-value="onClassChange"
            />
          </div>
          <div class="col-12 col-md-4">
            <q-select
              v-model="form.term_id"
              :options="termOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              label="Term *"
              :loading="loadingTerms"
              :disable="loadingTerms || !form.class_id"
            />
          </div>
          <div class="col-12 col-md-4">
            <q-input
              v-model="form.date"
              label="Date *"
              type="date"
              :max="today"
            />
          </div>
        </div>
      </MobileCard>

      <MobileCard v-if="students.length > 0" variant="default" padding="md">
        <div class="card-title">Mark Attendance for Students</div>
        <div class="row q-col-gutter-sm q-mb-md">
          <q-btn
            color="positive"
            label="Mark All Present"
            class="q-mr-sm"
            icon="check_circle"
            @click="markAll('present')"
            size="sm"
          />
          <q-btn
            color="negative"
            label="Mark All Absent"
            icon="cancel"
            class="q-mr-sm"
            @click="markAll('absent')"
            size="sm"
          />
          <q-btn
            color="warning"
            label="Mark All Late"
            icon="schedule"
            class="q-mr-sm"
            @click="markAll('late')"
            size="sm"
          />
          <q-btn
            color="info"
            label="Mark All Excused"
            icon="event_busy"
            class="q-mr-sm"
            @click="markAll('excused')"
            size="sm"
          />
        </div>

        <q-list separator>
          <q-item
            v-for="student in students"
            :key="student.id"
            class="q-pa-md"
          >
            <q-item-section avatar>
              <q-avatar color="primary" text-color="white">
                {{ getStudentInitials(student) }}
              </q-avatar>
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ getStudentName(student) }}</q-item-label>
              <q-item-label caption>{{ student.student_number }}</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-btn-toggle
                v-model="attendanceData[student.id].status"
                :options="statusOptions"
                class="q-ml-sm q-mr-sm attendance-toggle"
                color="primary"
                size="sm"
                @update:model-value="() => {}"
              />
            </q-item-section>
            <q-item-section side>
              <q-input
                v-model="attendanceData[student.id].remarks"
                label="Remarks"
                dense
                outlined
                style="min-width: 200px;"
              />
            </q-item-section>
          </q-item>
        </q-list>

        <div class="row justify-end q-mt-md">
          <q-btn
            color="primary"
            label="Save Attendance"
            icon="save"
            unelevated
            :loading="submitting"
            @click="submitAttendance"
          />
        </div>
      </MobileCard>

      <MobileCard v-else-if="form.class_id && form.term_id && form.date && !loadingStudents" variant="default" padding="lg">
        <div class="empty-state">
          <q-icon name="info" size="48px" color="grey-6" />
          <div class="empty-text">No students found</div>
          <div class="empty-subtext">This class has no enrolled students for the selected term.</div>
        </div>
      </MobileCard>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import api from 'src/services/api';

const router = useRouter();
const $q = useQuasar();

const loadingClasses = ref(false);
const loadingTerms = ref(false);
const loadingStudents = ref(false);
const submitting = ref(false);
const classes = ref([]);
const terms = ref([]);
const students = ref([]);
const activeTerm = ref(null);

const form = ref({
  class_id: null,
  term_id: null,
  date: new Date().toISOString().split('T')[0],
});

const attendanceData = ref({});

const today = computed(() => {
  return new Date().toISOString().split('T')[0];
});

const statusOptions = [
  { label: 'Present', value: 'present', icon: 'check_circle', color: 'positive' },
  { label: 'Absent', value: 'absent', icon: 'cancel', color: 'negative' },
  { label: 'Late', value: 'late', icon: 'schedule', color: 'warning' },
  { label: 'Excused', value: 'excused', icon: 'event_busy', color: 'info' },
];

const classOptions = computed(() => {
  return classes.value.map((cls) => ({
    label: cls.name,
    value: cls.id,
  }));
});

const termOptions = computed(() => {
  const options = [];
  if (activeTerm.value) {
    options.push({
      label: `${activeTerm.value.name} (Active)`,
      value: activeTerm.value.id,
    });
  }
  terms.value.forEach((term) => {
    if (!activeTerm.value || term.id !== activeTerm.value.id) {
      options.push({
        label: term.name,
        value: term.id,
      });
    }
  });
  return options;
});

const getStudentName = (student) => {
  if (!student) return 'Unknown';
  const parts = [student.first_name, student.middle_name, student.last_name].filter(Boolean);
  return parts.join(' ') || 'Unknown';
};

const getStudentInitials = (student) => {
  if (!student) return '?';
  const firstName = student.first_name || '';
  const lastName = student.last_name || '';
  return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase() || '?';
};

const fetchMarkingData = async () => {
  try {
    const response = await api.get('/attendance/marking-data');
    if (response.data.success && response.data.data) {
      classes.value = response.data.data.classes || [];
      activeTerm.value = response.data.data.active_term;
      
      // Fetch all terms for the dropdown
      const termsResponse = await api.get('/terms', { params: { per_page: 100 } });
      if (termsResponse.data.success) {
        terms.value = termsResponse.data.data || [];
      }
    }
  } catch (error) {
    console.error('Failed to fetch marking data:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to load classes and terms. Please try again.',
      position: 'top',
    });
  }
};

const onClassChange = () => {
  form.value.term_id = activeTerm.value?.id || null;
  fetchStudents();
};

const fetchStudents = async () => {
  if (!form.value.class_id || !form.value.term_id || !form.value.date) {
    students.value = [];
    attendanceData.value = {};
    return;
  }

  loadingStudents.value = true;
  try {
    const response = await api.get(`/classes/${form.value.class_id}/students`);
    if (response.data.success) {
      students.value = response.data.data || [];
      
      // Initialize attendance data for each student
      const data = {};
      students.value.forEach((student) => {
        data[student.id] = {
          student_id: student.id,
          status: 'present',
          remarks: '',
        };
      });
      attendanceData.value = data;
    }
  } catch (error) {
    console.error('Failed to fetch students:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to load students. Please try again.',
      position: 'top',
    });
  } finally {
    loadingStudents.value = false;
  }
};

const markAll = (status) => {
  Object.keys(attendanceData.value).forEach((studentId) => {
    attendanceData.value[studentId].status = status;
  });
};

const submitAttendance = async () => {
  if (!form.value.class_id || !form.value.term_id || !form.value.date) {
    $q.notify({
      type: 'warning',
      message: 'Please fill in all required fields',
      position: 'top',
    });
    return;
  }

  if (students.value.length === 0) {
    $q.notify({
      type: 'warning',
      message: 'No students to mark attendance for',
      position: 'top',
    });
    return;
  }

  submitting.value = true;
  try {
    const payload = {
      class_id: form.value.class_id,
      term_id: form.value.term_id,
      date: form.value.date,
      students: Object.values(attendanceData.value),
    };

    const response = await api.post('/attendance/mark', payload);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Attendance marked successfully',
        position: 'top',
      });
      router.push('/app/attendance');
    }
  } catch (error) {
    console.error('Failed to mark attendance:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to mark attendance. Please try again.',
      position: 'top',
    });
  } finally {
    submitting.value = false;
  }
};

watch(
  () => [form.value.class_id, form.value.term_id, form.value.date],
  () => {
    fetchStudents();
  }
);

onMounted(() => {
  fetchMarkingData();
});
</script>

<script>
import { onMounted } from 'vue';

export default {
  name: 'MarkAttendancePage',
};
</script>

<style lang="scss" scoped>
.mark-attendance-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.page-content {
  max-width: 1200px;
  margin: 0 auto;
}

.card-title {
  font-size: var(--font-size-lg);
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: var(--spacing-md);
}

.attendance-toggle {
  :deep(.q-btn) {
    margin-right: 4px;
    
    &:last-child {
      margin-right: 0;
    }
  }
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-xl);
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
