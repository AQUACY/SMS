<template>
  <div class="row q-col-gutter-md">
    <!-- Today's Attendance -->
    <div v-if="todayAttendance" class="col-12 col-md-6">
      <q-card class="widget-card">
        <q-card-section>
          <div class="text-h6 text-weight-bold q-mb-md">Today's Attendance</div>
          <div class="row q-col-gutter-sm">
            <div class="col-6">
              <div class="text-center">
                <div class="text-h4 text-weight-bold text-green">{{ todayAttendance.present || 0 }}</div>
                <div class="text-caption text-grey-7">Present</div>
              </div>
            </div>
            <div class="col-6">
              <div class="text-center">
                <div class="text-h4 text-weight-bold text-red">{{ todayAttendance.absent || 0 }}</div>
                <div class="text-caption text-grey-7">Absent</div>
              </div>
            </div>
          </div>
          <q-btn 
            flat 
            dense 
            label="View Attendance" 
            color="primary" 
            size="sm" 
            class="full-width q-mt-md"
            to="/app/attendance"
          />
        </q-card-section>
      </q-card>
    </div>

    <!-- Active Term -->
    <div v-if="activeTerm" class="col-12 col-md-6">
      <q-card class="widget-card">
        <q-card-section>
          <div class="text-h6 text-weight-bold q-mb-md">Active Term</div>
          <div class="text-h5 q-mb-sm">{{ activeTerm.name }}</div>
          <div class="text-caption text-grey-7">Term {{ activeTerm.term_number }}</div>
          <q-btn 
            flat 
            dense 
            label="Manage Terms" 
            color="primary" 
            size="sm" 
            class="full-width q-mt-md"
            to="/app/terms"
          />
        </q-card-section>
      </q-card>
    </div>

    <!-- Recent Payments -->
    <div class="col-12 col-lg-6">
      <q-card class="widget-card">
        <q-card-section>
          <div class="row items-center justify-between q-mb-md">
            <div class="text-h6 text-weight-bold">Recent Fee Payments</div>
            <q-btn flat dense label="View All" color="primary" size="sm" to="/app/payments" />
          </div>
          <q-list separator v-if="recentPayments && recentPayments.length > 0">
            <q-item v-for="payment in recentPayments" :key="payment.id" clickable :to="`/app/payments/${payment.id}`">
              <q-item-section avatar>
                <q-icon 
                  :name="getPaymentIcon(payment.status)" 
                  :color="getPaymentColor(payment.status)" 
                  size="32px" 
                />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ payment.student?.full_name || 'N/A' }}</q-item-label>
                <q-item-label caption>{{ payment.reference }}</q-item-label>
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
            No recent payments
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- Quick Actions -->
    <div class="col-12 col-lg-6">
      <q-card class="widget-card">
        <q-card-section>
          <div class="text-h6 text-weight-bold q-mb-md">Quick Actions</div>
          <div class="row q-gutter-sm">
            <q-btn color="primary" label="Students" icon="people" unelevated to="/app/students" />
            <q-btn color="secondary" label="Teachers" icon="person" unelevated to="/app/teachers" />
            <q-btn color="accent" label="Classes" icon="class" unelevated to="/app/classes" />
            <q-btn color="positive" label="Fee Payments" icon="payment" unelevated to="/app/payments" />
            <q-btn color="info" label="Assessments" icon="edit" unelevated to="/app/assessments" />
            <q-btn color="warning" label="Users" icon="people_outline" unelevated to="/app/users" />
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

const todayAttendance = computed(() => props.stats.today_attendance);
const activeTerm = computed(() => props.stats.active_term);
const recentPayments = computed(() => props.stats.recent_payments || []);

function formatCurrency(amount) {
  return new Intl.NumberFormat('en-GH', {
    style: 'currency',
    currency: 'GHS',
    minimumFractionDigits: 0,
  }).format(amount);
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

