<template>
  <q-page class="q-pa-lg">
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">Fees Management</div>
        <div class="text-body2 text-grey-7">Manage term fees for classes, levels, or school-wide</div>
      </div>
      <div>
        <q-btn
          color="primary"
          label="Add Fee"
          icon="add"
          unelevated
          to="/app/fees/create"
        />
      </div>
    </div>

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

function editFee(id) {
  router.push(`/app/fees/${id}/edit`);
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
.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}
</style>

