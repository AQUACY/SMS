<template>
  <q-page class="subscriptions-page">
    <MobilePageHeader
      title="My Subscriptions"
      subtitle="Active and past subscriptions"
    >
      <template v-slot:actions>
        <q-btn
          color="primary"
          :label="$q.screen.gt.xs ? 'Subscribe' : ''"
          icon="payment"
          unelevated
          to="/app/parent/payments"
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
          @update:model-value="fetchSubscriptions"
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
          @update:model-value="fetchSubscriptions"
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
      
      <div v-else-if="subscriptions.length > 0" class="cards-container">
        <MobileListCard
          v-for="subscription in subscriptions"
          :key="subscription.id"
          :title="subscription.student?.full_name || 'N/A'"
          :subtitle="subscription.term?.name || 'N/A'"
          :description="getSubscriptionDescription(subscription)"
          icon="subscriptions"
          :badge="subscription.status"
          :badge-color="getStatusColor(subscription.status)"
          icon-bg="rgba(76, 175, 80, 0.1)"
          @click="viewSubscription(subscription)"
        >
          <template v-slot:extra>
            <div class="subscription-details">
              <div class="detail-item">
                <div class="detail-label">Amount</div>
                <div class="detail-value">
                  {{ formatCurrency(subscription.amount) }} {{ subscription.currency || 'GHS' }}
                </div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Validity</div>
                <div class="detail-value">
                  {{ formatDate(subscription.starts_at) }} - {{ formatDate(subscription.expires_at) }}
                </div>
              </div>
            </div>
            <div class="card-actions">
              <q-btn
                flat
                dense
                icon="visibility"
                color="primary"
                label="View Details"
                @click.stop="viewSubscription(subscription)"
                size="sm"
              />
            </div>
          </template>
        </MobileListCard>
      </div>
      
      <div v-else class="empty-state">
        <q-icon name="subscriptions" size="64px" color="grey-5" />
        <div class="empty-text">No subscriptions found</div>
        <div class="empty-subtext">Make a payment to subscribe to a term</div>
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
                @update:model-value="fetchSubscriptions"
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
                @update:model-value="fetchSubscriptions"
              />
            </div>
          </div>

          <q-table
            :rows="subscriptions"
            :columns="columns"
            row-key="id"
            :loading="loading"
            flat
            class="subscriptions-table"
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

          <template v-slot:body-cell-status="props">
            <q-td :props="props">
              <q-badge
                :color="getStatusColor(props.row.status)"
                :label="props.row.status"
              />
            </q-td>
          </template>

          <template v-slot:body-cell-amount="props">
            <q-td :props="props">
              <div class="text-body2">
                {{ formatCurrency(props.row.amount) }} {{ props.row.currency || 'GHS' }}
              </div>
            </q-td>
          </template>

          <template v-slot:body-cell-dates="props">
            <q-td :props="props">
              <div class="text-body2">
                <div>Start: {{ formatDate(props.row.starts_at) }}</div>
                <div class="text-caption text-grey-7">
                  Expires: {{ formatDate(props.row.expires_at) }}
                </div>
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
                @click="viewSubscription(props.row)"
              >
                <q-tooltip>View Details</q-tooltip>
              </q-btn>
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
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import MobileListCard from 'src/components/mobile/MobileListCard.vue';
import api from 'src/services/api';

const router = useRouter();
const $q = useQuasar();

const loading = ref(false);
const subscriptions = ref([]);
const filterStatus = ref(null);
const filterStudent = ref(null);

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Expired', value: 'expired' },
  { label: 'Cancelled', value: 'cancelled' },
];

const columns = [
  { name: 'student', label: 'Student', field: 'student', align: 'left', sortable: true },
  { name: 'term', label: 'Term', field: 'term', align: 'left', sortable: true },
  { name: 'status', label: 'Status', field: 'status', align: 'center' },
  { name: 'amount', label: 'Amount', field: 'amount', align: 'right' },
  { name: 'dates', label: 'Validity Period', field: 'dates', align: 'left' },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'center' },
];

const studentOptions = computed(() => {
  const students = new Set();
  subscriptions.value.forEach(sub => {
    if (sub.student) {
      students.add(sub.student);
    }
  });
  return Array.from(students);
});

onMounted(() => {
  fetchSubscriptions();
});

async function fetchSubscriptions() {
  loading.value = true;
  try {
    const response = await api.get('/parent/subscriptions');
    if (response.data.success) {
      let data = response.data.data || [];
      
      // Apply filters
      if (filterStatus.value) {
        data = data.filter(sub => sub.status === filterStatus.value);
      }
      if (filterStudent.value) {
        data = data.filter(sub => sub.student_id === filterStudent.value);
      }

      subscriptions.value = data;
    }
  } catch (error) {
    console.error('Failed to fetch subscriptions:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch subscriptions',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
}

function viewSubscription(subscription) {
  // Could navigate to a detail page or show a dialog
  $q.dialog({
    title: 'Subscription Details',
    message: `
      <div class="q-gutter-sm">
        <div><strong>Student:</strong> ${subscription.student?.full_name || 'N/A'}</div>
        <div><strong>Term:</strong> ${subscription.term?.name || 'N/A'}</div>
        <div><strong>Status:</strong> ${subscription.status}</div>
        <div><strong>Amount:</strong> ${formatCurrency(subscription.amount)} ${subscription.currency || 'GHS'}</div>
        <div><strong>Start Date:</strong> ${formatDate(subscription.starts_at)}</div>
        <div><strong>Expiry Date:</strong> ${formatDate(subscription.expires_at)}</div>
      </div>
    `,
    html: true,
  });
}

function getStatusColor(status) {
  const colors = {
    active: 'positive',
    expired: 'grey',
    cancelled: 'negative',
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
  });
}

function getSubscriptionDescription(subscription) {
  const parts = [];
  if (subscription.term?.academic_year?.name) {
    parts.push(`Year: ${subscription.term.academic_year.name}`);
  }
  if (subscription.student?.student_number) {
    parts.push(`ID: ${subscription.student.student_number}`);
  }
  return parts.join(' • ') || 'No additional details';
}
</script>

<style lang="scss" scoped>
.subscriptions-page {
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

.subscription-details {
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
  font-weight: 600;
}

.empty-subtext {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  margin-top: var(--spacing-xs);
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

.subscription-card {
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

.subscriptions-table {
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
