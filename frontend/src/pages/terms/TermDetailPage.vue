<template>
  <q-page class="q-pa-lg">
    <div class="row items-center q-mb-lg">
      <q-btn
        flat
        icon="arrow_back"
        label="Back"
        @click="router.push('/app/terms')"
        class="q-mr-md"
      />
      <div>
        <div class="text-h5 text-weight-bold">Term Details</div>
        <div class="text-body2 text-grey-7">View and manage term information</div>
      </div>
      <q-space />
      <q-btn
        v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin && canEdit"
        color="primary"
        label="Edit Term"
        icon="edit"
        unelevated
        @click="router.push(`/app/terms/${termId}/edit`)"
        class="q-mr-sm"
      />
    </div>

    <div v-if="loading" class="row justify-center q-pa-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <div v-else-if="term" class="row q-col-gutter-md">
      <!-- Left Column - Term Information -->
      <div class="col-12 col-md-8">
        <!-- Basic Information Card -->
        <q-card class="widget-card q-mb-md">
          <q-card-section>
            <div class="row items-center q-mb-md">
              <q-avatar size="80px" class="bg-primary q-mr-md">
                <q-icon name="event" size="48px" color="white" />
              </q-avatar>
              <div>
                <div class="text-h5 text-weight-bold q-mb-xs">{{ term.name }}</div>
                <div class="text-body2 text-grey-7 q-mb-xs">
                  Term {{ term.term_number }} - {{ term.academic_year?.name || 'N/A' }}
                </div>
                <q-badge
                  :color="getStatusColor(term.status)"
                  :label="formatStatus(term.status)"
                />
              </div>
            </div>

            <q-separator class="q-my-md" />

            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <div class="text-caption text-grey-7 q-mb-xs">Start Date</div>
                <div class="text-body1">{{ formatDate(term.start_date) }}</div>
              </div>
              <div class="col-12 col-sm-6">
                <div class="text-caption text-grey-7 q-mb-xs">End Date</div>
                <div class="text-body1">{{ formatDate(term.end_date) }}</div>
              </div>
              <div class="col-12 col-sm-6" v-if="term.grace_period_days">
                <div class="text-caption text-grey-7 q-mb-xs">Grace Period</div>
                <div class="text-body1">{{ term.grace_period_days }} days</div>
              </div>
              <div class="col-12 col-sm-6" v-if="term.grace_period_end">
                <div class="text-caption text-grey-7 q-mb-xs">Grace Period Ends</div>
                <div class="text-body1">{{ formatDate(term.grace_period_end) }}</div>
              </div>
              <div class="col-12 col-sm-6" v-if="term.closed_at">
                <div class="text-caption text-grey-7 q-mb-xs">Closed At</div>
                <div class="text-body1">{{ formatDateTime(term.closed_at) }}</div>
              </div>
              <div class="col-12 col-sm-6" v-if="term.archived_at">
                <div class="text-caption text-grey-7 q-mb-xs">Archived At</div>
                <div class="text-body1">{{ formatDateTime(term.archived_at) }}</div>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Assessments Card -->
        <q-card class="widget-card q-mb-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">Assessments ({{ assessments.length }})</div>
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
            <div v-else class="text-body2 text-grey-7">No assessments for this term</div>
          </q-card-section>
        </q-card>

        <!-- Subscriptions Card -->
        <q-card class="widget-card">
          <q-card-section>
            <div class="text-h6 q-mb-md">Subscriptions ({{ subscriptions.length }})</div>
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
            <div v-else class="text-body2 text-grey-7">No subscriptions for this term</div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Right Column - Lifecycle Actions -->
      <div class="col-12 col-md-4">
        <q-card class="widget-card">
          <q-card-section>
            <div class="text-h6 q-mb-md">Term Lifecycle</div>
            <div class="q-gutter-sm">
              <q-btn
                v-if="term.status === 'draft' && (authStore.isSchoolAdmin || authStore.isSuperAdmin)"
                flat
                color="positive"
                icon="play_arrow"
                label="Activate Term"
                @click="activateTerm"
                :loading="actionLoading"
                class="full-width"
              />
              <q-btn
                v-if="term.status === 'active' && (authStore.isSchoolAdmin || authStore.isSuperAdmin)"
                flat
                color="warning"
                icon="schedule"
                label="Start Closing"
                @click="startClosing"
                :loading="actionLoading"
                class="full-width"
              />
              <q-btn
                v-if="['active', 'closing'].includes(term.status) && (authStore.isSchoolAdmin || authStore.isSuperAdmin)"
                flat
                color="negative"
                icon="lock"
                label="Close Term"
                @click="closeTerm"
                :loading="actionLoading"
                class="full-width"
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
              <div v-if="!canPerformAction" class="text-body2 text-grey-7 text-center q-pa-md">
                No actions available for this term status
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'src/stores/auth';
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
.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}
</style>
