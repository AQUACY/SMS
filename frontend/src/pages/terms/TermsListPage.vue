<template>
  <q-page class="q-pa-lg">
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">Terms</div>
        <div class="text-body2 text-grey-7">Manage academic terms</div>
      </div>
      <q-btn
        v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
        color="primary"
        label="Add Term"
        icon="add"
        unelevated
        to="/app/terms/create"
      />
    </div>

    <q-card class="widget-card q-pa-md">
      <q-card-section>
        <div class="row q-mb-md">
          <div class="col-12 col-md-6">
            <q-select
              v-model="filterAcademicYear"
              :options="academicYears"
              option-label="name"
              option-value="id"
              emit-value
              map-options
              outlined
              dense
              clearable
              label="Filter by Academic Year"
              @update:model-value="onFilter"
              :loading="loadingAcademicYears"
            />
          </div>
          <div class="col-12 col-md-6">
            <q-select
              v-model="filterStatus"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              outlined
              dense
              clearable
              label="Filter by Status"
              @update:model-value="onFilter"
            />
          </div>
        </div>

        <q-table
          :rows="terms"
          :columns="columns"
          row-key="id"
          :loading="loading"
          :pagination="pagination"
          @request="onRequest"
          flat
        >
          <template v-slot:body-cell-status="props">
            <q-td :props="props">
              <q-badge
                :color="getStatusColor(props.value)"
                :label="formatStatus(props.value)"
              />
            </q-td>
          </template>

          <template v-slot:body-cell-dates="props">
            <q-td :props="props">
              <div class="text-body2">{{ formatDate(props.row.start_date) }}</div>
              <div class="text-caption text-grey-7">to {{ formatDate(props.row.end_date) }}</div>
            </q-td>
          </template>

          <template v-slot:body-cell-actions="props">
            <q-td :props="props">
              <q-btn
                flat
                dense
                icon="visibility"
                color="primary"
                @click="viewTerm(props.row.id)"
                class="q-mr-xs"
              />
              <q-btn
                v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin && canEdit(props.row)"
                flat
                dense
                icon="edit"
                color="primary"
                @click="editTerm(props.row.id)"
                class="q-mr-xs"
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
const loadingAcademicYears = ref(false);
const terms = ref([]);
const academicYears = ref([]);
const filterAcademicYear = ref(null);
const filterStatus = ref(null);
const pagination = ref({
  page: 1,
  rowsPerPage: 15,
  rowsNumber: 0,
});

const statusOptions = [
  { label: 'Draft', value: 'draft' },
  { label: 'Active', value: 'active' },
  { label: 'Closing', value: 'closing' },
  { label: 'Closed', value: 'closed' },
  { label: 'Archived', value: 'archived' },
];

const columns = [
  {
    name: 'name',
    label: 'Term Name',
    field: 'name',
    align: 'left',
    sortable: true,
  },
  {
    name: 'term_number',
    label: 'Term #',
    field: 'term_number',
    align: 'center',
    sortable: true,
  },
  {
    name: 'academic_year',
    label: 'Academic Year',
    field: (row) => row.academic_year?.name || 'N/A',
    align: 'left',
  },
  {
    name: 'dates',
    label: 'Dates',
    field: 'start_date',
    align: 'left',
  },
  {
    name: 'status',
    label: 'Status',
    field: 'status',
    align: 'center',
  },
  {
    name: 'actions',
    label: 'Actions',
    field: 'actions',
    align: 'center',
  },
];

const getStatusColor = (status) => {
  const colors = {
    draft: 'grey',
    active: 'positive',
    closing: 'warning',
    closed: 'negative',
    archived: 'dark',
  };
  return colors[status] || 'grey';
};

const formatStatus = (status) => {
  return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown';
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  const d = new Date(date);
  return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
};

const canEdit = (term) => {
  return !['closed', 'archived'].includes(term.status);
};

const fetchAcademicYears = async () => {
  loadingAcademicYears.value = true;
  try {
    const response = await api.get('/academic-years', {
      params: { per_page: 100 },
    });
    if (response.data.success) {
      academicYears.value = (response.data.data || []).map((ay) => ({
        id: ay.id,
        name: ay.name,
      }));
    }
  } catch (error) {
    console.error('Failed to fetch academic years:', error);
  } finally {
    loadingAcademicYears.value = false;
  }
};

const fetchTerms = async () => {
  loading.value = true;
  try {
    const params = {
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
    };

    if (filterAcademicYear.value) {
      params.academic_year_id = filterAcademicYear.value;
    }

    if (filterStatus.value) {
      params.status = filterStatus.value;
    }

    const response = await api.get('/terms', { params });

    if (response.data.success) {
      terms.value = response.data.data || [];
      if (response.data.meta) {
        pagination.value.rowsNumber = response.data.meta.total || 0;
        pagination.value.page = response.data.meta.current_page || 1;
      }
    }
  } catch (error) {
    console.error('Failed to fetch terms:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to load terms. Please try again.',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
};

const onRequest = (props) => {
  pagination.value.page = props.pagination.page;
  pagination.value.rowsPerPage = props.pagination.rowsPerPage;
  fetchTerms();
};

const onFilter = () => {
  pagination.value.page = 1;
  fetchTerms();
};

const viewTerm = (id) => {
  router.push(`/app/terms/${id}`);
};

const editTerm = (id) => {
  router.push(`/app/terms/${id}/edit`);
};

onMounted(() => {
  fetchAcademicYears();
  fetchTerms();
});
</script>

<style lang="scss" scoped>
.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}
</style>
