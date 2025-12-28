<template>
  <div class="row q-col-gutter-md">
    <!-- My Children -->
    <div class="col-12 col-lg-6">
      <q-card class="widget-card">
        <q-card-section>
          <div class="row items-center justify-between q-mb-md">
            <div class="text-h6 text-weight-bold">My Children</div>
            <q-btn flat dense label="View All" color="primary" size="sm" to="/app/parent/children" />
          </div>
          <q-list separator v-if="children && children.length > 0">
            <q-item 
              v-for="child in children" 
              :key="child?.id || Math.random()" 
              clickable 
              :to="child?.id ? `/app/parent/children/${child.id}` : null"
            >
              <q-item-section avatar>
                <q-avatar color="primary" text-color="white" size="40px">
                  {{ child?.name?.charAt(0) || '?' }}
                </q-avatar>
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ child?.name || 'N/A' }}</q-item-label>
                <q-item-label caption>
                  {{ child?.student_number || 'N/A' }} - {{ child?.class || 'Not enrolled' }}
                </q-item-label>
                <q-item-label caption class="text-grey-6">{{ child?.school || '' }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-icon name="chevron_right" color="grey-5" />
              </q-item-section>
            </q-item>
          </q-list>
          <div v-else class="text-center q-pa-md text-grey-6">
            No children linked yet
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- Active Subscriptions -->
    <div class="col-12 col-lg-6">
      <q-card class="widget-card">
        <q-card-section>
          <div class="row items-center justify-between q-mb-md">
            <div class="text-h6 text-weight-bold">Active Subscriptions</div>
            <q-btn flat dense label="View All" color="primary" size="sm" to="/app/parent/children" />
          </div>
          <q-list separator v-if="subscriptions && subscriptions.length > 0">
            <q-item v-for="sub in subscriptions" :key="sub.id">
              <q-item-section avatar>
                <q-icon name="subscriptions" color="positive" size="32px" />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ sub.student_name }}</q-item-label>
                <q-item-label caption>{{ sub.term_name }}</q-item-label>
                <q-item-label caption class="text-grey-6">
                  Expires: {{ formatDate(sub.expires_at) }}
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-badge color="positive" label="Active" />
              </q-item-section>
            </q-item>
          </q-list>
          <div v-else class="text-center q-pa-md text-grey-6">
            No active subscriptions
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- Recent Payments -->
    <div class="col-12 col-lg-6">
      <q-card class="widget-card">
        <q-card-section>
          <div class="row items-center justify-between q-mb-md">
            <div class="text-h6 text-weight-bold">Recent Payments</div>
            <q-btn flat dense label="View All" color="primary" size="sm" />
          </div>
          <q-list separator v-if="recentPayments && recentPayments.length > 0">
            <q-item v-for="payment in recentPayments" :key="payment.id">
              <q-item-section avatar>
                <q-icon 
                  :name="getPaymentIcon(payment.status)" 
                  :color="getPaymentColor(payment.status)" 
                  size="32px" 
                />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ payment.student?.full_name || 'N/A' }}</q-item-label>
                <q-item-label caption>{{ payment.term?.name || 'N/A' }}</q-item-label>
                <q-item-label caption class="text-grey-6">{{ formatDate(payment.created_at) }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <div class="text-right">
                  <div class="text-weight-bold">{{ formatCurrency(payment.amount) }}</div>
                  <q-badge :color="getPaymentColor(payment.status)" :label="payment.status" />
                </div>
              </q-item-section>
            </q-item>
          </q-list>
          <div v-else class="text-center q-pa-md text-grey-6">
            No payments yet
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- Student Activity -->
    <div class="col-12 col-lg-6">
      <q-card class="widget-card">
        <q-card-section>
          <div class="text-h6 text-weight-bold q-mb-md">Recent Activity</div>
          <q-list separator v-if="studentActivity && studentActivity.length > 0">
            <q-item v-for="activity in studentActivity" :key="activity?.student_id || Math.random()">
              <q-item-section avatar>
                <q-avatar color="primary" text-color="white" size="32px">
                  {{ activity?.student_name?.charAt(0) || '?' }}
                </q-avatar>
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ activity?.student_name || 'N/A' }}</q-item-label>
                <q-item-label caption v-if="activity?.latest_attendance">
                  Last attendance: {{ formatDate(activity.latest_attendance?.date) }} 
                  ({{ activity.latest_attendance?.status || 'N/A' }})
                </q-item-label>
                <q-item-label caption v-if="activity?.latest_result">
                  Latest result: {{ activity.latest_result?.assessment_name || 'N/A' }} 
                  ({{ activity.latest_result?.marks_obtained || 0 }}/{{ activity.latest_result?.total_marks || 0 }})
                </q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
          <div v-else class="text-center q-pa-md text-grey-6">
            No recent activity
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
            <q-btn 
              color="primary" 
              label="View Children" 
              icon="people" 
              unelevated 
              to="/app/parent/children" 
            />
            <q-btn 
              color="secondary" 
              label="Make Payment" 
              icon="payment" 
              unelevated 
              to="/app/parent/children" 
            />
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

const children = computed(() => props.stats.children || []);
const subscriptions = computed(() => props.stats.subscriptions || []);
const recentPayments = computed(() => props.stats.recent_payments || []);
const studentActivity = computed(() => props.stats.student_activity || []);

function formatCurrency(amount) {
  if (!amount && amount !== 0) return 'GHS 0';
  return new Intl.NumberFormat('en-GH', {
    style: 'currency',
    currency: 'GHS',
    minimumFractionDigits: 0,
  }).format(amount);
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A';
  try {
    return new Date(dateStr).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    });
  } catch (e) {
    return 'N/A';
  }
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

