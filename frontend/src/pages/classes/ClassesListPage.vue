<template>
  <q-page class="classes-list-page">
    <MobilePageHeader
      title="Classes"
      subtitle="Manage all classes"
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
          :label="$q.screen.gt.xs ? 'Add Class' : ''"
          icon="add"
          unelevated
          to="/app/classes/create"
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
      
      <div v-else-if="classes.length > 0" class="cards-container">
        <MobileListCard
          v-for="classItem in classes"
          :key="classItem.id"
          :title="classItem.name || 'N/A'"
          :subtitle="`Level: ${classItem.level || 'N/A'}`"
          :description="getClassDescription(classItem)"
          icon="class"
          :badge="classItem.is_active ? 'Active' : 'Inactive'"
          :badge-color="classItem.is_active ? 'positive' : 'negative'"
          icon-bg="rgba(33, 150, 243, 0.1)"
          @click="viewClass(classItem.id)"
        >
          <template v-slot:extra>
            <div class="card-actions">
              <q-btn
                flat
                dense
                icon="visibility"
                color="primary"
                label="View"
                @click.stop="viewClass(classItem.id)"
                size="sm"
              />
              <q-btn
                v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
                flat
                dense
                icon="edit"
                color="primary"
                label="Edit"
                @click.stop="editClass(classItem.id)"
                size="sm"
              />
            </div>
          </template>
        </MobileListCard>
      </div>
      
      <div v-else class="empty-state">
        <q-icon name="class" size="64px" color="grey-5" />
        <div class="empty-text">No classes found</div>
      </div>
    </div>

    <!-- Desktop Table View -->
    <div class="desktop-table-view">
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
    </div>

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
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileListCard from 'src/components/mobile/MobileListCard.vue';
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

function getClassDescription(classItem) {
  const parts = [];
  if (classItem.section) parts.push(`Section: ${classItem.section}`);
  if (classItem.capacity) parts.push(`Capacity: ${classItem.capacity}`);
  if (classItem.class_teacher?.user) {
    parts.push(`Teacher: ${classItem.class_teacher.user.first_name} ${classItem.class_teacher.user.last_name}`);
  }
  return parts.join(' • ') || 'No additional details';
}

onMounted(() => {
  fetchClasses();
});
</script>

<style lang="scss" scoped>
.classes-list-page {
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
