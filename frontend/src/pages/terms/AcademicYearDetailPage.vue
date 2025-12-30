<template>
  <q-page class="academic-year-detail-page">
    <MobilePageHeader
      title="Academic Year Details"
      subtitle="View and manage academic year information"
      :show-back="true"
      @back="router.push('/app/academic-years')"
    >
      <template v-slot:actions>
        <q-btn
          v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
          color="primary"
          :label="$q.screen.gt.xs ? 'Edit' : ''"
          icon="edit"
          unelevated
          @click="router.push(`/app/academic-years/${academicYearId}/edit`)"
          class="mobile-btn q-mr-xs"
        />
        <q-btn
          v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin && !academicYear?.is_active"
          color="positive"
          :label="$q.screen.gt.xs ? 'Activate' : ''"
          icon="play_arrow"
          unelevated
          @click="activateAcademicYear"
          :loading="actionLoading"
          class="mobile-btn"
        />
      </template>
    </MobilePageHeader>

    <div v-if="loading" class="detail-loading">
      <MobileCard v-for="i in 2" :key="i" variant="default" padding="md" class="q-mb-md">
        <q-skeleton type="rect" height="100px" class="q-mb-md" />
        <q-skeleton type="text" width="60%" />
        <q-skeleton type="text" width="40%" />
      </MobileCard>
    </div>

    <div v-else-if="academicYear" class="detail-content">
      <!-- Basic Information Card -->
      <MobileCard variant="default" padding="lg" class="info-card q-mb-md">
        <div class="year-header">
          <q-avatar size="80px" class="year-avatar bg-primary">
            <q-icon name="calendar_today" size="48px" color="white" />
          </q-avatar>
          <div class="year-info">
            <div class="year-name">{{ academicYear.name }}</div>
            <q-badge
              :color="academicYear.is_active ? 'positive' : 'grey'"
              :label="academicYear.is_active ? 'Active' : 'Inactive'"
              class="q-mt-xs"
            />
          </div>
        </div>

        <q-separator class="q-my-md" />

        <div class="info-grid">
          <div class="info-item">
            <div class="info-label">Start Date</div>
            <div class="info-value">{{ formatDate(academicYear.start_date) }}</div>
          </div>
          <div class="info-item">
            <div class="info-label">End Date</div>
            <div class="info-value">{{ formatDate(academicYear.end_date) }}</div>
          </div>
        </div>
      </MobileCard>

      <!-- Terms Card -->
      <MobileCard variant="default" padding="md" class="q-mb-md">
        <div class="card-title">Terms ({{ terms.length }})</div>
        <q-list v-if="terms.length > 0" separator>
          <q-item
            v-for="term in terms"
            :key="term.id"
            clickable
            @click="router.push(`/app/terms/${term.id}`)"
          >
            <q-item-section avatar>
              <q-icon name="event" color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label>{{ term.name }}</q-item-label>
              <q-item-label caption>
                Term {{ term.term_number }} | {{ formatDate(term.start_date) }} - {{ formatDate(term.end_date) }}
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-badge :color="getStatusColor(term.status)">
                {{ formatStatus(term.status) }}
              </q-badge>
            </q-item-section>
            <q-item-section side>
              <q-icon name="chevron_right" color="grey-6" />
            </q-item-section>
          </q-item>
        </q-list>
        <div v-else class="empty-text">No terms for this academic year</div>
      </MobileCard>

      <!-- Quick Actions Card -->
      <MobileCard variant="default" padding="md">
        <div class="card-title">Quick Actions</div>
        <div class="action-buttons">
          <q-btn
            flat
            color="primary"
            icon="event"
            label="Add Term"
            @click="router.push(`/app/terms/create?academic_year_id=${academicYearId}`)"
            class="full-width q-mb-sm"
          />
          <q-btn
            flat
            color="primary"
            icon="list"
            label="View All Terms"
            @click="router.push(`/app/terms?academic_year_id=${academicYearId}`)"
            class="full-width"
          />
        </div>
      </MobileCard>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
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

const academicYearId = route.params.id;
const loading = ref(false);
const actionLoading = ref(false);
const academicYear = ref(null);
const terms = ref([]);

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

const fetchAcademicYear = async () => {
  loading.value = true;
  try {
    const response = await api.get(`/academic-years/${academicYearId}`);
    if (response.data.success && response.data.data) {
      academicYear.value = response.data.data;
      terms.value = academicYear.value.terms || [];
    }
  } catch (error) {
    console.error('Failed to fetch academic year:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to load academic year details. Please try again.',
      position: 'top',
    });
    router.push('/app/academic-years');
  } finally {
    loading.value = false;
  }
};

const activateAcademicYear = async () => {
  actionLoading.value = true;
  try {
    const response = await api.post(`/academic-years/${academicYearId}/activate`);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Academic year activated successfully',
        position: 'top',
      });
      fetchAcademicYear();
    }
  } catch (error) {
    console.error('Failed to activate academic year:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to activate academic year. Please try again.',
      position: 'top',
    });
  } finally {
    actionLoading.value = false;
  }
};

onMounted(() => {
  fetchAcademicYear();
});
</script>

<style lang="scss" scoped>
.academic-year-detail-page {
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

.year-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-md);
  border-bottom: 1px solid var(--border-light);
}

.year-avatar {
  flex-shrink: 0;
}

.year-info {
  flex: 1;
}

.year-name {
  font-size: var(--font-size-xl);
  font-weight: 700;
  color: var(--text-primary);
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

