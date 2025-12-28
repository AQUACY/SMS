<template>
  <q-page class="q-pa-lg" v-if="exam">
    <div class="row items-center q-mb-lg">
      <q-btn
        flat
        icon="arrow_back"
        @click="$router.push('/app/exams')"
        class="q-mr-md"
      />
      <div>
        <div class="text-h5 text-weight-bold">{{ exam.name }}</div>
        <div class="text-body2 text-grey-7">
          {{ exam.class_subject?.subject?.name || 'N/A' }} - {{ exam.class_subject?.class?.name || 'N/A' }}
        </div>
      </div>
      <q-space />
      <q-btn
        v-if="exam && (authStore.isTeacher || authStore.isSchoolAdmin || authStore.isSuperAdmin) && !exam.is_finalized && canEdit"
        color="positive"
        label="Verify"
        icon="check_circle"
        unelevated
        @click="verifyExam"
        class="q-mr-sm"
      />
      <q-btn
        v-if="exam && (authStore.isTeacher || authStore.isSchoolAdmin || authStore.isSuperAdmin) && !exam.is_finalized && canEdit"
        color="primary"
        label="Edit"
        icon="edit"
        unelevated
        @click="editExam"
        class="q-mr-sm"
      />
      <q-btn
        v-if="exam && (authStore.isTeacher || authStore.isSchoolAdmin || authStore.isSuperAdmin) && !exam.is_finalized && canEdit"
        color="negative"
        label="Delete"
        icon="delete"
        unelevated
        @click="deleteExam"
      />
    </div>

    <div class="row q-col-gutter-md">
      <!-- Exam Information -->
      <div class="col-12 col-md-8">
        <q-card class="widget-card q-mb-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">Exam Information</div>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-7">Term</div>
                <div class="text-body1">{{ exam.term?.name || 'N/A' }}</div>
                <div class="text-caption text-grey-7 q-mt-xs">
                  {{ exam.term?.academic_year?.name || '' }}
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-7">Class</div>
                <div class="text-body1">{{ exam.class_subject?.class?.name || 'N/A' }}</div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-7">Subject</div>
                <div class="text-body1">{{ exam.class_subject?.subject?.name || 'N/A' }}</div>
                <div class="text-caption text-grey-7 q-mt-xs" v-if="exam.class_subject?.subject?.code">
                  Code: {{ exam.class_subject.subject.code }}
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-7">Teacher</div>
                <div class="text-body1" v-if="exam.teacher?.user">
                  {{ exam.teacher.user.first_name }} {{ exam.teacher.user.last_name }}
                </div>
                <div class="text-body1 text-grey-7" v-else>Not assigned</div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-7">Total Marks</div>
                <div class="text-body1">{{ exam.total_marks }}</div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-7">Weight</div>
                <div class="text-body1">{{ exam.weight }}%</div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-7">Exam Date</div>
                <div class="text-body1">{{ formatDate(exam.assessment_date) }}</div>
              </div>
              <div class="col-12 col-md-6" v-if="exam.due_date">
                <div class="text-caption text-grey-7">Due Date</div>
                <div class="text-body1">{{ formatDate(exam.due_date) }}</div>
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
                    {{ props.row.marks_obtained || '-' }} / {{ exam.total_marks }}
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
          <div class="text-h6">Exam</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="showEditDialog = false" />
        </q-card-section>

        <q-card-section class="q-pt-md">
          <q-form @submit="saveEdit" class="q-gutter-md">
            <q-input
              v-model="editForm.name"
              label="Exam Name *"
              outlined
              dense
              :rules="[(val) => !!val || 'Exam name is required']"
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
                  label="Exam Date *"
                  type="date"
                  outlined
                  dense
                  :rules="[(val) => !!val || 'Exam date is required']"
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
                    (val) => !val || !editForm.assessment_date || val >= editForm.assessment_date || 'Due date must be on or after exam date',
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
const exam = ref(null);
const results = ref([]);

const resultColumns = [
  { name: 'student', label: 'Student', field: 'student', align: 'left' },
  { name: 'marks', label: 'Marks', field: 'marks', align: 'left' },
  { name: 'grade', label: 'Grade', field: 'grade', align: 'center' },
  { name: 'remarks', label: 'Remarks', field: 'remarks', align: 'left' },
];

const canEdit = computed(() => {
  if (!exam.value) return false;
  
  // Admins can always edit (they'll get backend validation if needed)
  if (authStore.isSchoolAdmin || authStore.isSuperAdmin) {
    return !exam.value.is_finalized;
  }
  
  // For teachers, check ownership and other conditions
  if (authStore.isTeacher) {
    // Cannot edit if exam is finalized
    if (exam.value.is_finalized) return false;
    
    // Check if term allows editing
    if (!exam.value.term) return false;
    const termStatus = exam.value.term.status;
    const termAllowsEdit = termStatus === 'draft' || termStatus === 'active';
    if (!termAllowsEdit) return false;
    
    // Teachers can only edit their own exams
    const teacherId = authStore.user?.teacher?.id;
    return exam.value.teacher_id === teacherId;
  }
  
  return false;
});

onMounted(async () => {
  // If user is a teacher but teacher relationship is missing, refresh user data
  if (authStore.isTeacher && !authStore.user?.teacher) {
    await authStore.fetchUser();
  }
  
  fetchExam();
  fetchResults();
});

async function fetchExam() {
  loading.value = true;
  try {
    const response = await api.get(`/exams/${route.params.id}`);
    if (response.data.success) {
      exam.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to fetch exam:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch exam',
      position: 'top',
    });
    router.push('/app/exams');
  } finally {
    loading.value = false;
  }
}

async function fetchResults() {
  try {
    const response = await api.get(`/exams/${route.params.id}/results`);
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
  total_marks: null,
  weight: null,
  assessment_date: '',
  due_date: '',
});

function editExam() {
  if (!exam.value) return;
  
  // Populate edit form with current exam data
  editForm.value = {
    name: exam.value.name || '',
    total_marks: exam.value.total_marks || null,
    weight: exam.value.weight || null,
    assessment_date: exam.value.assessment_date ? formatDateForInput(exam.value.assessment_date) : '',
    due_date: exam.value.due_date ? formatDateForInput(exam.value.due_date) : '',
  };
  
  showEditDialog.value = true;
}

async function saveEdit() {
  try {
    const response = await api.put(`/exams/${exam.value.id}`, editForm.value);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Exam updated successfully',
        position: 'top',
      });
      showEditDialog.value = false;
      // Refresh exam data
      await fetchExam();
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to update exam',
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

async function verifyExam() {
  $q.dialog({
    title: 'Verify Exam',
    message: `Are you sure you want to verify the exam "${exam.value.name}"? Once verified, you won't be able to edit it.`,
    cancel: true,
    persistent: true,
    ok: {
      label: 'Verify',
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
      const response = await api.put(`/exams/${exam.value.id}`, {
        is_finalized: true,
      });
      if (response.data.success) {
        $q.notify({
          type: 'positive',
          message: 'Exam verified successfully',
          position: 'top',
        });
        // Refresh exam data
        await fetchExam();
      }
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to verify exam',
        position: 'top',
      });
    }
  });
}

async function deleteExam() {
  $q.dialog({
    title: 'Confirm Delete',
    message: `Are you sure you want to delete the exam "${exam.value.name}"?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await api.delete(`/exams/${exam.value.id}`);
      $q.notify({
        type: 'positive',
        message: 'Exam deleted successfully',
        position: 'top',
      });
      router.push('/app/exams');
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to delete exam',
        position: 'top',
      });
    }
  });
}

function enterResults() {
  router.push(`/app/results/enter?exam_id=${exam.value.id}`);
}

function viewClass() {
  if (exam.value?.class_subject?.class?.id) {
    router.push(`/app/classes/${exam.value.class_subject.class.id}`);
  }
}

function viewSubject() {
  if (exam.value?.class_subject?.subject?.id) {
    router.push(`/app/subjects/${exam.value.class_subject.subject.id}`);
  }
}

function viewTerm() {
  if (exam.value?.term?.id) {
    router.push(`/app/terms/${exam.value.term.id}`);
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

function calculatePercentage(marksObtained) {
  if (!exam.value?.total_marks) return 0;
  return ((marksObtained / exam.value.total_marks) * 100).toFixed(1);
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
