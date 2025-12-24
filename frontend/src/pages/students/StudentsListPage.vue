<template>
  <q-page class="q-pa-lg">
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">Students</div>
        <div class="text-body2 text-grey-7">Manage all students</div>
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
          label="Add Student"
          icon="add"
          unelevated
          to="/app/students/create"
          v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
        />
      </div>
    </div>

    <q-card class="widget-card">
      <q-card-section>
        <div class="text-h6 q-mb-md">Students List</div>
        <q-table
          :rows="students"
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
                :color="props.value === 'Active' ? 'positive' : 'negative'"
                :label="props.value"
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
                @click="viewStudent(props.row.id)"
                class="q-mr-xs"
              />
              <q-btn
                v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
                flat
                dense
                icon="edit"
                color="primary"
                @click="editStudent(props.row.id)"
              />
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <!-- Excel Import Dialog -->
    <ExcelImportDialog
      v-model="showImportDialog"
      type="students"
      title="Import Students from Excel"
      description="Download the template, fill it with student data, and upload it here to bulk import students."
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
const students = ref([]);
const showImportDialog = ref(false);

const pagination = ref({
  page: 1,
  rowsPerPage: 15,
  rowsNumber: 0,
});

const columns = [
  { name: 'student_number', label: 'Student ID', field: 'student_number', align: 'left', sortable: true },
  { name: 'full_name', label: 'Name', field: 'full_name', align: 'left', sortable: true },
  { name: 'class', label: 'Class', field: 'class', align: 'left' },
  { name: 'status', label: 'Status', field: 'status', align: 'left' },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'right' },
];

onMounted(() => {
  fetchStudents();
});

function onRequest(props) {
  pagination.value = props.pagination;
  fetchStudents();
}

async function fetchStudents() {
  loading.value = true;
  try {
    const params = {
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
    };

    const response = await api.get('/students', { params });
    if (response.data.success) {
      students.value = response.data.data;
      pagination.value.rowsNumber = response.data.meta?.total || 0;
    }
  } catch (error) {
    console.error('Failed to fetch students:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch students',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
}

function viewStudent(id) {
  router.push(`/app/students/${id}`);
}

function editStudent(id) {
  router.push(`/app/students/${id}/edit`);
}

function handleImportSuccess(data) {
  // Refresh students list after successful import
  fetchStudents();
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

