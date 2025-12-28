<template>
  <div class="row q-col-gutter-md">
    <!-- Recent Schools -->
    <div class="col-12 col-lg-6">
      <q-card class="widget-card">
        <q-card-section>
          <div class="row items-center justify-between q-mb-md">
            <div class="text-h6 text-weight-bold">Recent Schools</div>
            <q-btn flat dense label="View All" color="primary" size="sm" to="/app/super-admin/schools" />
          </div>
          <q-list separator>
            <q-item v-for="school in recentSchools" :key="school.id" clickable :to="`/app/super-admin/schools/${school.id}`">
              <q-item-section avatar>
                <q-icon name="school" color="primary" size="32px" />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ school.name }}</q-item-label>
                <q-item-label caption>{{ school.code }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-badge :color="school.is_active ? 'positive' : 'negative'" :label="school.is_active ? 'Active' : 'Inactive'" />
              </q-item-section>
            </q-item>
          </q-list>
          <div v-if="!recentSchools || recentSchools.length === 0" class="text-center q-pa-md text-grey-6">
            No schools yet
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- Monthly Revenue Chart -->
    <div class="col-12 col-lg-6">
      <q-card class="widget-card">
        <q-card-section>
          <div class="text-h6 text-weight-bold q-mb-md">Monthly Subscription Revenue</div>
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

    <!-- Quick Actions -->
    <div class="col-12">
      <q-card class="widget-card">
        <q-card-section>
          <div class="text-h6 text-weight-bold q-mb-md">Quick Actions</div>
          <div class="row q-gutter-sm">
            <q-btn color="primary" label="Manage Schools" icon="school" unelevated to="/app/super-admin/schools" />
            <q-btn color="secondary" label="Subscription Prices" icon="attach_money" unelevated to="/app/subscription-prices" />
            <q-btn color="accent" label="View All Payments" icon="payment" unelevated to="/app/payments" />
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

const recentSchools = computed(() => props.stats.recent_schools || []);
const monthlyRevenue = computed(() => props.stats.monthly_revenue || []);

function formatMonth(monthStr) {
  const [year, month] = monthStr.split('-');
  const date = new Date(year, month - 1);
  return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('en-GH', {
    style: 'currency',
    currency: 'GHS',
    minimumFractionDigits: 0,
  }).format(amount);
}

function getProgressValue(revenue) {
  if (!monthlyRevenue.value || monthlyRevenue.value.length === 0) return 0;
  const maxRevenue = Math.max(...monthlyRevenue.value.map(m => parseFloat(m.revenue)));
  return maxRevenue > 0 ? parseFloat(revenue) / maxRevenue : 0;
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

