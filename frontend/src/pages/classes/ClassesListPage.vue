<template>
  <q-page class="q-pa-lg">
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">Classes</div>
        <div class="text-body2 text-grey-7">Manage all classes</div>
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
          label="Add Class"
          icon="add"
          unelevated
          to="/app/classes/create"
        />
      </div>
    </div>

    <q-card class="widget-card">
      <q-card-section>
        <div class="text-h6 q-mb-md">Classes List</div>
        <q-table
          :rows="classes"
          :columns="columns"
          row-key="id"
          :loading="loading"
          :pagination="pagination"
          @request="onRequest"
          flat
        >
          <template v-slot:body-cell-class_teacher="props">
            <q-td :props="props">
              <span v-if="props.value">{{ props.value }}</span>
              <span v-else class="text-grey-6">Not assigned</span>
            </q-td>
          </template>

          <template v-slot:body-cell-status="props">
            <q-td :props="props">
              <q-badge
                :color="props.value ? 'positive' : 'negative'"
                :label="props.value ? 'Active' : 'Inactive'"
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
                @click="viewClass(props.row.id)"
                class="q-mr-xs"
              />
              <q-btn
                v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
                flat
                dense
                icon="edit"
                color="primary"
                @click="editClass(props.row.id)"
              />
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <!-- Excel Import Dialog -->
    <ExcelImportDialog
      v-model="showImportDialog"
      type="classes"
      title="Import Classes from Excel"
      description="Download the template, fill it with class data, and upload it here to bulk import classes."
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
const classes = ref([]);
const showImportDialog = ref(false);
const pagination = ref({
  page: 1,
  rowsPerPage: 15,
  rowsNumber: 0,
});

const columns = [
  {
    name: 'name',
    label: 'Class Name',
    field: 'name',
    align: 'left',
    sortable: true,
  },
  {
    name: 'level',
    label: 'Level',
    field: 'level',
    align: 'left',
    sortable: true,
  },
  {
    name: 'section',
    label: 'Section',
    field: 'section',
    align: 'left',
  },
  {
    name: 'capacity',
    label: 'Capacity',
    field: 'capacity',
    align: 'center',
    sortable: true,
  },
  {
    name: 'class_teacher',
    label: 'Class Teacher',
    field: (row) => {
      if (row.class_teacher && row.class_teacher.user) {
        return `${row.class_teacher.user.first_name} ${row.class_teacher.user.last_name}`;
      }
      return null;
    },
    align: 'left',
  },
  {
    name: 'academic_year',
    label: 'Academic Year',
    field: (row) => row.academic_year?.name || 'N/A',
    align: 'left',
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

const fetchClasses = async () => {
  loading.value = true;
  try {
    const response = await api.get('/classes', {
      params: {
        page: pagination.value.page,
        per_page: pagination.value.rowsPerPage,
      },
    });

    if (response.data.success) {
      // BaseApiController paginated() returns data as array and meta for pagination
      classes.value = response.data.data || [];
      if (response.data.meta) {
        pagination.value.rowsNumber = response.data.meta.total || 0;
        pagination.value.page = response.data.meta.current_page || 1;
      }
    }
  } catch (error) {
    console.error('Failed to fetch classes:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to load classes. Please try again.',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
};

const onRequest = (props) => {
  pagination.value.page = props.pagination.page;
  pagination.value.rowsPerPage = props.pagination.rowsPerPage;
  fetchClasses();
};

const viewClass = (id) => {
  router.push(`/app/classes/${id}`);
};

const editClass = (id) => {
  router.push(`/app/classes/${id}/edit`);
};

const handleImportSuccess = () => {
  fetchClasses();
};

onMounted(() => {
  fetchClasses();
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
