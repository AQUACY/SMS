<template>
  <q-page class="q-pa-lg">
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">Subjects</div>
        <div class="text-body2 text-grey-7">Manage all subjects</div>
      </div>
      <div>
        <q-btn
          v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
          color="secondary"
          label="Import Excel"
          icon="upload"
          unelevated
          @click="showImportDialog = true"
          class="q-mr-sm"
        />
        <q-btn
          v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
          color="primary"
          label="Add Subject"
          icon="add"
          unelevated
          to="/app/subjects/create"
        />
      </div>
    </div>

    <q-card class="widget-card">
      <q-card-section>
        <div class="row q-mb-md">
          <div class="col-12 col-md-6">
            <q-input
              v-model="searchQuery"
              placeholder="Search subjects..."
              outlined
              dense
              clearable
              @update:model-value="onSearch"
            >
              <template v-slot:prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>
          <div class="col-12 col-md-6">
            <q-select
              v-model="filterCore"
              :options="coreOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              outlined
              dense
              clearable
              label="Filter by Type"
              @update:model-value="onFilter"
            />
          </div>
        </div>

        <q-table
          :rows="subjects"
          :columns="columns"
          row-key="id"
          :loading="loading"
          :pagination="pagination"
          @request="onRequest"
          flat
        >
          <template v-slot:body-cell-is_core="props">
            <q-td :props="props">
              <q-badge
                :color="props.value ? 'primary' : 'grey'"
                :label="props.value ? 'Core' : 'Elective'"
              />
            </q-td>
          </template>

          <template v-slot:body-cell-actions="props">
            <q-td :props="props">
              <q-btn
                flat
                dense
                icon="visibility"
                color="primary"
                @click="viewSubject(props.row.id)"
                class="q-mr-xs"
              />
              <q-btn
                v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
                flat
                dense
                icon="edit"
                color="primary"
                @click="editSubject(props.row.id)"
              />
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <!-- Excel Import Dialog -->
    <ExcelImportDialog
      v-model="showImportDialog"
      type="subjects"
      title="Import Subjects from Excel"
      description="Download the template, fill it with subject data, and upload it here to bulk import subjects."
      @imported="handleImportSuccess"
    />
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'src/stores/auth';
import ExcelImportDialog from 'src/components/ExcelImportDialog.vue';
import api from 'src/services/api';

const router = useRouter();
const $q = useQuasar();
const authStore = useAuthStore();

const loading = ref(false);
const subjects = ref([]);
const showImportDialog = ref(false);
const searchQuery = ref('');
const filterCore = ref(null);
const pagination = ref({
  page: 1,
  rowsPerPage: 15,
  rowsNumber: 0,
});

const coreOptions = [
  { label: 'Core Subjects', value: true },
  { label: 'Elective Subjects', value: false },
];

const columns = [
  {
    name: 'name',
    label: 'Subject Name',
    field: 'name',
    align: 'left',
    sortable: true,
  },
  {
    name: 'code',
    label: 'Code',
    field: 'code',
    align: 'left',
  },
  {
    name: 'is_core',
    label: 'Type',
    field: 'is_core',
    align: 'center',
  },
  {
    name: 'description',
    label: 'Description',
    field: 'description',
    align: 'left',
    style: 'max-width: 200px',
    classes: 'ellipsis',
  },
  {
    name: 'actions',
    label: 'Actions',
    field: 'actions',
    align: 'center',
  },
];

const fetchSubjects = async () => {
  loading.value = true;
  try {
    const params = {
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
    };

    if (searchQuery.value) {
      params.search = searchQuery.value;
    }

    if (filterCore.value !== null) {
      params.is_core = filterCore.value;
    }

    const response = await api.get('/subjects', { params });

    if (response.data.success) {
      // BaseApiController paginated() returns data as array and meta for pagination
      subjects.value = response.data.data || [];
      if (response.data.meta) {
        pagination.value.rowsNumber = response.data.meta.total || 0;
        pagination.value.page = response.data.meta.current_page || 1;
      }
    }
  } catch (error) {
    console.error('Failed to fetch subjects:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to load subjects. Please try again.',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
};

const onRequest = (props) => {
  pagination.value.page = props.pagination.page;
  pagination.value.rowsPerPage = props.pagination.rowsPerPage;
  fetchSubjects();
};

const onSearch = () => {
  pagination.value.page = 1;
  fetchSubjects();
};

const onFilter = () => {
  pagination.value.page = 1;
  fetchSubjects();
};

const viewSubject = (id) => {
  router.push(`/app/subjects/${id}`);
};

const editSubject = (id) => {
  router.push(`/app/subjects/${id}/edit`);
};

const handleImportSuccess = () => {
  fetchSubjects();
};

onMounted(() => {
  fetchSubjects();
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
