<template>
  <q-page class="q-pa-lg">
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">Teachers</div>
        <div class="text-body2 text-grey-7">Manage all teachers</div>
      </div>
      <div>
        <q-btn
          color="secondary"
          label="Import Excel"
          icon="upload"
          unelevated
          @click="showImportDialog = true"
          v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
          class="q-mr-sm"
        />
        <q-btn
          color="primary"
          label="Add Teacher"
          icon="add"
          unelevated
          to="/app/teachers/create"
          v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
        />
      </div>
    </div>

    <q-card class="widget-card">
      <q-card-section>
        <div class="text-h6 q-mb-md">Teachers List</div>
        <q-table
          :rows="teachers"
          :columns="columns"
          row-key="id"
          :loading="loading"
          flat
          v-model:pagination="pagination"
          @request="onRequest"
        >
          <template v-slot:body-cell-name="props">
            <q-td :props="props">
              <div class="text-weight-medium">{{ props.value }}</div>
              <div class="text-caption text-grey-7">{{ props.row.staff_number || 'No Staff Number' }}</div>
            </q-td>
          </template>
          <template v-slot:body-cell-status="props">
            <q-td :props="props">
              <q-badge :color="props.row.user?.is_active ? 'green' : 'red'" :label="props.value" />
            </q-td>
          </template>
          <template v-slot:body-cell-actions="props">
            <q-td :props="props">
              <q-btn
                flat
                dense
                icon="visibility"
                color="primary"
                @click="viewTeacher(props.row.id)"
                class="q-mr-xs"
              />
              <q-btn
                v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
                flat
                dense
                icon="edit"
                color="primary"
                @click="editTeacher(props.row.id)"
              />
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <!-- Excel Import Dialog -->
    <ExcelImportDialog
      v-model="showImportDialog"
      type="teachers"
      title="Import Teachers from Excel"
      description="Download the template, fill it with teacher data, and upload it here to bulk import teachers."
      @imported="handleImportSuccess"
    />
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from 'src/stores/auth';
import { useQuasar } from 'quasar';
import api from 'src/services/api';
import ExcelImportDialog from 'src/components/ExcelImportDialog.vue';

const router = useRouter();
const authStore = useAuthStore();
const $q = useQuasar();

const loading = ref(false);
const teachers = ref([]);
const showImportDialog = ref(false);

const pagination = ref({
  sortBy: 'created_at',
  descending: true,
  page: 1,
  rowsPerPage: 15,
  rowsNumber: 0,
});

const columns = [
  { name: 'name', label: 'Name', field: 'name', align: 'left' },
  { name: 'email', label: 'Email', field: 'email', align: 'left' },
  { name: 'qualification', label: 'Qualification', field: 'qualification', align: 'left' },
  { name: 'specialization', label: 'Specialization', field: 'specialization', align: 'left' },
  { name: 'status', label: 'Status', field: 'status', align: 'left' },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'right' },
];

onMounted(() => {
  fetchTeachers();
});

async function fetchTeachers(page = 1, perPage = 15) {
  loading.value = true;
  try {
    const response = await api.get('/teachers', {
      params: {
        page: page,
        per_page: perPage,
      },
    });
    if (response.data.success) {
      const teachersData = response.data.data?.data || response.data.data || [];
      teachers.value = teachersData.map(teacher => ({
        id: teacher.id,
        name: getTeacherName(teacher),
        email: teacher.user?.email || 'N/A',
        staff_number: teacher.staff_number,
        qualification: teacher.qualification || 'N/A',
        specialization: teacher.specialization || 'N/A',
        status: teacher.user?.is_active ? 'Active' : 'Inactive',
        user: teacher.user,
      }));
      
      // Handle pagination - check if it's a paginated response
      if (response.data.data?.current_page !== undefined) {
        pagination.value.page = response.data.data.current_page;
        pagination.value.rowsPerPage = response.data.data.per_page;
        pagination.value.rowsNumber = response.data.data.total;
      }
    }
  } catch (error) {
    console.error('Failed to fetch teachers:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch teachers.',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
}

function onRequest(props) {
  const { page, rowsPerPage } = props.pagination;
  fetchTeachers(page, rowsPerPage);
}

function getTeacherName(teacher) {
  if (!teacher || !teacher.user) return 'Unknown';
  const firstName = teacher.user.first_name || '';
  const lastName = teacher.user.last_name || '';
  return `${firstName} ${lastName}`.trim() || 'Unknown';
}

function viewTeacher(id) {
  router.push(`/app/teachers/${id}`);
}

function editTeacher(id) {
  router.push(`/app/teachers/${id}/edit`);
}

function handleImportSuccess() {
  fetchTeachers(pagination.value.page, pagination.value.rowsPerPage);
  showImportDialog.value = false;
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
