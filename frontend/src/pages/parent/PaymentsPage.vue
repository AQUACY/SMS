<template>
  <q-page class="parent-page">
    <!-- Mobile Header -->
    <div class="parent-header q-pa-md">
      <div class="row items-center justify-between">
        <div class="col">
          <div class="text-h6 text-weight-bold">My Payments</div>
          <div class="text-caption text-grey-7">Payment history</div>
        </div>
        <q-btn
          round
          color="primary"
          icon="subscriptions"
          unelevated
          size="md"
          to="/app/parent/subscriptions"
          class="q-ml-sm"
        >
          <q-tooltip>View Subscriptions</q-tooltip>
        </q-btn>
      </div>
    </div>

    <!-- Content Area -->
    <div class="parent-content q-pa-md">
      <!-- Skeleton Loading -->
      <div v-if="loading" class="q-gutter-md">
        <q-card class="payment-card" v-for="n in 3" :key="n">
          <q-card-section>
            <q-skeleton type="rect" height="60px" class="q-mb-md" />
            <q-skeleton type="text" width="80%" />
            <q-skeleton type="text" width="60%" />
            <q-skeleton type="text" width="40%" class="q-mt-sm" />
          </q-card-section>
        </q-card>
      </div>

      <div v-else>
        <!-- Filters (Mobile-friendly) -->
        <div class="q-gutter-sm q-mb-md">
          <q-select
            v-model="filterStatus"
            :options="statusOptions"
            label="Filter by Status"
            outlined
            dense
            clearable
            @update:model-value="fetchPayments"
            class="col-12 col-sm-6"
          />
          <q-select
            v-model="filterStudent"
            :options="studentOptions"
            option-label="full_name"
            option-value="id"
            emit-value
            map-options
            label="Filter by Student"
            outlined
            dense
            clearable
            @update:model-value="fetchPayments"
            class="col-12 col-sm-6"
          />
        </div>

        <!-- Desktop Table -->
        <q-table
          v-if="$q.screen.gt.sm"
          :rows="payments"
          :columns="columns"
          row-key="id"
          :loading="loading"
          flat
          class="payments-table"
        >
          <template v-slot:body-cell-student="props">
            <q-td :props="props">
              <div class="text-body2">{{ props.row.student?.full_name || 'N/A' }}</div>
              <div class="text-caption text-grey-7">{{ props.row.student?.student_number || '' }}</div>
            </q-td>
          </template>

          <template v-slot:body-cell-term="props">
            <q-td :props="props">
              <div class="text-body2">{{ props.row.term?.name || 'N/A' }}</div>
              <div class="text-caption text-grey-7">{{ props.row.term?.academic_year?.name || '' }}</div>
            </q-td>
          </template>

          <template v-slot:body-cell-amount="props">
            <q-td :props="props">
              <div class="text-body2 text-weight-bold">
                {{ formatCurrency(props.row.amount) }} {{ props.row.currency || 'GHS' }}
              </div>
            </q-td>
          </template>

          <template v-slot:body-cell-method="props">
            <q-td :props="props">
              <div class="text-body2">{{ formatPaymentMethod(props.row) }}</div>
              <div v-if="props.row.momo_provider" class="text-caption text-grey-7">
                {{ props.row.momo_provider.toUpperCase() }}
              </div>
            </q-td>
          </template>

          <template v-slot:body-cell-status="props">
            <q-td :props="props">
              <q-badge
                :color="getStatusColor(props.row.status)"
                :label="props.row.status"
              />
            </q-td>
          </template>

          <template v-slot:body-cell-reference="props">
            <q-td :props="props">
              <div class="text-body2">{{ props.row.reference || 'N/A' }}</div>
              <div v-if="props.row.momo_transaction_id" class="text-caption text-grey-7">
                TXN: {{ props.row.momo_transaction_id }}
              </div>
            </q-td>
          </template>

          <template v-slot:body-cell-date="props">
            <q-td :props="props">
              <div class="text-body2">{{ formatDate(props.row.created_at) }}</div>
              <div v-if="props.row.verified_at" class="text-caption text-grey-7">
                Verified: {{ formatDate(props.row.verified_at) }}
              </div>
            </q-td>
          </template>

          <template v-slot:body-cell-actions="props">
            <q-td :props="props">
              <q-btn
                flat
                dense
                round
                icon="visibility"
                color="primary"
                @click="viewPayment(props.row)"
              >
                <q-tooltip>View Details</q-tooltip>
              </q-btn>
            </q-td>
          </template>
        </q-table>

        <!-- Mobile Card View -->
        <div v-if="$q.screen.lt.md" class="q-gutter-md">
          <q-card
            v-for="payment in payments"
            :key="payment.id"
            class="payment-card"
            @click="viewPayment(payment)"
          >
            <q-card-section class="q-pa-md">
              <div class="row items-center justify-between q-mb-md">
                <div>
                  <div class="text-h6 text-weight-bold">{{ payment.student?.full_name || 'N/A' }}</div>
                  <div class="text-caption text-grey-7">{{ payment.student?.student_number || '' }}</div>
                </div>
                <q-badge
                  :color="getStatusColor(payment.status)"
                  :label="payment.status"
                  size="md"
                />
              </div>

              <q-separator class="q-my-md" />

              <div class="q-gutter-y-sm">
                <div>
                  <div class="text-caption text-grey-7">Term</div>
                  <div class="text-body2">{{ payment.term?.name || 'N/A' }}</div>
                  <div class="text-caption text-grey-7">{{ payment.term?.academic_year?.name || '' }}</div>
                </div>
                <div>
                  <div class="text-caption text-grey-7">Amount</div>
                  <div class="text-h6 text-weight-bold text-primary">
                    {{ formatCurrency(payment.amount) }} {{ payment.currency || 'GHS' }}
                  </div>
                </div>
                <div>
                  <div class="text-caption text-grey-7">Payment Method</div>
                  <div class="text-body2">{{ formatPaymentMethod(payment) }}</div>
                </div>
                <div>
                  <div class="text-caption text-grey-7">Date</div>
                  <div class="text-body2">{{ formatDate(payment.created_at) }}</div>
                </div>
                <div v-if="payment.reference">
                  <div class="text-caption text-grey-7">Reference</div>
                  <div class="text-body2">{{ payment.reference }}</div>
                </div>
              </div>
            </q-card-section>
            <q-card-actions class="q-pa-md q-pt-none">
              <q-btn
                flat
                label="View Details"
                color="primary"
                icon="chevron_right"
                icon-right="chevron_right"
                class="full-width"
                size="md"
              />
            </q-card-actions>
          </q-card>
        </div>

        <div v-if="!loading && payments.length === 0" class="empty-state text-center q-pa-lg">
          <q-icon name="payment" size="64px" color="grey-5" class="q-mb-md" />
          <div class="text-h6 text-grey-7 q-mb-sm">No Payments Found</div>
          <div class="text-body2 text-grey-6">
            You haven't made any payments yet.
          </div>
        </div>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'src/stores/auth';
import api from 'src/services/api';

const $q = useQuasar();
const authStore = useAuthStore();

const loading = ref(false);
const payments = ref([]);
const filterStatus = ref(null);
const filterStudent = ref(null);

const statusOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'Completed', value: 'completed' },
  { label: 'Failed', value: 'failed' },
];

const columns = [
  { name: 'student', label: 'Student', field: 'student', align: 'left', sortable: true },
  { name: 'term', label: 'Term', field: 'term', align: 'left', sortable: true },
  { name: 'amount', label: 'Amount', field: 'amount', align: 'right', sortable: true },
  { name: 'method', label: 'Payment Method', field: 'method', align: 'left' },
  { name: 'status', label: 'Status', field: 'status', align: 'center' },
  { name: 'reference', label: 'Reference', field: 'reference', align: 'left' },
  { name: 'date', label: 'Date', field: 'date', align: 'left', sortable: true },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'center' },
];

const studentOptions = computed(() => {
  const students = new Set();
  payments.value.forEach(payment => {
    if (payment.student) {
      students.add(payment.student);
    }
  });
  return Array.from(students);
});

onMounted(() => {
  fetchPayments();
});

async function fetchPayments() {
  loading.value = true;
  try {
    const response = await api.get('/parent/payments');
    if (response.data.success) {
      let data = response.data.data || [];
      
      // Apply filters
      if (filterStatus.value) {
        data = data.filter(payment => payment.status === filterStatus.value);
      }
      if (filterStudent.value) {
        data = data.filter(payment => payment.student_id === filterStudent.value);
      }

      payments.value = data;
    }
  } catch (error) {
    console.error('Failed to fetch payments:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch payments',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
}

function viewPayment(payment) {
  $q.dialog({
    title: 'Payment Details',
    message: `
      <div class="q-gutter-sm">
        <div><strong>Student:</strong> ${payment.student?.full_name || 'N/A'}</div>
        <div><strong>Term:</strong> ${payment.term?.name || 'N/A'}</div>
        <div><strong>Amount:</strong> ${formatCurrency(payment.amount)} ${payment.currency || 'GHS'}</div>
        <div><strong>Payment Method:</strong> ${formatPaymentMethod(payment)}</div>
        <div><strong>Status:</strong> ${payment.status}</div>
        <div><strong>Reference:</strong> ${payment.reference || 'N/A'}</div>
        <div><strong>Date:</strong> ${formatDate(payment.created_at)}</div>
        ${payment.verified_at ? `<div><strong>Verified At:</strong> ${formatDate(payment.verified_at)}</div>` : ''}
        ${payment.momo_transaction_id ? `<div><strong>Transaction ID:</strong> ${payment.momo_transaction_id}</div>` : ''}
      </div>
    `,
    html: true,
  });
}

function formatPaymentMethod(payment) {
  if (payment.payment_method === 'momo') {
    return `Mobile Money (${payment.momo_provider?.toUpperCase() || 'N/A'})`;
  }
  return payment.payment_method ? payment.payment_method.charAt(0).toUpperCase() + payment.payment_method.slice(1) : 'N/A';
}

function getStatusColor(status) {
  const colors = {
    pending: 'warning',
    completed: 'positive',
    failed: 'negative',
  };
  return colors[status] || 'grey';
}

function formatCurrency(amount) {
  if (!amount) return '0.00';
  return parseFloat(amount).toFixed(2);
}

function formatDate(date) {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}
</script>

<style lang="scss" scoped>
.parent-page {
  background: #f5f5f5;
  min-height: 100vh;
}

.parent-header {
  background: white;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  position: sticky;
  top: 0;
  z-index: 100;
}

.parent-content {
  max-width: 1200px;
  margin: 0 auto;
}

.payment-card {
  border-radius: 16px;
  border: none;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  background: white;
  cursor: pointer;
  transition: all 0.2s ease;

  &:active {
    transform: scale(0.98);
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.12);
  }
}

.payments-table {
  background: white;
  border-radius: 16px;
}

.empty-state {
  background: white;
  border-radius: 16px;
  margin: 16px;
}

// Mobile optimizations
@media (max-width: 600px) {
  .parent-header {
    padding: 12px 16px;
  }

  .parent-content {
    padding: 12px;
  }
}
</style>
