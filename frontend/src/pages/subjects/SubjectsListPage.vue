<template>
  <q-page class="subjects-list-page">
    <MobilePageHeader
      title="Subjects"
      subtitle="Manage all subjects"
    >
      <template v-slot:actions>
        <q-btn
          v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
          color="secondary"
          :label="$q.screen.gt.xs ? 'Import Excel' : ''"
          icon="upload"
          unelevated
          @click="showImportDialog = true"
          class="mobile-btn q-mr-xs"
        />
        <q-btn
          v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
          color="primary"
          :label="$q.screen.gt.xs ? 'Add Subject' : ''"
          icon="add"
          unelevated
          to="/app/subjects/create"
          class="mobile-btn"
        />
      </template>
    </MobilePageHeader>

    <!-- Filters -->
    <MobileCard variant="default" padding="md" class="filters-card">
      <div class="filters-grid">
        <q-input
          v-model="searchQuery"
          placeholder="Search subjects..."
          outlined
          dense
          clearable
          @update:model-value="onSearch"
          class="filter-item"
        >
          <template v-slot:prepend>
            <q-icon name="search" />
          </template>
        </q-input>
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
          class="filter-item"
        />
      </div>
    </MobileCard>

    <!-- Mobile Card View -->
    <div class="mobile-list-view">
      <div v-if="loading" class="loading-cards">
        <q-card v-for="i in 3" :key="i" class="mobile-list-card">
          <q-card-section>
            <q-skeleton type="rect" height="80px" />
          </q-card-section>
        </q-card>
      </div>
      
      <div v-else-if="subjects.length > 0" class="cards-container">
        <MobileListCard
          v-for="subject in subjects"
          :key="subject.id"
          :title="subject.name"
          :subtitle="subject.code || 'No Code'"
          :description="getSubjectDescription(subject)"
          icon="menu_book"
          :badge="subject.is_core ? 'Core' : 'Elective'"
          :badge-color="subject.is_core ? 'primary' : 'grey'"
          icon-bg="rgba(33, 150, 243, 0.1)"
          @click="viewSubject(subject.id)"
        >
          <template v-slot:extra>
            <div class="card-actions">
              <q-btn
                flat
                dense
                icon="visibility"
                color="primary"
                label="View"
                @click.stop="viewSubject(subject.id)"
                size="sm"
              />
              <q-btn
                v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
                flat
                dense
                icon="edit"
                color="primary"
                label="Edit"
                @click.stop="editSubject(subject.id)"
                size="sm"
              />
            </div>
          </template>
        </MobileListCard>
      </div>
      
      <div v-else class="empty-state">
        <q-icon name="menu_book" size="64px" color="grey-5" />
        <div class="empty-text">No subjects found</div>
      </div>
    </div>

    <!-- Desktop Table View -->
    <div class="desktop-table-view">
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
    </div>

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
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import MobileListCard from 'src/components/mobile/MobileListCard.vue';
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

function getSubjectDescription(subject) {
  const parts = [];
  if (subject.description) parts.push(subject.description);
  if (subject.is_core) parts.push('Core Subject');
  else parts.push('Elective Subject');
  return parts.join(' • ') || 'No description';
}

onMounted(() => {
  fetchSubjects();
});
</script>

<style lang="scss" scoped>
.subjects-list-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.filters-card {
  margin-bottom: var(--spacing-md);
}

.filters-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-md);
  
  @media (min-width: 600px) {
    grid-template-columns: repeat(2, 1fr);
  }
}

.filter-item {
  width: 100%;
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
