<template>
  <q-page class="payments-page">
    <MobilePageHeader
      title="My Payments"
      subtitle="Payment history"
    >
      <template v-slot:actions>
        <q-btn
          color="secondary"
          :label="$q.screen.gt.xs ? 'Verify' : ''"
          icon="verified"
          unelevated
          to="/app/parent/verify-payment"
          class="mobile-btn q-mr-xs"
        />
        <q-btn
          color="primary"
          :label="$q.screen.gt.xs ? 'Subscriptions' : ''"
          icon="subscriptions"
          unelevated
          to="/app/parent/subscriptions"
          class="mobile-btn"
        />
      </template>
    </MobilePageHeader>

    <!-- Filters -->
    <MobileCard variant="default" padding="md" class="filters-card">
      <div class="filters-grid">
        <q-select
          v-model="filterStatus"
          :options="statusOptions"
          label="Filter by Status"
          outlined
          dense
          clearable
          @update:model-value="fetchPayments"
          class="filter-item"
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
          class="filter-item"
        />
      </div>
    </MobileCard>

    <!-- Mobile Card View -->
    <div class="mobile-list-view">
      <div v-if="loading" class="loading-cards">
        <q-card v-for="i in 3" :key="i" class="mobile-list-card">
          <q-card-section>
            <q-skeleton type="rect" height="120px" />
          </q-card-section>
        </q-card>
      </div>
      
      <div v-else-if="payments.length > 0" class="cards-container">
        <MobileListCard
          v-for="payment in payments"
          :key="payment.id"
          :title="payment.student?.full_name || 'N/A'"
          :subtitle="payment.term?.name || 'N/A'"
          :description="getPaymentDescription(payment)"
          icon="payment"
          :badge="payment.status"
          :badge-color="getStatusColor(payment.status)"
          icon-bg="rgba(76, 175, 80, 0.1)"
          @click="viewPayment(payment)"
        >
          <template v-slot:extra>
            <div class="payment-details">
              <div class="detail-item">
                <div class="detail-label">Amount</div>
                <div class="detail-value">
                  {{ formatCurrency(payment.amount) }} {{ payment.currency || 'GHS' }}
                </div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Date</div>
                <div class="detail-value">{{ formatDate(payment.created_at) }}</div>
              </div>
            </div>
            <div class="card-actions">
              <q-btn
                flat
                dense
                icon="visibility"
                color="primary"
                label="View"
                @click.stop="viewPayment(payment)"
                size="sm"
              />
            </div>
          </template>
        </MobileListCard>
      </div>
      
      <div v-else class="empty-state">
        <q-icon name="payment" size="64px" color="grey-5" />
        <div class="empty-text">No payments found</div>
      </div>
    </div>

    <!-- Desktop Table View -->
    <div class="desktop-table-view">
      <q-card class="widget-card">
        <q-card-section>
          <div class="row q-gutter-sm q-mb-md">
            <div class="col-12 col-sm-6">
              <q-select
                v-model="filterStatus"
                :options="statusOptions"
                label="Filter by Status"
                outlined
                dense
                clearable
                @update:model-value="fetchPayments"
              />
            </div>
            <div class="col-12 col-sm-6">
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
              />
            </div>
          </div>

          <q-table
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
              <div v-if="props.row.verification_token && props.row.status === 'pending'" class="text-caption text-primary q-mt-xs">
                Token: {{ props.row.verification_token }}
              </div>
              <div v-if="props.row.momo_transaction_id" class="text-caption text-grey-7">
                TXN: {{ props.row.momo_transaction_id }}
              </div>
            </q-td>
          </template>

          <template v-slot:body-cell-actions="props">
            <q-td :props="props">
              <div class="row q-gutter-xs">
                <q-btn
                  v-if="props.row.status === 'pending' && props.row.verification_token"
                  flat
                  dense
                  round
                  icon="verified"
                  color="positive"
                  @click="goToVerify(props.row)"
                >
                  <q-tooltip>Verify Payment</q-tooltip>
                </q-btn>
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
        </q-table>
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
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

function goToVerify(payment) {
  router.push({
    path: '/app/parent/verify-payment',
    query: {
      token: payment.verification_token,
    },
  });
}

function getPaymentDescription(payment) {
  const parts = [];
  if (payment.term?.academic_year?.name) {
    parts.push(`Year: ${payment.term.academic_year.name}`);
  }
  if (payment.payment_method) {
    parts.push(`Method: ${formatPaymentMethod(payment)}`);
  }
  if (payment.reference) {
    parts.push(`Ref: ${payment.reference}`);
  }
  return parts.join(' • ') || 'No additional details';
}
</script>

<style lang="scss" scoped>
.payments-page {
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

.payment-details {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-sm);
  
  .detail-item {
    .detail-label {
      font-size: var(--font-size-xs);
      color: var(--text-secondary);
      margin-bottom: var(--spacing-xs);
    }
    
    .detail-value {
      font-size: var(--font-size-sm);
      color: var(--text-primary);
      font-weight: 500;
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
