<template>
  <q-page class="parent-page">
    <MobilePageHeader
      title="My Children"
      subtitle="View your linked children"
    >
      <template v-slot:actions>
        <q-btn
          color="primary"
          :label="$q.screen.gt.xs ? 'Link Child' : ''"
          icon="add"
          unelevated
          to="/app/parent/link-child"
          class="mobile-btn"
        />
      </template>
    </MobilePageHeader>

    <!-- Content Area -->
    <div class="parent-content">
      <!-- Skeleton Loading -->
      <div v-if="loading" class="q-gutter-md">
        <div
          v-for="n in 3"
          :key="n"
        >
          <q-card class="child-card">
            <q-card-section>
              <q-skeleton type="rect" height="80px" class="q-mb-md" />
              <q-skeleton type="text" width="60%" />
              <q-skeleton type="text" width="40%" />
              <q-skeleton type="text" width="50%" class="q-mt-sm" />
            </q-card-section>
            <q-card-actions>
              <q-skeleton type="rect" width="100px" height="40px" />
            </q-card-actions>
          </q-card>
        </div>
      </div>

      <div v-else-if="children.length === 0" class="empty-state text-center q-pa-xl">
      <q-icon name="child_care" size="64px" color="grey-5" class="q-mb-md" />
      <div class="text-h6 text-grey-7 q-mb-sm">No Children Linked</div>
      <div class="text-body2 text-grey-6 q-mb-md">
        Link your child using the student ID or number provided by the school.
      </div>
        <q-btn
          color="primary"
          label="Link Your First Child"
          icon="add"
          unelevated
          size="lg"
          to="/app/parent/link-child"
          class="q-mt-md"
        />
      </div>

      <div v-else class="children-cards">
        <MobileListCard
          v-for="child in children"
          :key="child.id"
          :title="getFullName(child)"
          :subtitle="`ID: ${child.student_number || 'N/A'}`"
          :description="getChildDescription(child)"
          icon="child_care"
          :badge="child.has_active_subscription ? 'Active' : 'No Subscription'"
          :badge-color="child.has_active_subscription ? 'positive' : 'warning'"
          icon-bg="rgba(156, 39, 176, 0.1)"
          @click="viewChild(child)"
        />
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import api from 'src/services/api';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileListCard from 'src/components/mobile/MobileListCard.vue';

const router = useRouter();
const $q = useQuasar();

const loading = ref(false);
const children = ref([]);

onMounted(() => {
  fetchChildren();
});

async function fetchChildren() {
  loading.value = true;
  try {
    const response = await api.get('/parent/children');
    if (response.data.success) {
      children.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch children:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch children',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
}

function viewChild(child) {
  router.push(`/app/parent/children/${child.id}`);
}

function subscribe(child) {
  // Navigate to subscriptions page where they can see available terms
  router.push('/app/parent/subscriptions');
  $q.notify({
    type: 'info',
    message: 'Please select a term to subscribe for ' + getFullName(child),
    position: 'top',
  });
}

function getFullName(child) {
  if (child.full_name) {
    return child.full_name;
  }
  // Fallback: construct from individual name fields
  const parts = [
    child.first_name,
    child.middle_name,
    child.last_name
  ].filter(part => part && part.trim() !== '');
  return parts.join(' ') || 'Unknown';
}

function formatDate(date) {
  if (!date) return 'N/A';
  try {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    if (isNaN(dateObj.getTime())) return 'N/A';
    
    return new Date(dateObj).toLocaleDateString('en-GB', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  } catch (error) {
    return 'N/A';
  }
}

function getChildDescription(child) {
  const parts = [];
  if (child.active_enrollment?.class) {
    parts.push(`Class: ${child.active_enrollment.class.name}`);
  }
  if (child.has_active_subscription) {
    parts.push('Active Subscription');
  } else {
    parts.push('No Active Subscription');
  }
  return parts.join(' • ') || 'No additional details';
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
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.children-cards {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.info-row {
  padding: 4px 0;
}

.empty-state {
  background: white;
  border-radius: 16px;
  margin: 16px;
}

.blurred-content {
  position: relative;
}

.blur-text {
  filter: blur(4px);
  user-select: none;
  pointer-events: none;
  color: transparent;
  text-shadow: 0 0 8px rgba(0, 0, 0, 0.5);
}

// Mobile optimizations
@media (max-width: 600px) {
  .parent-header {
    padding: 12px 16px;
  }

  .parent-content {
    padding: 12px;
  }

  .child-card {
    margin-bottom: 12px;
  }
}
</style>
