<template>
  <q-page class="q-pa-lg">
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">Academic Years</div>
        <div class="text-body2 text-grey-7">Manage academic years</div>
      </div>
      <q-btn
        v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
        color="primary"
        label="Add Academic Year"
        icon="add"
        unelevated
        to="/app/academic-years/create"
      />
    </div>

    <q-card class="widget-card q-pa-md">
      <q-card-section>
        <div class="row q-mb-md">
          <div class="col-12 col-md-6">
            <q-toggle
              v-model="filterActive"
              label="Show Active Only"
              @update:model-value="onFilter"
            />
          </div>
        </div>

        <q-table
          :rows="academicYears"
          :columns="columns"
          row-key="id"
          :loading="loading"
          :pagination="pagination"
          @request="onRequest"
          flat
        >
          <template v-slot:body-cell-dates="props">
            <q-td :props="props">
              <div class="text-body2">{{ formatDate(props.row.start_date) }}</div>
              <div class="text-caption text-grey-7">to {{ formatDate(props.row.end_date) }}</div>
            </q-td>
          </template>

          <template v-slot:body-cell-status="props">
            <q-td :props="props">
              <q-badge
                :color="props.value ? 'positive' : 'grey'"
                :label="props.value ? 'Active' : 'Inactive'"
              />
            </q-td>
          </template>

          <template v-slot:body-cell-terms="props">
            <q-td :props="props">
              <q-badge color="primary" :label="`${props.value} terms`" />
            </q-td>
          </template>

          <template v-slot:body-cell-actions="props">
            <q-td :props="props">
              <q-btn
                flat
                dense
                icon="visibility"
                color="primary"
                @click="viewAcademicYear(props.row.id)"
                class="q-mr-xs"
              />
              <q-btn
                v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
                flat
                dense
                icon="edit"
                color="primary"
                @click="editAcademicYear(props.row.id)"
                class="q-mr-xs"
              />
              <q-btn
                v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin && !props.row.is_active"
                flat
                dense
                icon="play_arrow"
                color="positive"
                @click="activateAcademicYear(props.row.id)"
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
const academicYears = ref([]);
const filterActive = ref(false);
const pagination = ref({
  page: 1,
  rowsPerPage: 15,
  rowsNumber: 0,
});

const columns = [
  {
    name: 'name',
    label: 'Academic Year',
    field: 'name',
    align: 'left',
    sortable: true,
  },
  {
    name: 'dates',
    label: 'Dates',
    field: 'start_date',
    align: 'left',
  },
  {
    name: 'terms',
    label: 'Terms',
    field: (row) => row.terms?.length || 0,
    align: 'center',
  },
  {
    name: 'status',
    label: 'Status',
    field: 'is_active',
    align: 'center',
  },
  {
    name: 'actions',
    label: 'Actions',
    field: 'actions',
    align: 'center',
  },
];

const formatDate = (date) => {
  if (!date) return 'N/A';
  const d = new Date(date);
  return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
};

const fetchAcademicYears = async () => {
  loading.value = true;
  try {
    const params = {
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
    };

    if (filterActive.value) {
      params.is_active = true;
    }

    const response = await api.get('/academic-years', { params });

    if (response.data.success) {
      academicYears.value = response.data.data || [];
      if (response.data.meta) {
        pagination.value.rowsNumber = response.data.meta.total || 0;
        pagination.value.page = response.data.meta.current_page || 1;
      }
    }
  } catch (error) {
    console.error('Failed to fetch academic years:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to load academic years. Please try again.',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
};

const onRequest = (props) => {
  pagination.value.page = props.pagination.page;
  pagination.value.rowsPerPage = props.pagination.rowsPerPage;
  fetchAcademicYears();
};

const onFilter = () => {
  pagination.value.page = 1;
  fetchAcademicYears();
};

const viewAcademicYear = (id) => {
  router.push(`/app/academic-years/${id}`);
};

const editAcademicYear = (id) => {
  router.push(`/app/academic-years/${id}/edit`);
};

const activateAcademicYear = async (id) => {
  try {
    const response = await api.post(`/academic-years/${id}/activate`);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Academic year activated successfully',
        position: 'top',
      });
      fetchAcademicYears();
    }
  } catch (error) {
    console.error('Failed to activate academic year:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to activate academic year. Please try again.',
      position: 'top',
    });
  }
};

onMounted(() => {
  fetchAcademicYears();
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
