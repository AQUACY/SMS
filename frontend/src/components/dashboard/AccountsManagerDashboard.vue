<template>
  <div class="row q-col-gutter-md">
    <!-- Payment Status Breakdown -->
    <div class="col-12 col-md-6">
      <q-card class="widget-card">
        <q-card-section>
          <div class="text-h6 text-weight-bold q-mb-md">Payment Status Breakdown</div>
          <q-list separator v-if="statusBreakdown && statusBreakdown.length > 0">
            <q-item v-for="status in statusBreakdown" :key="status.status">
              <q-item-section avatar>
                <q-icon 
                  :name="getPaymentIcon(status.status)" 
                  :color="getPaymentColor(status.status)" 
                  size="32px" 
                />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-capitalize">{{ status.status }}</q-item-label>
                <q-item-label caption>{{ status.count }} payments</q-item-label>
              </q-item-section>
              <q-item-section side>
                <div class="text-weight-bold">{{ formatCurrency(status.total) }}</div>
              </q-item-section>
            </q-item>
          </q-list>
          <div v-else class="text-center q-pa-md text-grey-6">
            No payment data available
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- Monthly Revenue Chart -->
    <div class="col-12 col-md-6">
      <q-card class="widget-card">
        <q-card-section>
          <div class="text-h6 text-weight-bold q-mb-md">Monthly Revenue (Last 6 Months)</div>
          <div v-if="monthlyRevenue && monthlyRevenue.length > 0">
            <div class="row q-col-gutter-xs">
              <div class="col-12" v-for="month in monthlyRevenue" :key="month.month">
                <div class="row items-center q-mb-sm">
                  <div class="col-4 text-caption">{{ formatMonth(month.month) }}</div>
                  <div class="col-6">
                    <q-linear-progress 
                      :value="getProgressValue(month.revenue)" 
                      color="primary" 
                      size="20px"
                      rounded
                    />
                  </div>
                  <div class="col-2 text-right text-caption text-weight-bold">
                    {{ formatCurrency(month.revenue) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center q-pa-md text-grey-6">
            No revenue data available
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- Recent Pending Payments -->
    <div class="col-12">
      <q-card class="widget-card">
        <q-card-section>
          <div class="row items-center justify-between q-mb-md">
            <div class="text-h6 text-weight-bold">Recent Pending Payments</div>
            <q-btn flat dense label="View All" color="primary" size="sm" to="/app/payments?status=pending" />
          </div>
          <q-table
            v-if="recentPending && recentPending.length > 0"
            :rows="recentPending"
            :columns="paymentColumns"
            row-key="id"
            flat
            :rows-per-page-options="[0]"
            hide-pagination
          >
            <template v-slot:body-cell-student="props">
              <q-td :props="props">
                {{ props.row.student?.full_name || 'N/A' }}
              </q-td>
            </template>
            <template v-slot:body-cell-amount="props">
              <q-td :props="props">
                <div class="text-weight-bold">{{ formatCurrency(props.value) }}</div>
              </q-td>
            </template>
            <template v-slot:body-cell-status="props">
              <q-td :props="props">
                <q-badge :color="getPaymentColor(props.value)" :label="props.value" />
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
                  size="sm"
                  :to="`/app/payments/${props.row.id}`"
                />
              </q-td>
            </template>
          </q-table>
          <div v-else class="text-center q-pa-md text-grey-6">
            No pending payments
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- Quick Actions -->
    <div class="col-12">
      <q-card class="widget-card">
        <q-card-section>
          <div class="text-h6 text-weight-bold q-mb-md">Quick Actions</div>
          <div class="row q-gutter-sm">
            <q-btn color="primary" label="Fee Payments" icon="payment" unelevated to="/app/payments" />
            <q-btn color="secondary" label="Fees Management" icon="attach_money" unelevated to="/app/fees" />
            <q-btn color="accent" label="Pending Payments" icon="pending" unelevated to="/app/payments?status=pending" />
          </div>
        </q-card-section>
      </q-card>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({}),
  },
});

const statusBreakdown = computed(() => props.stats.status_breakdown || []);
const monthlyRevenue = computed(() => props.stats.monthly_revenue_chart || []);
const recentPending = computed(() => props.stats.recent_pending_payments || []);

const paymentColumns = [
  { name: 'reference', label: 'Reference', field: 'reference', align: 'left' },
  { name: 'student', label: 'Student', field: 'student', align: 'left' },
  { name: 'amount', label: 'Amount', field: 'amount', align: 'right' },
  { name: 'status', label: 'Status', field: 'status', align: 'center' },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'center' },
];

function formatCurrency(amount) {
  return new Intl.NumberFormat('en-GH', {
    style: 'currency',
    currency: 'GHS',
    minimumFractionDigits: 0,
  }).format(amount);
}

function formatMonth(monthStr) {
  const [year, month] = monthStr.split('-');
  const date = new Date(year, month - 1);
  return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
}

function getProgressValue(revenue) {
  if (!monthlyRevenue.value || monthlyRevenue.value.length === 0) return 0;
  const maxRevenue = Math.max(...monthlyRevenue.value.map(m => parseFloat(m.revenue)));
  return maxRevenue > 0 ? parseFloat(revenue) / maxRevenue : 0;
}

function getPaymentIcon(status) {
  const icons = {
    completed: 'check_circle',
    pending: 'pending',
    processing: 'sync',
    failed: 'error',
  };
  return icons[status] || 'payment';
}

function getPaymentColor(status) {
  const colors = {
    completed: 'positive',
    pending: 'warning',
    processing: 'info',
    failed: 'negative',
  };
  return colors[status] || 'grey';
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

