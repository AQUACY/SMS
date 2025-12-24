<template>
  <q-page class="q-pa-lg">
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">Exams</div>
        <div class="text-body2 text-grey-7">View and manage all exams</div>
      </div>
      <q-btn
        v-if="authStore.isTeacher || authStore.isSchoolAdmin || authStore.isSuperAdmin"
        color="primary"
        label="Create Exam"
        icon="add"
        unelevated
        to="/app/exams/create"
      />
    </div>

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
              />
              <q-btn
                v-if="(authStore.isSchoolAdmin || authStore.isSuperAdmin) && canEdit(props.row)"
                flat
                dense
                icon="edit"
                color="primary"
                @click="editExam(props.row.id)"
                class="q-mr-xs"
              />
              <q-btn
                v-if="(authStore.isSchoolAdmin || authStore.isSuperAdmin) && canEdit(props.row)"
                flat
                dense
                icon="delete"
                color="negative"
                @click="deleteExam(props.row)"
              />
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'src/stores/auth';
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
  { name: 'teacher', label: 'Teacher', field: 'teacher', align: 'left' },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'right' },
];

onMounted(() => {
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
  // For now, navigate to detail page - edit can be added later
  router.push(`/app/exams/${id}`);
}

function canEdit(exam) {
  // Can only edit if term allows new assessments
  return exam.term?.status === 'draft' || exam.term?.status === 'active';
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
</script>

<style lang="scss" scoped>
.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}
</style>
