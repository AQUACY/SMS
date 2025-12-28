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
        v-if="assessment && (authStore.isTeacher || authStore.isSchoolAdmin || authStore.isSuperAdmin) && !assessment.is_finalized && canEdit"
        color="positive"
        label="Finalize"
        icon="check_circle"
        unelevated
        @click="finalizeAssessment"
        class="q-mr-sm"
      />
      <q-btn
        v-if="assessment && (authStore.isTeacher || authStore.isSchoolAdmin || authStore.isSuperAdmin) && !assessment.is_finalized && canEdit"
        color="primary"
        label="Edit"
        icon="edit"
        unelevated
        @click="editAssessment"
        class="q-mr-sm"
      />
      <q-btn
        v-if="assessment && (authStore.isTeacher || authStore.isSchoolAdmin || authStore.isSuperAdmin) && !assessment.is_finalized && canEdit"
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
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-7">Status</div>
                <div class="text-body1">
                  <q-badge
                    :color="assessment.is_finalized ? 'positive' : 'warning'"
                    :label="assessment.is_finalized ? 'Finalized' : 'Pending'"
                    size="md"
                  />
                </div>
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

    <!-- Edit Dialog -->
    <q-dialog 
      v-model="showEditDialog" 
      persistent
      :maximized="$q.screen.lt.sm"
      transition-show="slide-up"
      transition-hide="slide-down"
    >
      <q-card class="edit-dialog-card">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Assessment</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="showEditDialog = false" />
        </q-card-section>

        <q-card-section class="q-pt-md">
          <q-form @submit="saveEdit" class="q-gutter-md">
            <q-select
              v-model="editForm.type"
              :options="typeOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              label="Assessment Type *"
              outlined
              dense
              :rules="[(val) => !!val || 'Assessment type is required']"
            />

            <q-input
              v-model="editForm.name"
              label="Assessment Name *"
              outlined
              dense
              :rules="[(val) => !!val || 'Assessment name is required']"
            />

            <div class="row q-col-gutter-sm">
              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="editForm.total_marks"
                  label="Marks *"
                  type="number"
                  outlined
                  dense
                  :rules="[
                    (val) => val !== null && val !== '' || 'Total marks is required',
                    (val) => val > 0 || 'Total marks must be greater than 0',
                    (val) => val <= 999.99 || 'Total marks cannot exceed 999.99',
                  ]"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-input
                  v-model.number="editForm.weight"
                  label="Weight (%) *"
                  type="number"
                  outlined
                  dense
                  :rules="[
                    (val) => val !== null && val !== '' || 'Weight is required',
                    (val) => val >= 0 || 'Weight cannot be negative',
                    (val) => val <= 100 || 'Weight cannot exceed 100%',
                  ]"
                />
              </div>
            </div>

            <div class="row q-col-gutter-sm">
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="editForm.assessment_date"
                  label="Assessment Date *"
                  type="date"
                  outlined
                  dense
                  :rules="[(val) => !!val || 'Assessment date is required']"
                />
              </div>

              <div class="col-12 col-sm-6">
                <q-input
                  v-model="editForm.due_date"
                  label="Due Date"
                  type="date"
                  outlined
                  dense
                  :rules="[
                    (val) => !val || !editForm.assessment_date || val >= editForm.assessment_date || 'Due date must be on or after assessment date',
                  ]"
                />
              </div>
            </div>

            <div class="row q-mt-md q-gutter-sm">
              <div class="col-12 col-sm-6">
                <q-btn
                  flat
                  label="Cancel"
                  color="grey-7"
                  class="full-width"
                  @click="showEditDialog = false"
                />
              </div>
              <div class="col-12 col-sm-6">
                <q-btn
                  type="submit"
                  color="primary"
                  label="Save Changes"
                  unelevated
                  class="full-width"
                />
              </div>
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
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
  if (!assessment.value) return false;
  
  // Admins can always edit (they'll get backend validation if needed)
  if (authStore.isSchoolAdmin || authStore.isSuperAdmin) {
    return !assessment.value.is_finalized;
  }
  
  // For teachers, check ownership and other conditions
  if (authStore.isTeacher) {
    // Cannot edit if assessment is finalized
    if (assessment.value.is_finalized) return false;
    
    // Check if term allows editing
    if (!assessment.value.term) return false;
    const termStatus = assessment.value.term.status;
    const termAllowsEdit = termStatus === 'draft' || termStatus === 'active';
    if (!termAllowsEdit) return false;
    
    // Teachers can only edit their own assessments
    const teacherId = authStore.user?.teacher?.id;
    return assessment.value.teacher_id === teacherId;
  }
  
  return false;
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

const showEditDialog = ref(false);
const editForm = ref({
  name: '',
  type: null,
  total_marks: null,
  weight: null,
  assessment_date: '',
  due_date: '',
});

const typeOptions = [
  { label: 'Exam', value: 'exam' },
  { label: 'Quiz', value: 'quiz' },
  { label: 'Assignment', value: 'assignment' },
  { label: 'Project', value: 'project' },
  { label: 'Other', value: 'other' },
];

function editAssessment() {
  if (!assessment.value) return;
  
  // Populate edit form with current assessment data
  editForm.value = {
    name: assessment.value.name || '',
    type: assessment.value.type || null,
    total_marks: assessment.value.total_marks || null,
    weight: assessment.value.weight || null,
    assessment_date: assessment.value.assessment_date ? formatDateForInput(assessment.value.assessment_date) : '',
    due_date: assessment.value.due_date ? formatDateForInput(assessment.value.due_date) : '',
  };
  
  showEditDialog.value = true;
}

async function saveEdit() {
  try {
    const response = await api.put(`/assessments/${assessment.value.id}`, editForm.value);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Assessment updated successfully',
        position: 'top',
      });
      showEditDialog.value = false;
      // Refresh assessment data
      await fetchAssessment();
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to update assessment',
      position: 'top',
    });
  }
}

function formatDateForInput(date) {
  if (!date) return '';
  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

async function finalizeAssessment() {
  $q.dialog({
    title: 'Finalize Assessment',
    message: `Are you sure you want to finalize the assessment "${assessment.value.name}"? Once finalized, you won't be able to edit it.`,
    cancel: true,
    persistent: true,
    ok: {
      label: 'Finalize',
      color: 'positive',
      flat: true,
    },
    cancel: {
      label: 'Cancel',
      flat: true,
      color: 'grey-7',
    },
  }).onOk(async () => {
    try {
      const response = await api.put(`/assessments/${assessment.value.id}`, {
        is_finalized: true,
      });
      if (response.data.success) {
        $q.notify({
          type: 'positive',
          message: 'Assessment finalized successfully',
          position: 'top',
        });
        // Refresh assessment data
        await fetchAssessment();
      }
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to finalize assessment',
        position: 'top',
      });
    }
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

.edit-dialog-card {
  width: 100%;
  max-width: 600px;
  min-width: 0;
}

@media (max-width: 600px) {
  .edit-dialog-card {
    width: 100%;
    height: 100%;
    max-width: 100%;
    margin: 0;
    border-radius: 0;
  }
}
</style>

