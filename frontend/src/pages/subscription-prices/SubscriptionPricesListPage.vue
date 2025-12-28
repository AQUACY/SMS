<template>
  <q-page class="q-pa-lg">
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">Subscription Prices</div>
        <div class="text-body2 text-grey-7">Manage subscription pricing for the platform</div>
      </div>
      <div>
        <q-btn
          color="primary"
          label="Add Price"
          icon="add"
          unelevated
          to="/app/subscription-prices/create"
        />
      </div>
    </div>

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
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
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
</script>

<style lang="scss" scoped>
.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}
</style>

