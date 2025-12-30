<template>
  <q-page class="academic-years-page">
    <MobilePageHeader
      title="Academic Years"
      subtitle="Manage academic years"
    >
      <template v-slot:actions>
        <q-btn
          v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
          color="primary"
          :label="$q.screen.gt.xs ? 'Add Academic Year' : ''"
          icon="add"
          unelevated
          to="/app/academic-years/create"
          class="mobile-btn"
        />
      </template>
    </MobilePageHeader>

    <!-- Filters -->
    <MobileCard variant="default" padding="md" class="filters-card">
      <q-toggle
        v-model="filterActive"
        label="Show Active Only"
        @update:model-value="onFilter"
      />
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
      
      <div v-else-if="academicYears.length > 0" class="cards-container">
        <MobileListCard
          v-for="year in academicYears"
          :key="year.id"
          :title="year.name"
          :subtitle="getYearDates(year)"
          :description="getYearDescription(year)"
          icon="calendar_today"
          :badge="year.is_active ? 'Active' : 'Inactive'"
          :badge-color="year.is_active ? 'positive' : 'grey'"
          icon-bg="rgba(76, 175, 80, 0.1)"
          @click="viewAcademicYear(year.id)"
        >
          <template v-slot:extra>
            <div class="year-stats">
              <div class="stat-item">
                <div class="stat-label">Terms</div>
                <div class="stat-value">{{ year.terms?.length || 0 }}</div>
              </div>
            </div>
            <div class="card-actions">
              <q-btn
                flat
                dense
                icon="visibility"
                color="primary"
                label="View"
                @click.stop="viewAcademicYear(year.id)"
                size="sm"
              />
              <q-btn
                v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
                flat
                dense
                icon="edit"
                color="primary"
                label="Edit"
                @click.stop="editAcademicYear(year.id)"
                size="sm"
              />
              <q-btn
                v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin && !year.is_active"
                flat
                dense
                icon="play_arrow"
                color="positive"
                label="Activate"
                @click.stop="activateAcademicYear(year.id)"
                size="sm"
              />
            </div>
          </template>
        </MobileListCard>
      </div>
      
      <div v-else class="empty-state">
        <q-icon name="calendar_today" size="64px" color="grey-5" />
        <div class="empty-text">No academic years found</div>
      </div>
    </div>

    <!-- Desktop Table View -->
    <div class="desktop-table-view">
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
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'src/stores/auth';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import MobileListCard from 'src/components/mobile/MobileListCard.vue';
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

function getYearDates(year) {
  if (year.start_date && year.end_date) {
    return `${formatDate(year.start_date)} - ${formatDate(year.end_date)}`;
  }
  return 'Dates not set';
}

function getYearDescription(year) {
  const parts = [];
  if (year.terms?.length > 0) {
    parts.push(`${year.terms.length} term${year.terms.length > 1 ? 's' : ''}`);
  }
  return parts.join(' • ') || 'No terms added';
}

onMounted(() => {
  fetchAcademicYears();
});
</script>

<style lang="scss" scoped>
.academic-years-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.filters-card {
  margin-bottom: var(--spacing-md);
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

.year-stats {
  display: flex;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-sm);
  
  .stat-item {
    .stat-label {
      font-size: var(--font-size-xs);
      color: var(--text-secondary);
      margin-bottom: var(--spacing-xs);
    }
    
    .stat-value {
      font-size: var(--font-size-base);
      font-weight: 600;
      color: var(--text-primary);
    }
  }
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
