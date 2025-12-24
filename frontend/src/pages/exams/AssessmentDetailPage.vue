<template>
  <q-page class="q-pa-lg" v-if="assessment">
    <div class="row items-center q-mb-lg">
      <q-btn
        flat
        icon="arrow_back"
        @click="$router.push('/app/assessments')"
        class="q-mr-md"
      />
      <div>
        <div class="text-h5 text-weight-bold">{{ assessment.name }}</div>
        <div class="text-body2 text-grey-7">
          <q-badge
            :color="getTypeColor(assessment.type)"
            :label="formatType(assessment.type)"
            class="q-mr-sm"
          />
          {{ assessment.class_subject?.subject?.name || 'N/A' }} - {{ assessment.class_subject?.class?.name || 'N/A' }}
        </div>
      </div>
      <q-space />
      <q-btn
        v-if="(authStore.isSchoolAdmin || authStore.isSuperAdmin) && canEdit"
        color="primary"
        label="Edit"
        icon="edit"
        unelevated
        @click="editAssessment"
        class="q-mr-sm"
      />
      <q-btn
        v-if="(authStore.isSchoolAdmin || authStore.isSuperAdmin) && canEdit"
        color="negative"
        label="Delete"
        icon="delete"
        unelevated
        @click="deleteAssessment"
      />
    </div>

    <div class="row q-col-gutter-md">
      <!-- Assessment Information -->
      <div class="col-12 col-md-8">
        <q-card class="widget-card q-mb-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">Assessment Information</div>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-7">Type</div>
                <div class="text-body1">
                  <q-badge
                    :color="getTypeColor(assessment.type)"
                    :label="formatType(assessment.type)"
                  />
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-7">Term</div>
                <div class="text-body1">{{ assessment.term?.name || 'N/A' }}</div>
                <div class="text-caption text-grey-7 q-mt-xs">
                  {{ assessment.term?.academic_year?.name || '' }}
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-7">Class</div>
                <div class="text-body1">{{ assessment.class_subject?.class?.name || 'N/A' }}</div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-7">Subject</div>
                <div class="text-body1">{{ assessment.class_subject?.subject?.name || 'N/A' }}</div>
                <div class="text-caption text-grey-7 q-mt-xs" v-if="assessment.class_subject?.subject?.code">
                  Code: {{ assessment.class_subject.subject.code }}
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-7">Teacher</div>
                <div class="text-body1" v-if="assessment.teacher?.user">
                  {{ assessment.teacher.user.first_name }} {{ assessment.teacher.user.last_name }}
                </div>
                <div class="text-body1 text-grey-7" v-else>Not assigned</div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-7">Total Marks</div>
                <div class="text-body1">{{ assessment.total_marks }}</div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-7">Weight</div>
                <div class="text-body1">{{ assessment.weight }}%</div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-7">Assessment Date</div>
                <div class="text-body1">{{ formatDate(assessment.assessment_date) }}</div>
              </div>
              <div class="col-12 col-md-6" v-if="assessment.due_date">
                <div class="text-caption text-grey-7">Due Date</div>
                <div class="text-body1">{{ formatDate(assessment.due_date) }}</div>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Results -->
        <q-card class="widget-card">
          <q-card-section>
            <div class="row items-center justify-between q-mb-md">
              <div class="text-h6">Results</div>
              <q-btn
                v-if="authStore.isTeacher || authStore.isSchoolAdmin || authStore.isSuperAdmin"
                color="primary"
                label="Enter Results"
                icon="add"
                unelevated
                @click="enterResults"
              />
            </div>

            <q-table
              v-if="results.length > 0"
              :rows="results"
              :columns="resultColumns"
              row-key="id"
              flat
            >
              <template v-slot:body-cell-student="props">
                <q-td :props="props">
                  <div class="text-body2">{{ props.row.student?.full_name || 'N/A' }}</div>
                  <div class="text-caption text-grey-7">{{ props.row.student?.student_number || '' }}</div>
                </q-td>
              </template>

              <template v-slot:body-cell-marks="props">
                <q-td :props="props">
                  <div class="text-body2">
                    {{ props.row.marks_obtained || '-' }} / {{ assessment.total_marks }}
                  </div>
                  <div class="text-caption text-grey-7" v-if="props.row.marks_obtained">
                    {{ calculatePercentage(props.row.marks_obtained) }}%
                  </div>
                </q-td>
              </template>

              <template v-slot:body-cell-grade="props">
                <q-td :props="props">
                  <q-badge
                    v-if="props.row.grade"
                    :color="getGradeColor(props.row.grade)"
                    :label="props.row.grade"
                  />
                  <span v-else class="text-grey-7">-</span>
                </q-td>
              </template>
            </q-table>

            <div v-else class="text-body2 text-grey-7 text-center q-pa-lg">
              No results entered yet. Click "Enter Results" to add student scores.
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Quick Actions -->
      <div class="col-12 col-md-4">
        <q-card class="widget-card">
          <q-card-section>
            <div class="text-h6 q-mb-md">Quick Actions</div>
            <div class="q-gutter-sm">
              <q-btn
                flat
                color="primary"
                icon="assignment"
                label="View Class"
                @click="viewClass"
                class="full-width"
              />
              <q-btn
                flat
                color="primary"
                icon="book"
                label="View Subject"
                @click="viewSubject"
                class="full-width"
              />
              <q-btn
                flat
                color="primary"
                icon="calendar_today"
                label="View Term"
                @click="viewTerm"
                class="full-width"
              />
              <q-btn
                v-if="assessment.type === 'exam'"
                flat
                color="primary"
                icon="quiz"
                label="View as Exam"
                @click="viewAsExam"
                class="full-width"
              />
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>

  <q-page v-else class="q-pa-lg flex flex-center">
    <q-spinner color="primary" size="3em" />
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'src/stores/auth';
import api from 'src/services/api';

const route = useRoute();
const router = useRouter();
const $q = useQuasar();
const authStore = useAuthStore();

const loading = ref(false);
const assessment = ref(null);
const results = ref([]);

const resultColumns = [
  { name: 'student', label: 'Student', field: 'student', align: 'left' },
  { name: 'marks', label: 'Marks', field: 'marks', align: 'left' },
  { name: 'grade', label: 'Grade', field: 'grade', align: 'center' },
  { name: 'remarks', label: 'Remarks', field: 'remarks', align: 'left' },
];

const canEdit = computed(() => {
  if (!assessment.value?.term) return false;
  return assessment.value.term.status === 'draft' || assessment.value.term.status === 'active';
});

onMounted(() => {
  fetchAssessment();
  fetchResults();
});

async function fetchAssessment() {
  loading.value = true;
  try {
    const response = await api.get(`/assessments/${route.params.id}`);
    if (response.data.success) {
      assessment.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to fetch assessment:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch assessment',
      position: 'top',
    });
    router.push('/app/assessments');
  } finally {
    loading.value = false;
  }
}

async function fetchResults() {
  try {
    const response = await api.get(`/assessments/${route.params.id}/results`);
    if (response.data.success) {
      results.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch results:', error);
  }
}

function editAssessment() {
  // For now, navigate to edit - can be implemented later
  $q.notify({
    type: 'info',
    message: 'Edit functionality coming soon',
    position: 'top',
  });
}

async function deleteAssessment() {
  $q.dialog({
    title: 'Confirm Delete',
    message: `Are you sure you want to delete the assessment "${assessment.value.name}"?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await api.delete(`/assessments/${assessment.value.id}`);
      $q.notify({
        type: 'positive',
        message: 'Assessment deleted successfully',
        position: 'top',
      });
      router.push('/app/assessments');
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to delete assessment',
        position: 'top',
      });
    }
  });
}

function enterResults() {
  router.push(`/app/results/enter?assessment_id=${assessment.value.id}`);
}

function viewClass() {
  if (assessment.value?.class_subject?.class?.id) {
    router.push(`/app/classes/${assessment.value.class_subject.class.id}`);
  }
}

function viewSubject() {
  if (assessment.value?.class_subject?.subject?.id) {
    router.push(`/app/subjects/${assessment.value.class_subject.subject.id}`);
  }
}

function viewTerm() {
  if (assessment.value?.term?.id) {
    router.push(`/app/terms/${assessment.value.term.id}`);
  }
}

function viewAsExam() {
  if (assessment.value?.type === 'exam') {
    router.push(`/app/exams/${assessment.value.id}`);
  }
}

function formatDate(date) {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
}

function formatType(type) {
  return type ? type.charAt(0).toUpperCase() + type.slice(1) : 'N/A';
}

function getTypeColor(type) {
  const colors = {
    exam: 'primary',
    quiz: 'info',
    assignment: 'warning',
    project: 'positive',
    other: 'grey',
  };
  return colors[type] || 'grey';
}

function calculatePercentage(marksObtained) {
  if (!assessment.value?.total_marks) return 0;
  return ((marksObtained / assessment.value.total_marks) * 100).toFixed(1);
}

function getGradeColor(grade) {
  const colors = {
    A: 'positive',
    B: 'positive',
    C: 'info',
    D: 'warning',
    E: 'negative',
    F: 'negative',
  };
  return colors[grade] || 'grey';
}
</script>

<style lang="scss" scoped>
.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}
</style>

