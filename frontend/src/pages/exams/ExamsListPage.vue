<template>
  <q-page class="exams-list-page">
    <MobilePageHeader
      title="Exams"
      subtitle="View and manage all exams"
    >
      <template v-slot:actions>
        <q-btn
          v-if="authStore.isTeacher || authStore.isSchoolAdmin || authStore.isSuperAdmin"
          color="primary"
          :label="$q.screen.gt.xs ? 'Create Exam' : ''"
          icon="add"
          unelevated
          to="/app/exams/create"
          class="mobile-btn"
        />
      </template>
    </MobilePageHeader>

    <!-- Filters -->
    <MobileCard variant="default" padding="md" class="filters-card">
      <div class="filters-grid">
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
          class="filter-item"
        />
        <q-select
          v-model="filterClass"
          :options="classes"
          option-label="name"
          option-value="id"
          emit-value
          map-options
          outlined
          dense
          clearable
          label="Filter by Class"
          @update:model-value="onFilter"
          :loading="loadingClasses"
          class="filter-item"
        />
        <q-input
          v-model="searchQuery"
          outlined
          dense
          placeholder="Search exams..."
          @update:model-value="onSearch"
          clearable
          class="filter-item"
        >
          <template v-slot:prepend>
            <q-icon name="search" />
          </template>
        </q-input>
      </div>
    </MobileCard>

    <!-- Mobile Card View -->
    <div class="mobile-list-view">
      <div v-if="loading" class="loading-cards">
        <q-card v-for="i in 3" :key="i" class="mobile-list-card">
          <q-card-section>
            <q-skeleton type="rect" height="100px" />
          </q-card-section>
        </q-card>
      </div>
      
      <div v-else-if="exams.length > 0" class="cards-container">
        <MobileListCard
          v-for="exam in exams"
          :key="exam.id"
          :title="exam.title || 'Exam'"
          :subtitle="exam.class_subject?.subject?.name || 'N/A'"
          :description="getExamDescription(exam)"
          icon="quiz"
          :badge="exam.is_finalized ? 'Verified' : 'Pending'"
          :badge-color="exam.is_finalized ? 'positive' : 'warning'"
          icon-bg="rgba(63, 81, 181, 0.1)"
          @click="viewExam(exam.id)"
        >
          <template v-slot:extra>
            <div class="exam-details">
              <div class="detail-item">
                <div class="detail-label">Total Marks</div>
                <div class="detail-value">{{ exam.total_marks }}</div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Weight</div>
                <div class="detail-value">{{ exam.weight }}%</div>
              </div>
            </div>
            <div class="card-actions">
              <q-btn
                flat
                dense
                icon="visibility"
                color="primary"
                label="View"
                @click.stop="viewExam(exam.id)"
                size="sm"
              />
              <q-btn
                v-if="showActionButtons(exam, 'verify')"
                flat
                dense
                icon="check_circle"
                color="positive"
                label="Verify"
                @click.stop="verifyExam(exam)"
                size="sm"
              />
              <q-btn
                v-if="showActionButtons(exam, 'edit')"
                flat
                dense
                icon="edit"
                color="primary"
                label="Edit"
                @click.stop="editExam(exam.id)"
                size="sm"
              />
            </div>
          </template>
        </MobileListCard>
      </div>
      
      <div v-else class="empty-state">
        <q-icon name="quiz" size="64px" color="grey-5" />
        <div class="empty-text">No exams found</div>
      </div>
    </div>

    <!-- Desktop Table View -->
    <div class="desktop-table-view">
      <q-card class="widget-card">
        <q-card-section>
          <div class="row q-mb-md">
            <div class="col-12 col-md-4">
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
            <div class="col-12 col-md-4">
              <q-select
                v-model="filterClass"
                :options="classes"
                option-label="name"
                option-value="id"
                emit-value
                map-options
                outlined
                dense
                clearable
                label="Filter by Class"
                @update:model-value="onFilter"
                :loading="loadingClasses"
              />
            </div>
            <div class="col-12 col-md-4">
              <q-input
                v-model="searchQuery"
                outlined
                dense
                placeholder="Search exams..."
                @update:model-value="onSearch"
                clearable
              >
                <template v-slot:prepend>
                  <q-icon name="search" />
                </template>
              </q-input>
            </div>
          </div>

          <q-table
            :rows="exams"
            :columns="columns"
            row-key="id"
            :loading="loading"
            :pagination="pagination"
            @request="onRequest"
            flat
          >
          <template v-slot:body-cell-class_subject="props">
            <q-td :props="props">
              <div class="text-body2">{{ props.row.class_subject?.subject?.name || 'N/A' }}</div>
              <div class="text-caption text-grey-7">{{ props.row.class_subject?.class?.name || '' }}</div>
            </q-td>
          </template>

          <template v-slot:body-cell-term="props">
            <q-td :props="props">
              <div class="text-body2">{{ props.row.term?.name || 'N/A' }}</div>
              <div class="text-caption text-grey-7">{{ formatDate(props.row.assessment_date) }}</div>
            </q-td>
          </template>

          <template v-slot:body-cell-marks="props">
            <q-td :props="props">
              <div class="text-body2">Total: {{ props.row.total_marks }}</div>
              <div class="text-caption text-grey-7">Weight: {{ props.row.weight }}%</div>
            </q-td>
          </template>

          <template v-slot:body-cell-status="props">
            <q-td :props="props">
              <q-badge
                :color="props.row.is_finalized ? 'positive' : 'warning'"
                :label="props.row.is_finalized ? 'Verified' : 'Pending'"
              />
            </q-td>
          </template>

          <template v-slot:body-cell-teacher="props">
            <q-td :props="props">
              <div v-if="props.row.teacher?.user">
                {{ props.row.teacher.user.first_name }} {{ props.row.teacher.user.last_name }}
              </div>
              <div v-else class="text-grey-7">N/A</div>
            </q-td>
          </template>

          <template v-slot:body-cell-actions="props">
            <q-td :props="props">
              <q-btn
                flat
                dense
                icon="visibility"
                color="primary"
                @click="viewExam(props.row.id)"
                class="q-mr-xs"
              >
                <q-tooltip>View Exam</q-tooltip>
              </q-btn>
              <q-btn
                v-if="showActionButtons(props.row, 'verify')"
                flat
                dense
                icon="check_circle"
                color="positive"
                @click="verifyExam(props.row)"
                class="q-mr-xs"
              >
                <q-tooltip>Verify Exam</q-tooltip>
              </q-btn>
              <q-btn
                v-if="showActionButtons(props.row, 'edit')"
                flat
                dense
                icon="edit"
                color="primary"
                @click="editExam(props.row.id)"
                class="q-mr-xs"
              >
                <q-tooltip>Edit Exam</q-tooltip>
              </q-btn>
              <q-btn
                v-if="showActionButtons(props.row, 'delete')"
                flat
                dense
                icon="delete"
                color="negative"
                @click="deleteExam(props.row)"
              >
                <q-tooltip>Delete Exam</q-tooltip>
              </q-btn>
            </q-td>
          </template>
        </q-table>
      </q-card-section>
      </q-card>
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
const loadingTerms = ref(false);
const loadingClasses = ref(false);
const exams = ref([]);
const terms = ref([]);
const classes = ref([]);
const filterTerm = ref(null);
const filterClass = ref(null);
const searchQuery = ref('');

const pagination = ref({
  page: 1,
  rowsPerPage: 15,
  rowsNumber: 0,
});

const columns = [
  { name: 'name', label: 'Exam Name', field: 'name', align: 'left', sortable: true },
  { name: 'class_subject', label: 'Subject / Class', field: 'class_subject', align: 'left' },
  { name: 'term', label: 'Term / Date', field: 'term', align: 'left' },
  { name: 'marks', label: 'Marks / Weight', field: 'marks', align: 'left' },
  { name: 'status', label: 'Status', field: 'is_finalized', align: 'center' },
  { name: 'teacher', label: 'Teacher', field: 'teacher', align: 'left' },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'right' },
];

onMounted(async () => {
  // If user is a teacher but teacher relationship is missing, refresh user data
  if (authStore.isTeacher && !authStore.user?.teacher) {
    await authStore.fetchUser();
  }
  
  fetchTerms();
  fetchClasses();
  fetchExams();
});

function onRequest(props) {
  pagination.value = props.pagination;
  fetchExams();
}

function onFilter() {
  pagination.value.page = 1;
  fetchExams();
}

function onSearch() {
  pagination.value.page = 1;
  fetchExams();
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

async function fetchClasses() {
  loadingClasses.value = true;
  try {
    const response = await api.get('/classes', { params: { per_page: 100 } });
    if (response.data.success) {
      classes.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch classes:', error);
  } finally {
    loadingClasses.value = false;
  }
}

async function fetchExams() {
  loading.value = true;
  try {
    const params = {
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
    };

    if (filterTerm.value) {
      params.term_id = filterTerm.value;
    }

    if (filterClass.value) {
      params.class_id = filterClass.value;
    }

    if (searchQuery.value) {
      params.search = searchQuery.value;
    }

    const response = await api.get('/exams', { params });
    if (response.data.success) {
      exams.value = response.data.data || [];
      pagination.value.rowsNumber = response.data.meta?.total || 0;
    }
  } catch (error) {
    console.error('Failed to fetch exams:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch exams',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
}

function viewExam(id) {
  router.push(`/app/exams/${id}`);
}

function editExam(id) {
  router.push(`/app/exams/${id}`);
}

function showActionButtons(exam, action) {
  // Basic permission check
  const hasPermission = authStore.isTeacher || authStore.isSchoolAdmin || authStore.isSuperAdmin;
  if (!hasPermission) return false;
  
  // Cannot perform actions on finalized exams (except view)
  if (exam.is_finalized && action !== 'view') return false;
  
  // For verify action, must not be finalized
  if (action === 'verify' && exam.is_finalized) return false;
  
  // Admins can always see buttons (they'll get backend validation if needed)
  if (authStore.isSchoolAdmin || authStore.isSuperAdmin) {
    return true;
  }
  
  // For teachers, check ownership
  if (authStore.isTeacher) {
    const teacherId = authStore.user?.teacher?.id;
    return exam.teacher_id === teacherId;
  }
  
  return false;
}

function canEdit(exam) {
  // Cannot edit if exam is finalized
  if (exam.is_finalized) return false;
  
  // Admins can always edit (they'll get backend validation if needed)
  if (authStore.isSchoolAdmin || authStore.isSuperAdmin) {
    return true;
  }
  
  // For teachers, check ownership and term status
  if (authStore.isTeacher) {
    // Check if term allows editing (if term exists)
    if (exam.term) {
      const termAllowsEdit = exam.term.status === 'draft' || exam.term.status === 'active';
      if (!termAllowsEdit) return false;
    }
    
    // Teachers can only edit their own exams
    const teacherId = authStore.user?.teacher?.id;
    return exam.teacher_id === teacherId;
  }
  
  return false;
}

async function verifyExam(exam) {
  $q.dialog({
    title: 'Verify Exam',
    message: `Are you sure you want to verify the exam "${exam.name}"? Once verified, you won't be able to edit it.`,
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
      const response = await api.put(`/exams/${exam.id}`, {
        is_finalized: true,
      });
      if (response.data.success) {
        $q.notify({
          type: 'positive',
          message: 'Exam verified successfully',
          position: 'top',
        });
        // Refresh exams list
        await fetchExams();
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

async function deleteExam(exam) {
  $q.dialog({
    title: 'Confirm Delete',
    message: `Are you sure you want to delete the exam "${exam.name}"?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await api.delete(`/exams/${exam.id}`);
      $q.notify({
        type: 'positive',
        message: 'Exam deleted successfully',
        position: 'top',
      });
      fetchExams();
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to delete exam',
        position: 'top',
      });
    }
  });
}

function formatDate(date) {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
}

function getExamDescription(exam) {
  const parts = [];
  if (exam.class_subject?.class?.name) {
    parts.push(`Class: ${exam.class_subject.class.name}`);
  }
  if (exam.term?.name) {
    parts.push(`Term: ${exam.term.name}`);
  }
  if (exam.assessment_date) {
    parts.push(`Date: ${formatDate(exam.assessment_date)}`);
  }
  return parts.join(' • ') || 'No additional details';
}
</script>

<style lang="scss" scoped>
.exams-list-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.filters-card {
  margin-bottom: var(--spacing-md);
}

.filters-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-md);
  
  @media (min-width: 600px) {
    grid-template-columns: repeat(2, 1fr);
  }
  
  @media (min-width: 960px) {
    grid-template-columns: repeat(3, 1fr);
  }
}

.filter-item {
  width: 100%;
}

.mobile-list-view {
  display: block;
  
  @media (min-width: 960px) {
    display: none;
  }
}

.desktop-table-view {
  display: none;
  
  @media (min-width: 960px) {
    display: block;
  }
}

.loading-cards {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.cards-container {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.exam-details {
  display: flex;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-sm);
  
  .detail-item {
    .detail-label {
      font-size: var(--font-size-xs);
      color: var(--text-secondary);
      margin-bottom: var(--spacing-xs);
    }
    
    .detail-value {
      font-size: var(--font-size-base);
      font-weight: 600;
      color: var(--text-primary);
    }
  }
}

.card-actions {
  display: flex;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
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
  font-size: var(--font-size-base);
  color: var(--text-secondary);
  margin-top: var(--spacing-md);
}

.mobile-btn {
  @media (max-width: 599px) {
    min-width: 0;
    padding: var(--spacing-sm);
  }
}

.widget-card {
  border-radius: var(--radius-xl);
  border: 1px solid var(--border-light);
  backdrop-filter: blur(10px);
  background: var(--bg-card);
  box-shadow: var(--shadow-sm);
  
  @media (min-width: 768px) {
    box-shadow: var(--shadow-md);
  }
}
</style>
