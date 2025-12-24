<template>
  <q-page class="parent-page">
    <!-- Mobile Header -->
    <div class="parent-header q-pa-md">
      <div class="row items-center justify-between">
        <div class="col">
          <div class="text-h6 text-weight-bold">My Subscriptions</div>
          <div class="text-caption text-grey-7">Active and past subscriptions</div>
        </div>
        <q-btn
          round
          color="primary"
          icon="payment"
          unelevated
          size="md"
          to="/app/parent/payments"
          class="q-ml-sm"
        >
          <q-tooltip>Subscribe to Term</q-tooltip>
        </q-btn>
      </div>
    </div>

    <!-- Content Area -->
    <div class="parent-content q-pa-md">
      <!-- Skeleton Loading -->
      <div v-if="loading" class="q-gutter-md">
        <q-card class="subscription-card" v-for="n in 3" :key="n">
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
            @update:model-value="fetchSubscriptions"
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
            @update:model-value="fetchSubscriptions"
            class="col-12 col-sm-6"
          />
        </div>

        <!-- Desktop Table -->
        <q-table
          v-if="$q.screen.gt.sm"
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

        <!-- Mobile Card View -->
        <div v-if="$q.screen.lt.md" class="q-gutter-md">
          <q-card
            v-for="subscription in subscriptions"
            :key="subscription.id"
            class="subscription-card"
            @click="viewSubscription(subscription)"
          >
            <q-card-section class="q-pa-md">
              <div class="row items-center justify-between q-mb-md">
                <div>
                  <div class="text-h6 text-weight-bold">{{ subscription.student?.full_name || 'N/A' }}</div>
                  <div class="text-caption text-grey-7">{{ subscription.student?.student_number || '' }}</div>
                </div>
                <q-badge
                  :color="getStatusColor(subscription.status)"
                  :label="subscription.status"
                  size="md"
                />
              </div>

              <q-separator class="q-my-md" />

              <div class="q-gutter-y-sm">
                <div>
                  <div class="text-caption text-grey-7">Term</div>
                  <div class="text-body2">{{ subscription.term?.name || 'N/A' }}</div>
                  <div class="text-caption text-grey-7">{{ subscription.term?.academic_year?.name || '' }}</div>
                </div>
                <div>
                  <div class="text-caption text-grey-7">Amount</div>
                  <div class="text-body1 text-weight-bold">
                    {{ formatCurrency(subscription.amount) }} {{ subscription.currency || 'GHS' }}
                  </div>
                </div>
                <div>
                  <div class="text-caption text-grey-7">Validity Period</div>
                  <div class="text-body2">Start: {{ formatDate(subscription.starts_at) }}</div>
                  <div class="text-body2">Expires: {{ formatDate(subscription.expires_at) }}</div>
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

        <div v-if="!loading && subscriptions.length === 0" class="empty-state text-center q-pa-lg">
          <q-icon name="subscriptions" size="64px" color="grey-5" class="q-mb-md" />
          <div class="text-h6 text-grey-7 q-mb-sm">No Subscriptions Found</div>
          <div class="text-body2 text-grey-6">
            You don't have any subscriptions yet. Make a payment to subscribe to a term.
          </div>
        </div>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
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
