<template>
  <q-page class="parent-page">
    <!-- Mobile Header -->
    <div class="parent-header q-pa-md">
      <div class="row items-center">
        <q-btn
          flat
          round
          icon="arrow_back"
          @click="router.push('/app/parent/children')"
          class="q-mr-sm"
          size="md"
        />
        <div class="col">
          <div class="text-h6 text-weight-bold">Child Details</div>
          <div class="text-caption text-grey-7">Academic records</div>
        </div>
      </div>
    </div>

    <!-- Content Area -->
    <div class="parent-content q-pa-md">
      <!-- Skeleton Loading -->
      <div v-if="loading" class="q-gutter-md">
        <q-card class="info-card">
          <q-card-section>
            <div class="row items-center q-mb-md">
              <q-skeleton type="QAvatar" size="80px" class="q-mr-md" />
              <div class="col">
                <q-skeleton type="text" width="70%" class="q-mb-sm" />
                <q-skeleton type="text" width="40%" />
              </div>
            </div>
            <q-separator class="q-my-md" />
            <q-skeleton type="text" width="60%" class="q-mb-md" />
            <q-skeleton type="rect" height="200px" />
          </q-card-section>
        </q-card>
        <q-card class="actions-card">
          <q-card-section>
            <q-skeleton type="text" width="60%" class="q-mb-md" />
            <q-skeleton type="list" />
          </q-card-section>
        </q-card>
      </div>

      <div v-else-if="child" class="q-gutter-md">
          <!-- Basic Information Card -->
          <q-card class="info-card">
            <q-card-section class="q-pa-md">
              <div class="row items-center q-mb-md">
                <q-avatar size="72px" class="bg-primary q-mr-md">
                  <q-icon name="person" size="40px" color="white" />
                </q-avatar>
                <div class="col">
                  <div class="text-h6 text-weight-bold q-mb-xs">{{ getFullName(child) }}</div>
                  <div class="text-body2 text-grey-7 q-mb-xs">ID: {{ child.student_number }}</div>
                  <q-badge
                    :color="child.is_active ? 'positive' : 'negative'"
                    :label="child.is_active ? 'Active' : 'Inactive'"
                    size="sm"
                  />
                </div>
              </div>

              <q-separator class="q-my-md" />

              <div :class="{ 'blurred-content': !child.has_active_subscription }">
                <div class="q-gutter-y-md">
                  <div class="info-item">
                    <div class="text-caption text-grey-7 q-mb-xs">Date of Birth</div>
                    <div class="text-body1">{{ formatDate(child.date_of_birth) }}</div>
                  </div>
                  <div class="info-item">
                    <div class="text-caption text-grey-7 q-mb-xs">Gender</div>
                    <div class="text-body1">{{ child.gender ? child.gender.charAt(0).toUpperCase() + child.gender.slice(1) : 'Not provided' }}</div>
                  </div>
                  <div class="info-item">
                    <div class="text-caption text-grey-7 q-mb-xs">Email</div>
                    <div class="text-body1">{{ child.email || 'Not provided' }}</div>
                  </div>
                  <div class="info-item">
                    <div class="text-caption text-grey-7 q-mb-xs">Phone</div>
                    <div class="text-body1">{{ child.phone || 'Not provided' }}</div>
                  </div>
                  <div class="info-item">
                    <div class="text-caption text-grey-7 q-mb-xs">Address</div>
                    <div class="text-body1">{{ child.address || 'Not provided' }}</div>
                  </div>
                </div>
              </div>
            </q-card-section>
          </q-card>

          <!-- Class Information Card -->
          <q-card class="info-card">
            <q-card-section class="q-pa-md">
              <div class="text-h6 q-mb-md">Current Class</div>
              <div v-if="child.active_enrollment?.class" class="row items-center">
                <q-icon name="class" size="24px" color="primary" class="q-mr-sm" />
                <div>
                  <div class="text-body1 text-weight-medium">{{ child.active_enrollment.class.name }}</div>
                  <div class="text-caption text-grey-7">
                    Academic Year: {{ child.active_enrollment.academic_year?.name || 'N/A' }}
                  </div>
                </div>
              </div>
              <div v-else class="text-body2 text-grey-7">Not enrolled in any class</div>
            </q-card-section>
          </q-card>

          <!-- Quick Actions Card -->
          <q-card class="actions-card">
          <!-- Payment Actions Section -->
          <q-card-section class="q-pa-md q-pb-sm">
            <div class="text-h6 q-mb-md">Payments & Subscriptions</div>
            
            <!-- Pay School Fees Button -->
            <q-btn
              flat
              unelevated
              class="action-btn full-width q-mb-sm"
              color="primary"
              @click="paySchoolFees(child)"
              size="lg"
            >
              <q-icon name="school" color="primary" size="24px" class="q-mr-sm" />
              <div class="col text-left">
                <div class="text-body1 text-weight-medium">Pay School Fees</div>
                <div class="text-caption text-grey-7">Pay term fees to the school</div>
              </div>
              <q-icon name="chevron_right" color="grey-6" />
            </q-btn>

            <!-- Subscribe Button -->
            <q-btn
              flat
              unelevated
              class="action-btn full-width"
              :color="child.has_active_subscription ? 'positive' : 'warning'"
              @click="subscribeToTerm(child)"
              size="lg"
            >
              <q-icon 
                :name="child.has_active_subscription ? 'check_circle' : 'card_membership'" 
                :color="child.has_active_subscription ? 'positive' : 'warning'" 
                size="24px" 
                class="q-mr-sm" 
              />
              <div class="col text-left">
                <div class="text-body1 text-weight-medium">
                  {{ child.has_active_subscription ? 'Manage Subscription' : 'Subscribe' }}
                </div>
                <div class="text-caption text-grey-7">
                  {{ child.has_active_subscription ? 'View or renew subscription' : 'Subscribe to view academic records' }}
                </div>
              </div>
              <q-icon name="chevron_right" color="grey-6" />
            </q-btn>
          </q-card-section>

          <q-separator class="q-mx-md" />

          <!-- Subscription Banner -->
          <q-banner
            v-if="!child.has_active_subscription"
            dense
            rounded
            class="bg-info text-white q-ma-md"
          >
            <template v-slot:avatar>
              <q-icon name="info" color="white" />
            </template>
            <div class="text-body2">
              Subscribe to unlock full access to your child's academic records, attendance, results, and report cards.
            </div>
          </q-banner>

          <q-card-section class="q-pa-md">
            <div class="text-h6 q-mb-md">Quick Actions</div>
            <div class="q-gutter-sm">
              <q-btn
                flat
                unelevated
                class="action-btn full-width"
                :class="{ 'blurred-content': !child.has_active_subscription }"
                @click="viewAttendance(child)"
                size="lg"
              >
                <q-icon name="checklist" color="primary" size="24px" class="q-mr-sm" />
                <div class="col text-left">
                  <div class="text-body1 text-weight-medium">View Attendance</div>
                  <div class="text-caption text-grey-7">View attendance records</div>
                </div>
                <q-icon name="chevron_right" color="grey-6" />
              </q-btn>

              <q-btn
                flat
                unelevated
                class="action-btn full-width"
                :class="{ 'blurred-content': !child.has_active_subscription }"
                @click="viewResults(child)"
                size="lg"
              >
                <q-icon name="assessment" color="primary" size="24px" class="q-mr-sm" />
                <div class="col text-left">
                  <div class="text-body1 text-weight-medium">View Results</div>
                  <div class="text-caption text-grey-7">Exam and assessment results</div>
                </div>
                <q-icon name="chevron_right" color="grey-6" />
              </q-btn>

              <q-btn
                flat
                unelevated
                class="action-btn full-width"
                :class="{ 'blurred-content': !child.has_active_subscription }"
                @click="viewReportCards(child)"
                size="lg"
              >
                <q-icon name="description" color="primary" size="24px" class="q-mr-sm" />
                <div class="col text-left">
                  <div class="text-body1 text-weight-medium">View Report Cards</div>
                  <div class="text-caption text-grey-7">Term report cards</div>
                </div>
                <q-icon name="chevron_right" color="grey-6" />
              </q-btn>

              <q-btn
                flat
                unelevated
                class="action-btn full-width"
                :class="{ 'blurred-content': !child.has_active_subscription }"
                @click="viewAssessments(child)"
                size="lg"
              >
                <q-icon name="quiz" color="primary" size="24px" class="q-mr-sm" />
                <div class="col text-left">
                  <div class="text-body1 text-weight-medium">View Assessments</div>
                  <div class="text-caption text-grey-7">All assessments and exams</div>
                </div>
                <q-icon name="chevron_right" color="grey-6" />
              </q-btn>

              <q-separator class="q-my-md" />

              <q-btn
                flat
                unelevated
                class="action-btn full-width"
                color="negative"
                @click="confirmUnlinkChild(child)"
                size="lg"
              >
                <q-icon name="link_off" color="negative" size="24px" class="q-mr-sm" />
                <div class="col text-left">
                  <div class="text-body1 text-weight-medium">Unlink Child</div>
                  <div class="text-caption text-grey-7">Remove this child from your account</div>
                </div>
                <q-icon name="chevron_right" color="grey-6" />
              </q-btn>
            </div>
          </q-card-section>
        </q-card>
        </div>

      <div v-if="!loading && !child" class="empty-state text-center q-pa-xl">
        <q-icon name="error_outline" size="64px" color="grey-5" class="q-mb-md" />
        <div class="text-h6 text-grey-7 q-mb-sm">Child Not Found</div>
        <div class="text-body2 text-grey-6">
          The child you're looking for doesn't exist or you don't have access to view it.
        </div>
        <q-btn
          color="primary"
          label="Back to My Children"
          icon="arrow_back"
          unelevated
          size="lg"
          @click="router.push('/app/parent/children')"
          class="q-mt-md"
        />
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import api from 'src/services/api';

const route = useRoute();
const router = useRouter();
const $q = useQuasar();

const childId = computed(() => route.params.id);
const loading = ref(false);
const child = ref(null);

onMounted(() => {
  fetchChildDetails();
});

async function fetchChildDetails() {
  loading.value = true;
  try {
    const response = await api.get(`/parent/children`);
    if (response.data.success) {
      const children = response.data.data || [];
      child.value = children.find(c => c.id === parseInt(childId.value));
      
      if (!child.value) {
        $q.notify({
          type: 'negative',
          message: 'Child not found',
          position: 'top',
        });
      }
    }
  } catch (error) {
    console.error('Failed to fetch child details:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch child details',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
}

function getFullName(child) {
  if (child.full_name) {
    return child.full_name;
  }
  const parts = [
    child.first_name,
    child.middle_name,
    child.last_name
  ].filter(part => part && part.trim() !== '');
  return parts.join(' ') || 'Unknown';
}

function formatDate(date) {
  if (!date) return 'Not provided';
  try {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    if (isNaN(dateObj.getTime())) return 'Not provided';
    
    return new Date(dateObj).toLocaleDateString('en-GB', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  } catch (error) {
    return 'Not provided';
  }
}

function viewAttendance(child) {
  if (!child.has_active_subscription) {
    $q.notify({
      type: 'warning',
      message: 'Please subscribe to view attendance records',
      position: 'top',
    });
    return;
  }
  router.push(`/app/parent/children/${child.id}/attendance`);
}

function viewResults(child) {
  if (!child.has_active_subscription) {
    $q.notify({
      type: 'warning',
      message: 'Please subscribe to view results',
      position: 'top',
    });
    return;
  }
  router.push(`/app/parent/children/${child.id}/results`);
}

function viewReportCards(child) {
  if (!child.has_active_subscription) {
    $q.notify({
      type: 'warning',
      message: 'Please subscribe to view report cards',
      position: 'top',
    });
    return;
  }
  router.push(`/app/parent/children/${child.id}/report-cards`);
}

function viewAssessments(child) {
  if (!child.has_active_subscription) {
    $q.notify({
      type: 'warning',
      message: 'Please subscribe to view assessments',
      position: 'top',
    });
    return;
  }
  router.push(`/app/parent/children/${child.id}/assessments`);
}

function paySchoolFees(child) {
  // Navigate to payment page - let user select term if no active term
  if (child.active_enrollment?.class?.academic_year?.active_term?.id) {
    router.push({
      path: `/app/parent/payment/${child.id}/${child.active_enrollment.class.academic_year.active_term.id}`,
      query: { type: 'fee' },
    });
  } else {
    // Navigate without term ID - user can select term on payment page
    router.push({
      path: `/app/parent/payment/${child.id}`,
      query: { type: 'fee' },
    });
  }
}

function subscribeToTerm(child) {
  // Navigate to payment page - let user select term if no active term
  if (child.active_enrollment?.class?.academic_year?.active_term?.id) {
    router.push({
      path: `/app/parent/payment/${child.id}/${child.active_enrollment.class.academic_year.active_term.id}`,
      query: { type: 'subscription' },
    });
  } else {
    // Navigate without term ID - user can select term on payment page
    // Note: Subscription might not require a term, but we'll include it for consistency
    router.push({
      path: `/app/parent/payment/${child.id}`,
      query: { type: 'subscription' },
    });
  }
}

function confirmUnlinkChild(child) {
  $q.dialog({
    title: 'Unlink Child',
    message: `Are you sure you want to unlink ${getFullName(child)} (${child.student_number}) from your account? This action cannot be undone.`,
    cancel: {
      label: 'Cancel',
      flat: true,
      color: 'grey-7',
    },
    ok: {
      label: 'Unlink',
      flat: true,
      color: 'negative',
    },
    persistent: true,
  }).onOk(() => {
    unlinkChild(child);
  });
}

async function unlinkChild(child) {
  try {
    const response = await api.delete(`/parent/unlink-child/${child.id}`);
    
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Child unlinked successfully',
        position: 'top',
      });
      
      // Redirect to children list
      router.push('/app/parent/children');
    }
  } catch (error) {
    console.error('Failed to unlink child:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to unlink child',
      position: 'top',
    });
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

.info-card,
.actions-card {
  border-radius: 16px;
  border: none;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  background: white;
}

.info-item {
  padding: 4px 0;
}

.action-btn {
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 8px;
  background: white;
  border: 1px solid rgba(0, 0, 0, 0.08);
  transition: all 0.2s ease;

  &:active {
    transform: scale(0.98);
    background: #f5f5f5;
  }
}

.empty-state {
  background: white;
  border-radius: 16px;
  margin: 16px;
}

.blurred-content {
  filter: blur(5px);
  pointer-events: none;
}

// Mobile optimizations
@media (max-width: 600px) {
  .parent-header {
    padding: 12px 16px;
  }

  .parent-content {
    padding: 12px;
  }

  .action-btn {
    padding: 14px;
  }
}
</style>

