<template>
  <q-page class="parent-page">
    <!-- Mobile Header -->
    <div class="parent-header q-pa-md">
      <div class="row items-center q-mb-sm">
        <div class="col">
          <div class="text-h5 text-weight-bold">My Children</div>
          <div class="text-body2 text-grey-7">View your linked children</div>
        </div>
        <q-btn
          round
          color="primary"
          icon="add"
          unelevated
          size="md"
          to="/app/parent/link-child"
          class="q-ml-sm"
        >
          <q-tooltip>Link Another Child</q-tooltip>
        </q-btn>
      </div>
    </div>

    <!-- Content Area -->
    <div class="parent-content q-pa-md">
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

      <div v-else class="q-gutter-md">
        <q-card
          v-for="child in children"
          :key="child.id"
          class="child-card"
          @click="viewChild(child)"
        >
          <q-card-section class="q-pa-md">
            <div class="row items-center q-mb-md">
              <q-avatar size="64px" color="primary" class="q-mr-md">
                <q-icon name="person" size="36px" color="white" />
              </q-avatar>
              <div class="col">
                <div class="text-h6 text-weight-bold q-mb-xs">{{ getFullName(child) }}</div>
                <div class="text-body2 text-grey-7">{{ child.student_number }}</div>
              </div>
            </div>

            <q-separator class="q-my-md" />

            <div class="q-gutter-y-sm">
              <!-- Always visible: Name and Class -->
              <div v-if="child.active_enrollment?.class" class="info-row">
                <div class="text-caption text-grey-7 q-mb-xs">Current Class</div>
                <div class="text-body1 text-weight-medium">{{ child.active_enrollment.class.name }}</div>
              </div>

              <!-- Blurred content if no active subscription -->
              <div
                v-if="!child.has_active_subscription"
                class="blurred-content"
              >
                <div v-if="child.gender">
                  <div class="text-caption text-grey-7">Gender</div>
                  <div class="text-body2 blur-text">{{ child.gender }}</div>
                </div>
                <div v-if="child.date_of_birth">
                  <div class="text-caption text-grey-7">Date of Birth</div>
                  <div class="text-body2 blur-text">{{ formatDate(child.date_of_birth) }}</div>
                </div>
                <div v-if="child.email">
                  <div class="text-caption text-grey-7">Email</div>
                  <div class="text-body2 blur-text">{{ child.email }}</div>
                </div>
                <div v-if="child.phone">
                  <div class="text-caption text-grey-7">Phone</div>
                  <div class="text-body2 blur-text">{{ child.phone }}</div>
                </div>
              </div>

              <!-- Clear content if has active subscription -->
              <div v-else>
                <div v-if="child.gender">
                  <div class="text-caption text-grey-7">Gender</div>
                  <div class="text-body2">{{ child.gender }}</div>
                </div>
                <div v-if="child.date_of_birth">
                  <div class="text-caption text-grey-7">Date of Birth</div>
                  <div class="text-body2">{{ formatDate(child.date_of_birth) }}</div>
                </div>
                <div v-if="child.email">
                  <div class="text-caption text-grey-7">Email</div>
                  <div class="text-body2">{{ child.email }}</div>
                </div>
                <div v-if="child.phone">
                  <div class="text-caption text-grey-7">Phone</div>
                  <div class="text-body2">{{ child.phone }}</div>
                </div>
              </div>

              <!-- Subscription Status Badge -->
              <div class="q-mt-sm">
                <q-badge
                  v-if="child.has_active_subscription"
                  color="positive"
                  label="Subscribed"
                  icon="check_circle"
                  size="md"
                />
                <q-badge
                  v-else
                  color="warning"
                  label="Subscription Required"
                  icon="lock"
                  size="md"
                />
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
              @click.stop="viewChild(child)"
              class="full-width"
              size="md"
            />
            <!-- <q-btn
              v-if="!child.has_active_subscription"
              flat
              label="Subscribe"
              color="positive"
              icon="payment"
              @click.stop="subscribe(child)"
              class="full-width q-mt-sm"
              size="md"
            /> -->
          </q-card-actions>
        </q-card>
      </div>
    </div>
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

.child-card {
  border-radius: 16px;
  border: none;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.2s ease;
  cursor: pointer;
  background: white;

  &:active {
    transform: scale(0.98);
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.12);
  }
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
