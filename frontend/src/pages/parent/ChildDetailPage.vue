<template>
  <q-page class="child-detail-page">
    <MobilePageHeader
      title="Child Details"
      subtitle="Academic records"
      :show-back="true"
      @back="router.push('/app/parent/children')"
    />

    <!-- Skeleton Loading -->
    <div v-if="loading" class="detail-loading">
      <MobileCard v-for="i in 2" :key="i" variant="default" padding="md" class="q-mb-md">
        <q-skeleton type="rect" height="100px" class="q-mb-md" />
        <q-skeleton type="text" width="60%" />
        <q-skeleton type="text" width="40%" />
      </MobileCard>
    </div>

    <div v-else-if="child" class="detail-content">
      <!-- Basic Information Card -->
      <MobileCard variant="default" padding="lg" class="info-card q-mb-md">
        <div class="child-header">
          <q-avatar size="80px" class="child-avatar bg-primary">
            <q-icon name="person" size="48px" color="white" />
          </q-avatar>
          <div class="child-info">
            <div class="child-name">{{ getFullName(child) }}</div>
            <div class="child-id">ID: {{ child.student_number }}</div>
            <q-badge
              :color="child.is_active ? 'positive' : 'negative'"
              :label="child.is_active ? 'Active' : 'Inactive'"
              class="q-mt-xs"
            />
          </div>
        </div>

        <q-separator class="q-my-md" />

        <div :class="{ 'blurred-content': !child.has_active_subscription }">
          <div class="info-grid">
            <div class="info-item">
              <div class="info-label">Date of Birth</div>
              <div class="info-value">{{ formatDate(child.date_of_birth) }}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Gender</div>
              <div class="info-value">{{ child.gender ? child.gender.charAt(0).toUpperCase() + child.gender.slice(1) : 'Not provided' }}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Email</div>
              <div class="info-value">{{ child.email || 'Not provided' }}</div>
            </div>
            <div class="info-item">
              <div class="info-label">Phone</div>
              <div class="info-value">{{ child.phone || 'Not provided' }}</div>
            </div>
            <div class="info-item full-width">
              <div class="info-label">Address</div>
              <div class="info-value">{{ child.address || 'Not provided' }}</div>
            </div>
          </div>
        </div>
      </MobileCard>

      <!-- Class Information Card -->
      <MobileCard variant="default" padding="md" class="q-mb-md">
        <div class="card-title">Current Class</div>
        <div v-if="child.active_enrollment?.class" class="class-info">
          <q-icon name="class" size="24px" color="primary" class="q-mr-sm" />
          <div>
            <div class="class-name">{{ child.active_enrollment.class.name }}</div>
            <div class="class-year">
              Academic Year: {{ child.active_enrollment.academic_year?.name || 'N/A' }}
            </div>
          </div>
        </div>
        <div v-else class="empty-text">Not enrolled in any class</div>
      </MobileCard>

      <!-- Payments & Subscriptions Card -->
      <MobileCard variant="default" padding="md" class="q-mb-md">
        <div class="card-title">Payments & Subscriptions</div>
            
        <div class="action-buttons">
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
        </div>

        <!-- Subscription Banner -->
        <q-banner
          v-if="!child.has_active_subscription"
          dense
          rounded
          class="subscription-banner bg-info text-white q-mt-md"
        >
          <template v-slot:avatar>
            <q-icon name="info" color="white" />
          </template>
          <div class="text-body2">
            Subscribe to unlock full access to your child's academic records, attendance, results, and report cards.
          </div>
        </q-banner>
      </MobileCard>

      <!-- Quick Actions Card -->
      <MobileCard variant="default" padding="md">
        <div class="card-title">Quick Actions</div>
        <div class="action-buttons">
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
      </MobileCard>
    </div>

    <div v-if="!loading && !child" class="empty-state">
      <q-icon name="error_outline" size="64px" color="grey-5" />
      <div class="empty-text">Child Not Found</div>
      <div class="empty-subtext">
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
  </q-page>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
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
.child-detail-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.detail-loading {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.detail-content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
  max-width: 1200px;
  margin: 0 auto;
}

.info-card {
  // Specific styling if needed
}

.child-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-md);
  border-bottom: 1px solid var(--border-light);
}

.child-avatar {
  flex-shrink: 0;
}

.child-info {
  flex: 1;
}

.child-name {
  font-size: var(--font-size-xl);
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: var(--spacing-xs);
}

.child-id {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  margin-bottom: var(--spacing-xs);
}

.info-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-md);
  
  @media (min-width: 600px) {
    grid-template-columns: repeat(2, 1fr);
  }
}

.info-item {
  &.full-width {
    grid-column: 1 / -1;
  }
}

.info-label {
  font-size: var(--font-size-xs);
  color: var(--text-secondary);
  margin-bottom: var(--spacing-xs);
}

.info-value {
  font-size: var(--font-size-base);
  color: var(--text-primary);
}

.card-title {
  font-size: var(--font-size-lg);
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: var(--spacing-md);
}

.class-info {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.class-name {
  font-size: var(--font-size-base);
  font-weight: 600;
  color: var(--text-primary);
}

.class-year {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
}

.empty-text {
  font-size: var(--font-size-base);
  color: var(--text-secondary);
  text-align: center;
  padding: var(--spacing-md);
}

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

.action-btn {
  border-radius: var(--radius-md);
  padding: var(--spacing-md);
  background: var(--bg-card);
  border: 1px solid var(--border-light);
  transition: all var(--transition-base);
  min-height: 64px;
  
  &:active {
    transform: scale(0.98);
    background: var(--bg-secondary);
  }
  
  @media (min-width: 768px) {
    &:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }
  }
}

.subscription-banner {
  margin-top: var(--spacing-md);
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
  font-size: var(--font-size-lg);
  font-weight: 600;
  color: var(--text-primary);
  margin-top: var(--spacing-md);
}

.empty-subtext {
  font-size: var(--font-size-base);
  color: var(--text-secondary);
  margin-top: var(--spacing-sm);
  margin-bottom: var(--spacing-md);
}

.blurred-content {
  filter: blur(5px);
  pointer-events: none;
  user-select: none;
}
</style>

