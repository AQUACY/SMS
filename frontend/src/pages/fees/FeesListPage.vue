<template>
  <q-page class="fees-list-page">
    <MobilePageHeader
      title="Fees Management"
      subtitle="Manage term fees for classes, levels, or school-wide"
    >
      <template v-slot:actions>
        <q-btn
          color="primary"
          :label="$q.screen.gt.xs ? 'Add Fee' : ''"
          icon="add"
          unelevated
          to="/app/fees/create"
          class="mobile-btn"
        />
      </template>
    </MobilePageHeader>

    <!-- Filters -->
    <MobileCard variant="default" padding="md" class="filters-card">
      <div class="filters-grid">
        <q-select
          v-model="filters.term_id"
          :options="terms"
          option-label="name"
          option-value="id"
          emit-value
          map-options
          label="Filter by Term"
          outlined
          dense
          clearable
          @update:model-value="fetchFees"
          class="filter-item"
        />
        <q-select
          v-model="filters.fee_type"
          :options="feeTypeOptions"
          label="Filter by Type"
          outlined
          dense
          clearable
          @update:model-value="fetchFees"
          class="filter-item"
        />
        <q-select
          v-model="filters.level_category"
          :options="levelOptions"
          label="Filter by Level"
          outlined
          dense
          clearable
          @update:model-value="fetchFees"
          class="filter-item"
        />
        <div class="filter-item">
          <q-toggle
            v-model="filters.is_active"
            label="Active Only"
            @update:model-value="fetchFees"
          />
        </div>
      </div>
    </MobileCard>

    <!-- Mobile Card View -->
    <div class="mobile-list-view">
      <div v-if="loading" class="loading-cards">
        <q-card v-for="i in 3" :key="i" class="mobile-list-card">
          <q-card-section>
            <q-skeleton type="rect" height="100px" />
          </q-card-section>
        </q-card>
      </div>
      
      <div v-else-if="fees.length > 0" class="cards-container">
        <MobileListCard
          v-for="fee in fees"
          :key="fee.id"
          :title="fee.scope_description || 'Fee'"
          :subtitle="fee.term?.name || 'N/A'"
          :description="getFeeDescription(fee)"
          icon="attach_money"
          :badge="fee.is_active ? 'Active' : 'Inactive'"
          :badge-color="fee.is_active ? 'positive' : 'negative'"
          icon-bg="rgba(255, 193, 7, 0.1)"
          @click="viewFee(fee.id)"
        >
          <template v-slot:extra>
            <div class="fee-amount">
              <div class="amount-label">Amount</div>
              <div class="amount-value">
                {{ formatCurrency(fee.amount) }} {{ fee.currency || 'GHS' }}
              </div>
            </div>
            <div class="card-actions">
              <q-btn
                flat
                dense
                icon="visibility"
                color="primary"
                label="View"
                @click.stop="viewFee(fee.id)"
                size="sm"
              />
              <q-btn
                flat
                dense
                icon="edit"
                color="primary"
                label="Edit"
                @click.stop="editFee(fee.id)"
                size="sm"
              />
            </div>
          </template>
        </MobileListCard>
      </div>
      
      <div v-else class="empty-state">
        <q-icon name="attach_money" size="64px" color="grey-5" />
        <div class="empty-text">No fees found</div>
      </div>
    </div>

    <!-- Desktop Table View -->
    <div class="desktop-table-view">
      <q-card class="widget-card">
        <q-card-section>
          <div class="row q-gutter-md q-mb-md">
            <div class="col-12 col-md-3">
              <q-select
                v-model="filters.term_id"
                :options="terms"
                option-label="name"
                option-value="id"
                emit-value
                map-options
                label="Filter by Term"
                outlined
                dense
                clearable
                @update:model-value="fetchFees"
              />
            </div>
            <div class="col-12 col-md-3">
              <q-select
                v-model="filters.fee_type"
                :options="feeTypeOptions"
                label="Filter by Type"
                outlined
                dense
                clearable
                @update:model-value="fetchFees"
              />
            </div>
            <div class="col-12 col-md-3">
              <q-select
                v-model="filters.level_category"
                :options="levelOptions"
                label="Filter by Level"
                outlined
                dense
                clearable
                @update:model-value="fetchFees"
              />
            </div>
            <div class="col-12 col-md-3">
              <q-toggle
                v-model="filters.is_active"
                label="Active Only"
                @update:model-value="fetchFees"
              />
            </div>
          </div>

          <q-table
            :rows="fees"
            :columns="columns"
            row-key="id"
            :loading="loading"
            :pagination="pagination"
            @request="onRequest"
            flat
          >
          <template v-slot:body-cell-scope="props">
            <q-td :props="props">
              <q-badge
                :color="getScopeColor(props.row.fee_type)"
                :label="props.row.scope_description"
              />
            </q-td>
          </template>

          <template v-slot:body-cell-amount="props">
            <q-td :props="props">
              <div class="text-body2 text-weight-bold">
                {{ formatCurrency(props.row.amount) }} {{ props.row.currency || 'GHS' }}
              </div>
            </q-td>
          </template>

          <template v-slot:body-cell-status="props">
            <q-td :props="props">
              <q-badge
                :color="props.row.is_active ? 'positive' : 'negative'"
                :label="props.row.is_active ? 'Active' : 'Inactive'"
              />
            </q-td>
          </template>

          <template v-slot:body-cell-actions="props">
            <q-td :props="props">
              <q-btn
                flat
                dense
                icon="edit"
                color="primary"
                @click="editFee(props.row.id)"
                class="q-mr-xs"
              />
              <q-btn
                flat
                dense
                icon="delete"
                color="negative"
                @click="confirmDelete(props.row)"
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
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import MobileListCard from 'src/components/mobile/MobileListCard.vue';
import api from 'src/services/api';

const router = useRouter();
const $q = useQuasar();

const loading = ref(false);
const fees = ref([]);
const terms = ref([]);

const filters = ref({
  term_id: null,
  fee_type: null,
  level_category: null,
  is_active: true,
});

const pagination = ref({
  page: 1,
  rowsPerPage: 15,
  rowsNumber: 0,
});

const feeTypeOptions = [
  { label: 'School Wide', value: 'school' },
  { label: 'Level Specific', value: 'level' },
  { label: 'Class Specific', value: 'class' },
];

const levelOptions = [
  { label: 'Nursery', value: 'nursery' },
  { label: 'Creche', value: 'creche' },
  { label: 'Primary', value: 'primary' },
  { label: 'JHS', value: 'jhs' },
  { label: 'SHS', value: 'shs' },
];

const columns = [
  { name: 'name', label: 'Fee Name', field: 'name', align: 'left', sortable: true },
  { name: 'term', label: 'Term', field: 'term', align: 'left', sortable: true },
  { name: 'scope', label: 'Scope', field: 'scope', align: 'left' },
  { name: 'amount', label: 'Amount', field: 'amount', align: 'right', sortable: true },
  { name: 'due_date', label: 'Due Date', field: 'due_date', align: 'left', sortable: true },
  { name: 'status', label: 'Status', field: 'status', align: 'center' },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'center' },
];

onMounted(() => {
  fetchTerms();
  fetchFees();
});

async function fetchTerms() {
  try {
    const response = await api.get('/terms', { params: { per_page: 100 } });
    if (response.data.success) {
      terms.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch terms:', error);
  }
}

async function fetchFees() {
  loading.value = true;
  try {
    const params = {
      per_page: pagination.value.rowsPerPage,
      page: pagination.value.page,
    };

    if (filters.value.term_id) {
      params.term_id = filters.value.term_id;
    }
    if (filters.value.fee_type) {
      params.fee_type = filters.value.fee_type;
    }
    if (filters.value.level_category) {
      params.level_category = filters.value.level_category;
    }
    if (filters.value.is_active !== null) {
      params.is_active = filters.value.is_active;
    }

    const response = await api.get('/fees', { params });
    if (response.data.success) {
      fees.value = (response.data.data || []).map(fee => ({
        ...fee,
        fee_type: fee.class_id ? 'class' : (fee.level_category ? 'level' : 'school'),
        scope_description: fee.class ? fee.class.name : (fee.level_category ? `${fee.level_category.charAt(0).toUpperCase() + fee.level_category.slice(1)} Level` : 'School Wide'),
        term: fee.term ? `${fee.term.name} (${fee.term.academic_year?.name || ''})` : 'N/A',
      }));
      
      if (response.data.meta) {
        pagination.value.rowsNumber = response.data.meta.total || 0;
      }
    }
  } catch (error) {
    console.error('Failed to fetch fees:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch fees',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
}

function onRequest(props) {
  pagination.value = props.pagination;
  fetchFees();
}

function viewFee(id) {
  router.push(`/app/fees/${id}`);
}

function editFee(id) {
  router.push(`/app/fees/${id}/edit`);
}

function getFeeDescription(fee) {
  const parts = [];
  if (fee.fee_type) {
    const typeLabel = feeTypeOptions.find(opt => opt.value === fee.fee_type)?.label || fee.fee_type;
    parts.push(typeLabel);
  }
  if (fee.level_category) {
    const levelLabel = levelOptions.find(opt => opt.value === fee.level_category)?.label || fee.level_category;
    parts.push(levelLabel);
  }
  if (fee.due_date) {
    parts.push(`Due: ${new Date(fee.due_date).toLocaleDateString()}`);
  }
  return parts.join(' • ') || 'No additional details';
}

function confirmDelete(fee) {
  $q.dialog({
    title: 'Delete Fee',
    message: `Are you sure you want to delete "${fee.name}"? This action cannot be undone.`,
    cancel: {
      label: 'Cancel',
      flat: true,
    },
    ok: {
      label: 'Delete',
      flat: true,
      color: 'negative',
    },
    persistent: true,
  }).onOk(() => {
    deleteFee(fee.id);
  });
}

async function deleteFee(id) {
  try {
    const response = await api.delete(`/fees/${id}`);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Fee deleted successfully',
        position: 'top',
      });
      fetchFees();
    }
  } catch (error) {
    console.error('Failed to delete fee:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to delete fee',
      position: 'top',
    });
  }
}

function getScopeColor(feeType) {
  const colors = {
    school: 'primary',
    level: 'info',
    class: 'secondary',
  };
  return colors[feeType] || 'grey';
}

function formatCurrency(amount) {
  if (!amount) return '0.00';
  return parseFloat(amount).toFixed(2);
}
</script>

<style lang="scss" scoped>
.fees-list-page {
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
  
  @media (min-width: 960px) {
    grid-template-columns: repeat(4, 1fr);
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

.fee-amount {
  margin-bottom: var(--spacing-sm);
  
  .amount-label {
    font-size: var(--font-size-xs);
    color: var(--text-secondary);
    margin-bottom: var(--spacing-xs);
  }
  
  .amount-value {
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--primary-color);
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

