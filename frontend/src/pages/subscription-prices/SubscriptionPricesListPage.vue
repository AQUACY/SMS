<template>
  <q-page class="subscription-prices-list-page">
    <MobilePageHeader
      title="Subscription Prices"
      subtitle="Manage subscription pricing for the platform"
    >
      <template v-slot:actions>
        <q-btn
          color="primary"
          :label="$q.screen.gt.xs ? 'Add Price' : ''"
          icon="add"
          unelevated
          to="/app/subscription-prices/create"
          class="mobile-btn"
        />
      </template>
    </MobilePageHeader>

    <!-- Filters -->
    <MobileCard variant="default" padding="md" class="filters-card">
      <div class="filters-grid">
        <q-select
          v-model="filters.price_type"
          :options="priceTypeOptions"
          label="Filter by Type"
          outlined
          dense
          clearable
          @update:model-value="fetchPrices"
          class="filter-item"
        />
        <q-select
          v-model="filters.school_id"
          :options="schools"
          option-label="name"
          option-value="id"
          emit-value
          map-options
          label="Filter by School"
          outlined
          dense
          clearable
          :loading="loadingSchools"
          @update:model-value="fetchPrices"
          class="filter-item"
        />
        <div class="filter-item">
          <q-toggle
            v-model="filters.is_active"
            label="Active Only"
            @update:model-value="fetchPrices"
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
      
      <div v-else-if="prices.length > 0" class="cards-container">
        <MobileListCard
          v-for="price in prices"
          :key="price.id"
          :title="price.name"
          :subtitle="price.scope_description || 'N/A'"
          :description="getPriceDescription(price)"
          icon="attach_money"
          :badge="price.is_active ? 'Active' : 'Inactive'"
          :badge-color="price.is_active ? 'positive' : 'negative'"
          icon-bg="rgba(255, 193, 7, 0.1)"
          @click="editPrice(price.id)"
        >
          <template v-slot:extra>
            <div class="price-amount">
              <div class="amount-label">Price</div>
              <div class="amount-value">
                {{ formatCurrency(price.amount) }} {{ price.currency || 'GHS' }}
              </div>
            </div>
            <div class="card-actions">
              <q-btn
                flat
                dense
                icon="edit"
                color="primary"
                label="Edit"
                @click.stop="editPrice(price.id)"
                size="sm"
              />
              <q-btn
                flat
                dense
                icon="delete"
                color="negative"
                label="Delete"
                @click.stop="confirmDelete(price)"
                size="sm"
              />
            </div>
          </template>
        </MobileListCard>
      </div>
      
      <div v-else class="empty-state">
        <q-icon name="attach_money" size="64px" color="grey-5" />
        <div class="empty-text">No subscription prices found</div>
      </div>
    </div>

    <!-- Desktop Table View -->
    <div class="desktop-table-view">
      <q-card class="widget-card">
        <q-card-section>
          <div class="row q-gutter-md q-mb-md">
            <div class="col-12 col-md-3">
              <q-select
                v-model="filters.price_type"
                :options="priceTypeOptions"
                label="Filter by Type"
                outlined
                dense
                clearable
                @update:model-value="fetchPrices"
              />
            </div>
            <div class="col-12 col-md-3">
              <q-select
                v-model="filters.school_id"
                :options="schools"
                option-label="name"
                option-value="id"
                emit-value
                map-options
                label="Filter by School"
                outlined
                dense
                clearable
                :loading="loadingSchools"
                @update:model-value="fetchPrices"
              />
            </div>
            <div class="col-12 col-md-3">
              <q-toggle
                v-model="filters.is_active"
                label="Active Only"
                @update:model-value="fetchPrices"
              />
            </div>
          </div>

          <q-table
            :rows="prices"
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
                :color="getScopeColor(props.row.price_type)"
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
                @click="editPrice(props.row.id)"
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
    </div>
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
const loadingSchools = ref(false);
const prices = ref([]);
const schools = ref([]);

const filters = ref({
  price_type: null,
  school_id: null,
  is_active: true,
});

const pagination = ref({
  page: 1,
  rowsPerPage: 15,
  rowsNumber: 0,
});

const priceTypeOptions = [
  { label: 'Global (All Schools)', value: 'global' },
  { label: 'School Specific', value: 'school' },
];

const columns = [
  { name: 'name', label: 'Price Name', field: 'name', align: 'left', sortable: true },
  { name: 'scope', label: 'Scope', field: 'scope', align: 'left' },
  { name: 'amount', label: 'Amount', field: 'amount', align: 'right', sortable: true },
  { name: 'status', label: 'Status', field: 'status', align: 'center' },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'center' },
];

onMounted(() => {
  fetchSchools();
  fetchPrices();
});

async function fetchSchools() {
  loadingSchools.value = true;
  try {
    const response = await api.get('/schools', { params: { per_page: 100 } });
    if (response.data.success) {
      schools.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch schools:', error);
  } finally {
    loadingSchools.value = false;
  }
}

async function fetchPrices() {
  loading.value = true;
  try {
    const params = {
      per_page: pagination.value.rowsPerPage,
      page: pagination.value.page,
    };

    if (filters.value.price_type) {
      params.price_type = filters.value.price_type;
    }
    if (filters.value.school_id) {
      params.school_id = filters.value.school_id;
    }
    if (filters.value.is_active !== null) {
      params.is_active = filters.value.is_active;
    }

    const response = await api.get('/subscription-prices', { params });
    if (response.data.success) {
      prices.value = (response.data.data || []).map(price => ({
        ...price,
        price_type: price.school_id ? 'school' : 'global',
        scope_description: price.school ? price.school.name : 'Global (All Schools)',
      }));
      
      if (response.data.meta) {
        pagination.value.rowsNumber = response.data.meta.total || 0;
      }
    }
  } catch (error) {
    console.error('Failed to fetch subscription prices:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch subscription prices',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
}

function onRequest(props) {
  pagination.value = props.pagination;
  fetchPrices();
}

function editPrice(id) {
  router.push(`/app/subscription-prices/${id}/edit`);
}

function confirmDelete(price) {
  $q.dialog({
    title: 'Delete Subscription Price',
    message: `Are you sure you want to delete "${price.name}"? This action cannot be undone.`,
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
    deletePrice(price.id);
  });
}

async function deletePrice(id) {
  try {
    const response = await api.delete(`/subscription-prices/${id}`);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Subscription price deleted successfully',
        position: 'top',
      });
      fetchPrices();
    }
  } catch (error) {
    console.error('Failed to delete subscription price:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to delete subscription price',
      position: 'top',
    });
  }
}

function getScopeColor(priceType) {
  const colors = {
    global: 'primary',
    level: 'info',
    class: 'secondary',
  };
  return colors[priceType] || 'grey';
}

function formatCurrency(amount) {
  if (!amount) return '0.00';
  return parseFloat(amount).toFixed(2);
}

function getPriceDescription(price) {
  const parts = [];
  if (price.school?.name) {
    parts.push(`School: ${price.school.name}`);
  } else {
    parts.push('Global (All Schools)');
  }
  if (price.price_type) {
    const typeLabel = priceTypeOptions.find(opt => opt.value === price.price_type)?.label || price.price_type;
    parts.push(`Type: ${typeLabel}`);
  }
  return parts.join(' • ') || 'No additional details';
}
</script>

<style lang="scss" scoped>
.subscription-prices-list-page {
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
    grid-template-columns: repeat(3, 1fr);
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

.price-amount {
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

