<template>
  <q-page class="students-list-page">
    <MobilePageHeader
      title="Students"
      subtitle="Manage all students"
    >
      <template v-slot:actions>
        <q-btn
          v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
          color="secondary"
          :label="$q.screen.gt.xs ? 'Import Excel' : ''"
          icon="upload"
          unelevated
          @click="showImportDialog = true"
          class="mobile-btn"
        />
        <q-btn
          v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
          color="primary"
          :label="$q.screen.gt.xs ? 'Add Student' : ''"
          icon="add"
          unelevated
          to="/app/students/create"
          class="mobile-btn"
        />
      </template>
    </MobilePageHeader>

    <!-- Mobile Card View -->
    <div class="mobile-list-view">
      <div v-if="loading" class="loading-cards">
        <q-card v-for="i in 3" :key="i" class="mobile-list-card">
          <q-card-section>
            <q-skeleton type="rect" height="80px" />
          </q-card-section>
        </q-card>
      </div>
      
      <div v-else-if="students.length > 0" class="cards-container">
        <MobileListCard
          v-for="student in students"
          :key="student.id"
          :title="student.full_name || 'N/A'"
          :subtitle="`ID: ${student.student_number || 'N/A'}`"
          :description="student.class ? `Class: ${student.class}` : 'Not enrolled'"
          icon="person"
          :badge="student.status"
          :badge-color="student.status === 'Active' ? 'positive' : 'negative'"
          icon-bg="rgba(156, 39, 176, 0.1)"
          @click="viewStudent(student.id)"
        >
          <template v-slot:extra>
            <div class="card-actions">
              <q-btn
                flat
                dense
                icon="visibility"
                color="primary"
                label="View"
                @click.stop="viewStudent(student.id)"
                size="sm"
              />
              <q-btn
                v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
                flat
                dense
                icon="edit"
                color="primary"
                label="Edit"
                @click.stop="editStudent(student.id)"
                size="sm"
              />
            </div>
          </template>
        </MobileListCard>
      </div>
      
      <div v-else class="empty-state">
        <q-icon name="people_outline" size="64px" color="grey-5" />
        <div class="empty-text">No students found</div>
      </div>
    </div>

    <!-- Desktop Table View -->
    <div class="desktop-table-view">
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
    </div>

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
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileListCard from 'src/components/mobile/MobileListCard.vue';
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
.students-list-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
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

