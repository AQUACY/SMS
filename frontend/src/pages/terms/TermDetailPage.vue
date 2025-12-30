<template>
  <q-page class="term-detail-page">
    <MobilePageHeader
      title="Term Details"
      subtitle="View and manage term information"
      :show-back="true"
      @back="router.push('/app/terms')"
    >
      <template v-slot:actions>
        <q-btn
          v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin && canEdit"
          color="primary"
          :label="$q.screen.gt.xs ? 'Edit Term' : ''"
          icon="edit"
          unelevated
          @click="router.push(`/app/terms/${termId}/edit`)"
          class="mobile-btn"
        />
      </template>
    </MobilePageHeader>

    <div v-if="loading" class="detail-loading">
      <MobileCard v-for="i in 3" :key="i" variant="default" padding="md" class="q-mb-md">
        <q-skeleton type="rect" height="100px" class="q-mb-md" />
        <q-skeleton type="text" width="60%" />
        <q-skeleton type="text" width="40%" />
      </MobileCard>
    </div>

    <div v-else-if="term" class="detail-content">
      <!-- Basic Information Card -->
      <MobileCard variant="default" padding="lg" class="info-card q-mb-md">
        <div class="term-header">
          <q-avatar size="80px" class="term-avatar bg-primary">
            <q-icon name="event" size="48px" color="white" />
          </q-avatar>
          <div class="term-info">
            <div class="term-name">{{ term.name }}</div>
            <div class="term-subtitle">
              Term {{ term.term_number }} - {{ term.academic_year?.name || 'N/A' }}
            </div>
            <q-badge
              :color="getStatusColor(term.status)"
              :label="formatStatus(term.status)"
              class="q-mt-xs"
            />
          </div>
        </div>

        <q-separator class="q-my-md" />

        <div class="info-grid">
          <div class="info-item">
            <div class="info-label">Start Date</div>
            <div class="info-value">{{ formatDate(term.start_date) }}</div>
          </div>
          <div class="info-item">
            <div class="info-label">End Date</div>
            <div class="info-value">{{ formatDate(term.end_date) }}</div>
          </div>
          <div class="info-item" v-if="term.grace_period_days">
            <div class="info-label">Grace Period</div>
            <div class="info-value">{{ term.grace_period_days }} days</div>
          </div>
          <div class="info-item" v-if="term.grace_period_end">
            <div class="info-label">Grace Period Ends</div>
            <div class="info-value">{{ formatDate(term.grace_period_end) }}</div>
          </div>
          <div class="info-item" v-if="term.closed_at">
            <div class="info-label">Closed At</div>
            <div class="info-value">{{ formatDateTime(term.closed_at) }}</div>
          </div>
          <div class="info-item" v-if="term.archived_at">
            <div class="info-label">Archived At</div>
            <div class="info-value">{{ formatDateTime(term.archived_at) }}</div>
          </div>
        </div>
      </MobileCard>

      <!-- Assessments Card -->
      <MobileCard variant="default" padding="md" class="q-mb-md">
        <div class="card-title">Assessments ({{ assessments.length }})</div>
        <q-list v-if="assessments.length > 0" separator>
          <q-item v-for="assessment in assessments" :key="assessment.id">
            <q-item-section avatar>
              <q-icon name="assignment" color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ assessment.name }}</q-item-label>
              <q-item-label caption>
                Type: {{ assessment.type }} | Total Marks: {{ assessment.total_marks }}
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-badge :color="assessment.is_finalized ? 'positive' : 'warning'">
                {{ assessment.is_finalized ? 'Finalized' : 'Draft' }}
              </q-badge>
            </q-item-section>
          </q-item>
        </q-list>
        <div v-else class="empty-text">No assessments for this term</div>
      </MobileCard>

      <!-- Subscriptions Card -->
      <MobileCard variant="default" padding="md" class="q-mb-md">
        <div class="card-title">Subscriptions ({{ subscriptions.length }})</div>
        <q-list v-if="subscriptions.length > 0" separator>
          <q-item v-for="subscription in subscriptions" :key="subscription.id">
            <q-item-section avatar>
              <q-icon name="payment" color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label>
                {{ subscription.student?.first_name }} {{ subscription.student?.last_name }}
              </q-item-label>
              <q-item-label caption>
                Status: {{ subscription.status }} | Amount: {{ subscription.currency }} {{ subscription.amount }}
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-badge :color="subscription.status === 'active' ? 'positive' : 'grey'">
                {{ subscription.status }}
              </q-badge>
            </q-item-section>
          </q-item>
        </q-list>
        <div v-else class="empty-text">No subscriptions for this term</div>
      </MobileCard>

      <!-- Term Lifecycle Card -->
      <MobileCard variant="default" padding="md">
        <div class="card-title">Term Lifecycle</div>
        <div class="action-buttons">
          <q-btn
            v-if="term.status === 'draft' && (authStore.isSchoolAdmin || authStore.isSuperAdmin)"
            flat
            color="positive"
            icon="play_arrow"
            label="Activate Term"
            @click="activateTerm"
            :loading="actionLoading"
            class="full-width q-mb-sm"
          />
          <q-btn
            v-if="term.status === 'active' && (authStore.isSchoolAdmin || authStore.isSuperAdmin)"
            flat
            color="warning"
            icon="schedule"
            label="Start Closing"
            @click="startClosing"
            :loading="actionLoading"
            class="full-width q-mb-sm"
          />
          <q-btn
            v-if="['active', 'closing'].includes(term.status) && (authStore.isSchoolAdmin || authStore.isSuperAdmin)"
            flat
            color="negative"
            icon="lock"
            label="Close Term"
            @click="closeTerm"
            :loading="actionLoading"
            class="full-width q-mb-sm"
          />
          <q-btn
            v-if="term.status === 'closed' && (authStore.isSchoolAdmin || authStore.isSuperAdmin)"
            flat
            color="dark"
            icon="archive"
            label="Archive Term"
            @click="archiveTerm"
            :loading="actionLoading"
            class="full-width"
          />
          <div v-if="!canPerformAction" class="empty-text">
            No actions available for this term status
          </div>
        </div>
      </MobileCard>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'src/stores/auth';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import api from 'src/services/api';

const route = useRoute();
const router = useRouter();
const $q = useQuasar();
const authStore = useAuthStore();

const termId = route.params.id;
const loading = ref(false);
const actionLoading = ref(false);
const term = ref(null);
const assessments = ref([]);
const subscriptions = ref([]);

const canEdit = computed(() => {
  return term.value && !['closed', 'archived'].includes(term.value.status);
});

const canPerformAction = computed(() => {
  if (!term.value) return false;
  const status = term.value.status;
  return (
    (status === 'draft' || status === 'active' || status === 'closing' || status === 'closed') &&
    (authStore.isSchoolAdmin || authStore.isSuperAdmin)
  );
});

const getStatusColor = (status) => {
  const colors = {
    draft: 'grey',
    active: 'positive',
    closing: 'warning',
    closed: 'negative',
    archived: 'dark',
  };
  return colors[status] || 'grey';
};

const formatStatus = (status) => {
  return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown';
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  const d = new Date(date);
  return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
};

const formatDateTime = (dateTime) => {
  if (!dateTime) return 'N/A';
  const d = new Date(dateTime);
  return d.toLocaleString('en-GB', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const fetchTerm = async () => {
  loading.value = true;
  try {
    const response = await api.get(`/terms/${termId}`);
    if (response.data.success && response.data.data) {
      term.value = response.data.data;
      assessments.value = term.value.assessments || [];
      subscriptions.value = term.value.subscriptions || [];
    }
  } catch (error) {
    console.error('Failed to fetch term:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to load term details. Please try again.',
      position: 'top',
    });
    router.push('/app/terms');
  } finally {
    loading.value = false;
  }
};

const activateTerm = async () => {
  actionLoading.value = true;
  try {
    const response = await api.post(`/terms/${termId}/activate`);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Term activated successfully',
        position: 'top',
      });
      fetchTerm();
    }
  } catch (error) {
    console.error('Failed to activate term:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to activate term. Please try again.',
      position: 'top',
    });
  } finally {
    actionLoading.value = false;
  }
};

const startClosing = async () => {
  actionLoading.value = true;
  try {
    const response = await api.post(`/terms/${termId}/start-closing`);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Term closing process started',
        position: 'top',
      });
      fetchTerm();
    }
  } catch (error) {
    console.error('Failed to start closing term:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to start closing term. Please try again.',
      position: 'top',
    });
  } finally {
    actionLoading.value = false;
  }
};

const closeTerm = async () => {
  $q.dialog({
    title: 'Confirm Close Term',
    message: 'Are you sure you want to close this term? This action cannot be undone.',
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    actionLoading.value = true;
    try {
      const response = await api.post(`/terms/${termId}/close`);
      if (response.data.success) {
        $q.notify({
          type: 'positive',
          message: 'Term closed successfully',
          position: 'top',
        });
        fetchTerm();
      }
    } catch (error) {
      console.error('Failed to close term:', error);
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to close term. Please try again.',
        position: 'top',
      });
    } finally {
      actionLoading.value = false;
    }
  });
};

const archiveTerm = async () => {
  $q.dialog({
    title: 'Confirm Archive Term',
    message: 'Are you sure you want to archive this term? This action cannot be undone.',
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    actionLoading.value = true;
    try {
      const response = await api.post(`/terms/${termId}/archive`);
      if (response.data.success) {
        $q.notify({
          type: 'positive',
          message: 'Term archived successfully',
          position: 'top',
        });
        fetchTerm();
      }
    } catch (error) {
      console.error('Failed to archive term:', error);
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to archive term. Please try again.',
        position: 'top',
      });
    } finally {
      actionLoading.value = false;
    }
  });
};

onMounted(() => {
  fetchTerm();
});
</script>

<style lang="scss" scoped>
.term-detail-page {
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
  
  @media (min-width: 768px) {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--spacing-lg);
  }
}

.info-card {
  @media (min-width: 768px) {
    grid-column: 1;
  }
}

.term-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-md);
  border-bottom: 1px solid var(--border-light);
}

.term-avatar {
  flex-shrink: 0;
}

.term-info {
  flex: 1;
}

.term-name {
  font-size: var(--font-size-xl);
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: var(--spacing-xs);
}

.term-subtitle {
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

.empty-text {
  font-size: var(--font-size-base);
  color: var(--text-secondary);
  text-align: center;
  padding: var(--spacing-md);
}

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.mobile-btn {
  @media (max-width: 599px) {
    min-width: 0;
    padding: var(--spacing-sm);
  }
}
</style>
