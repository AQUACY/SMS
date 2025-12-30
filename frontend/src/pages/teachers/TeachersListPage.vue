<template>
  <q-page class="teachers-list-page">
    <MobilePageHeader
      title="Teachers"
      subtitle="Manage all teachers"
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
          :label="$q.screen.gt.xs ? 'Add Teacher' : ''"
          icon="add"
          unelevated
          to="/app/teachers/create"
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
      
      <div v-else-if="teachers.length > 0" class="cards-container">
        <MobileListCard
          v-for="teacher in teachers"
          :key="teacher.id"
          :title="teacher.name"
          :subtitle="teacher.staff_number ? `Staff: ${teacher.staff_number}` : 'No Staff Number'"
          :description="getTeacherDescription(teacher)"
          icon="person_outline"
          :badge="teacher.status"
          :badge-color="teacher.user?.is_active ? 'positive' : 'negative'"
          icon-bg="rgba(33, 150, 243, 0.1)"
          @click="viewTeacher(teacher.id)"
        >
          <template v-slot:extra>
            <div class="card-actions">
              <q-btn
                flat
                dense
                icon="visibility"
                color="primary"
                label="View"
                @click.stop="viewTeacher(teacher.id)"
                size="sm"
              />
              <q-btn
                v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
                flat
                dense
                icon="edit"
                color="primary"
                label="Edit"
                @click.stop="editTeacher(teacher.id)"
                size="sm"
              />
            </div>
          </template>
        </MobileListCard>
      </div>
      
      <div v-else class="empty-state">
        <q-icon name="person_outline" size="64px" color="grey-5" />
        <div class="empty-text">No teachers found</div>
      </div>
    </div>

    <!-- Desktop Table View -->
    <div class="desktop-table-view">
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
    </div>

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
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileListCard from 'src/components/mobile/MobileListCard.vue';

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

function getTeacherDescription(teacher) {
  const parts = [];
  if (teacher.email) parts.push(teacher.email);
  if (teacher.qualification && teacher.qualification !== 'N/A') parts.push(teacher.qualification);
  if (teacher.specialization && teacher.specialization !== 'N/A') parts.push(teacher.specialization);
  return parts.join(' • ') || 'No additional details';
}
</script>

<style lang="scss" scoped>
.teachers-list-page {
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
