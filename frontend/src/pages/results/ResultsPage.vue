<template>
  <q-page class="results-page">
    <!-- Mobile Header -->
    <div class="mobile-only">
      <MobilePageHeader
        title="Results"
        subtitle="View and manage student results"
      >
        <template #actions>
          <q-btn
            v-if="authStore.isTeacher || authStore.isSchoolAdmin || authStore.isSuperAdmin"
            flat
            round
            dense
            icon="edit"
            color="primary"
            to="/app/results/enter"
          >
            <q-tooltip>Enter Results</q-tooltip>
          </q-btn>
        </template>
      </MobilePageHeader>
    </div>

    <!-- Desktop Header -->
    <div class="desktop-only q-pa-lg">
      <div class="row items-center justify-between q-mb-lg">
        <div>
          <div class="text-h5 text-weight-bold">Results</div>
          <div class="text-body2 text-grey-7">View and manage student results</div>
        </div>
        <q-btn
          v-if="authStore.isTeacher || authStore.isSchoolAdmin || authStore.isSuperAdmin"
          color="primary"
          label="Enter Results"
          icon="edit"
          unelevated
          to="/app/results/enter"
        />
      </div>
    </div>

    <div class="page-content">
      <MobileCard variant="default" padding="md">
        <div class="row q-mb-md">
          <div class="col-12 col-md-3">
            <q-select
              v-model="filterAssessment"
              :options="assessments"
              option-label="name"
              option-value="id"
              emit-value
              map-options
              outlined
              dense
              clearable
              label="Filter by Assessment"
              @update:model-value="onFilter"
              :loading="loadingAssessments"
            >
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section>
                    <q-item-label>{{ scope.opt.name }}</q-item-label>
                    <q-item-label caption>
                      {{ scope.opt.class_subject?.subject?.name || '' }} - 
                      {{ scope.opt.class_subject?.class?.name || '' }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
          </div>
          <div class="col-12 col-md-3">
            <q-select
              v-model="filterStudent"
              :options="students"
              option-label="full_name"
              option-value="id"
              emit-value
              map-options
              outlined
              dense
              clearable
              label="Filter by Student"
              @update:model-value="onFilter"
              :loading="loadingStudents"
            >
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section>
                    <q-item-label>{{ scope.opt.full_name || `${scope.opt.first_name} ${scope.opt.last_name}` }}</q-item-label>
                    <q-item-label caption>{{ scope.opt.student_number || '' }}</q-item-label>
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
          </div>
          <div class="col-12 col-md-3">
            <q-select
              v-model="filterTerm"
              :options="terms"
              option-label="name"
              option-value="id"
              emit-value
              map-options
              outlined
              dense
              clearable
              label="Filter by Term"
              @update:model-value="onFilter"
              :loading="loadingTerms"
            />
          </div>
          <div class="col-12 col-md-3">
            <q-input
              v-model="searchQuery"
              outlined
              dense
              placeholder="Search results..."
              @update:model-value="onSearch"
              clearable
            >
              <template v-slot:prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>
        </div>

        <!-- Mobile View: Card List -->
        <div class="mobile-only">
          <div v-if="loading" class="text-center q-pa-xl">
            <q-spinner color="primary" size="3em" />
          </div>
          <div v-else-if="results.length === 0" class="empty-state">
            <q-icon name="assignment" size="64px" color="grey-5" />
            <div class="empty-text">No Results Found</div>
            <div class="empty-subtext">No results found for the selected filters.</div>
          </div>
          <div v-else class="results-list">
            <MobileListCard
              v-for="result in results"
              :key="result.id"
              :title="result.student?.full_name || getStudentName(result.student)"
              :subtitle="result.assessment?.name || 'N/A'"
              :description="`${result.assessment?.class_subject?.subject?.name || ''} - ${result.assessment?.class_subject?.class?.name || ''}`"
              icon="assignment"
              :badge="result.grade || '-'"
              :badge-color="result.grade ? getGradeColor(result.grade) : 'grey'"
              :clickable="true"
              @click="viewStudentResults(result.student?.id)"
            >
              <template #extra>
                <div class="result-marks">
                  <div class="marks-display">
                    {{ result.marks_obtained || '-' }} / {{ result.assessment?.total_marks || '-' }}
                  </div>
                  <div v-if="result.marks_obtained && result.assessment?.total_marks" class="percentage">
                    {{ calculatePercentage(result.marks_obtained, result.assessment.total_marks) }}%
                  </div>
                </div>
              </template>
            </MobileListCard>
          </div>
        </div>

        <!-- Desktop View: Table -->
        <div class="desktop-only">
          <q-table
          :rows="results"
          :columns="columns"
          row-key="id"
          :loading="loading"
          :pagination="pagination"
          @request="onRequest"
          flat
        >
          <template v-slot:body-cell-student="props">
            <q-td :props="props">
              <div class="text-body2">{{ props.row.student?.full_name || getStudentName(props.row.student) }}</div>
              <div class="text-caption text-grey-7">{{ props.row.student?.student_number || '' }}</div>
            </q-td>
          </template>

          <template v-slot:body-cell-assessment="props">
            <q-td :props="props">
              <div class="text-body2">{{ props.row.assessment?.name || 'N/A' }}</div>
              <div class="text-caption text-grey-7">
                {{ props.row.assessment?.class_subject?.subject?.name || '' }} - 
                {{ props.row.assessment?.class_subject?.class?.name || '' }}
              </div>
            </q-td>
          </template>

          <template v-slot:body-cell-marks="props">
            <q-td :props="props">
              <div class="text-body2">
                {{ props.row.marks_obtained || '-' }} / {{ props.row.assessment?.total_marks || '-' }}
              </div>
              <div class="text-caption text-grey-7" v-if="props.row.marks_obtained && props.row.assessment?.total_marks">
                {{ calculatePercentage(props.row.marks_obtained, props.row.assessment.total_marks) }}%
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

          <template v-slot:body-cell-actions="props">
            <q-td :props="props">
              <q-btn
                flat
                dense
                icon="visibility"
                color="primary"
                @click="viewStudentResults(props.row.student?.id)"
                class="q-mr-xs"
              />
              <q-btn
                flat
                dense
                icon="description"
                color="primary"
                @click="viewAssessment(props.row.assessment?.id)"
              />
            </q-td>
          </template>
          </q-table>
        </div>
      </MobileCard>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'src/stores/auth';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import MobileListCard from 'src/components/mobile/MobileListCard.vue';
import api from 'src/services/api';

const router = useRouter();
const $q = useQuasar();
const authStore = useAuthStore();

const loading = ref(false);
const loadingAssessments = ref(false);
const loadingStudents = ref(false);
const loadingTerms = ref(false);
const results = ref([]);
const assessments = ref([]);
const students = ref([]);
const terms = ref([]);
const filterAssessment = ref(null);
const filterStudent = ref(null);
const filterTerm = ref(null);
const searchQuery = ref('');

const pagination = ref({
  page: 1,
  rowsPerPage: 15,
  rowsNumber: 0,
});

const columns = [
  { name: 'student', label: 'Student', field: 'student', align: 'left' },
  { name: 'assessment', label: 'Assessment', field: 'assessment', align: 'left' },
  { name: 'marks', label: 'Marks', field: 'marks', align: 'left' },
  { name: 'grade', label: 'Grade', field: 'grade', align: 'center' },
  { name: 'remarks', label: 'Remarks', field: 'remarks', align: 'left' },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'right' },
];

onMounted(() => {
  fetchAssessments();
  fetchStudents();
  fetchTerms();
  fetchResults();
});

function onRequest(props) {
  pagination.value = props.pagination;
  fetchResults();
}

function onFilter() {
  pagination.value.page = 1;
  fetchResults();
}

function onSearch() {
  pagination.value.page = 1;
  fetchResults();
}

async function fetchAssessments() {
  loadingAssessments.value = true;
  try {
    const response = await api.get('/assessments', { params: { per_page: 100 } });
    if (response.data.success) {
      assessments.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch assessments:', error);
  } finally {
    loadingAssessments.value = false;
  }
}

async function fetchStudents() {
  loadingStudents.value = true;
  try {
    const response = await api.get('/students', { params: { per_page: 100 } });
    if (response.data.success) {
      students.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch students:', error);
  } finally {
    loadingStudents.value = false;
  }
}

async function fetchTerms() {
  loadingTerms.value = true;
  try {
    const response = await api.get('/terms', { params: { per_page: 100 } });
    if (response.data.success) {
      terms.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch terms:', error);
  } finally {
    loadingTerms.value = false;
  }
}

async function fetchResults() {
  loading.value = true;
  try {
    const params = {
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
    };

    if (filterAssessment.value) {
      params.assessment_id = filterAssessment.value;
    }

    if (filterStudent.value) {
      params.student_id = filterStudent.value;
    }

    if (searchQuery.value) {
      params.search = searchQuery.value;
    }

    const response = await api.get('/results', { params });
    if (response.data.success) {
      results.value = response.data.data || [];
      pagination.value.rowsNumber = response.data.meta?.total || 0;
    }
  } catch (error) {
    console.error('Failed to fetch results:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch results',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
}

function viewStudentResults(studentId) {
  if (studentId) {
    router.push(`/app/results/${studentId}`);
  }
}

function viewAssessment(assessmentId) {
  if (assessmentId) {
    // Check if it's an exam or assessment
    const assessment = assessments.value.find(a => a.id === assessmentId);
    if (assessment?.type === 'exam') {
      router.push(`/app/exams/${assessmentId}`);
    } else {
      router.push(`/app/assessments/${assessmentId}`);
    }
  }
}

function getStudentName(student) {
  if (!student) return 'N/A';
  if (student.full_name) return student.full_name;
  return `${student.first_name || ''} ${student.last_name || ''}`.trim() || 'N/A';
}

function calculatePercentage(obtained, total) {
  if (!obtained || !total) return '0';
  return ((obtained / total) * 100).toFixed(1);
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
.results-page {
  padding: 0;
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
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

.page-content {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: 0;
  }
}

.results-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.result-marks {
  text-align: right;
}

.marks-display {
  font-size: var(--font-size-base);
  font-weight: 600;
  color: var(--text-primary);
}

.percentage {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  margin-top: 2px;
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
