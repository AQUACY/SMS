<template>
  <q-page class="subject-detail-page">
    <MobilePageHeader
      title="Subject Details"
      subtitle="View and manage subject information"
      :show-back="true"
      @back="router.push('/app/subjects')"
    >
      <template v-slot:actions>
        <q-btn
          v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
          color="primary"
          :label="$q.screen.gt.xs ? 'Edit Subject' : ''"
          icon="edit"
          unelevated
          @click="router.push(`/app/subjects/${subjectId}/edit`)"
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

    <div v-else-if="subject" class="detail-content">
      <!-- Basic Information Card -->
      <MobileCard variant="default" padding="lg" class="info-card q-mb-md">
        <div class="subject-header">
          <q-avatar size="80px" class="subject-avatar bg-primary">
            <q-icon name="book" size="48px" color="white" />
          </q-avatar>
          <div class="subject-info">
            <div class="subject-name">{{ subject.name }}</div>
            <div v-if="subject.code" class="subject-code">Code: {{ subject.code }}</div>
            <q-badge
              :color="subject.is_core ? 'primary' : 'grey'"
              :label="subject.is_core ? 'Core Subject' : 'Elective Subject'"
              class="q-mt-xs"
            />
          </div>
        </div>

        <q-separator class="q-my-md" />

        <div class="description-section">
          <div class="info-label">Description</div>
          <div class="info-value">{{ subject.description || 'No description provided' }}</div>
        </div>
      </MobileCard>

      <!-- Classes Card -->
      <MobileCard variant="default" padding="md" class="q-mb-md">
        <div class="card-title">Assigned Classes ({{ classes.length }})</div>
        <q-list v-if="classes.length > 0" separator>
          <q-item
            v-for="classSubject in classes"
            :key="classSubject.id"
            clickable
            @click="router.push(`/app/classes/${classSubject.class_id}`)"
          >
            <q-item-section avatar>
              <q-icon name="class" color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label>
                {{ classSubject.class?.name || 'Unknown Class' }}
              </q-item-label>
              <q-item-label caption>
                <span v-if="classSubject.teacher && classSubject.teacher.user">
                  Teacher: {{ getTeacherName(classSubject.teacher) }}
                </span>
                <span v-else>No teacher assigned</span>
              </q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-icon name="chevron_right" color="grey-6" />
            </q-item-section>
          </q-item>
        </q-list>
        <div v-else class="empty-text">Not assigned to any classes</div>
      </MobileCard>

      <!-- Quick Actions Card -->
      <MobileCard variant="default" padding="md">
        <div class="card-title">Quick Actions</div>
        <q-btn
          flat
          color="primary"
          icon="class"
          label="View All Classes"
          class="full-width"
          @click="router.push('/app/classes')"
        />
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

const subjectId = route.params.id;
const loading = ref(false);
const subject = ref(null);
const classes = ref([]);

const getTeacherName = (teacher) => {
  if (teacher && teacher.user) {
    return `${teacher.user.first_name} ${teacher.user.last_name}`;
  }
  return 'Unknown';
};

const fetchSubject = async () => {
  loading.value = true;
  try {
    const response = await api.get(`/subjects/${subjectId}`);
    if (response.data.success && response.data.data) {
      subject.value = response.data.data;
      
      // Get classes from classSubjects relationship
      if (subject.value.class_subjects) {
        classes.value = subject.value.class_subjects || [];
      }
    }
  } catch (error) {
    console.error('Failed to fetch subject:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to load subject details. Please try again.',
      position: 'top',
    });
    router.push('/app/subjects');
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchSubject();
});
</script>

<style lang="scss" scoped>
.subject-detail-page {
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

.subject-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-md);
  border-bottom: 1px solid var(--border-light);
}

.subject-avatar {
  flex-shrink: 0;
}

.subject-info {
  flex: 1;
}

.subject-name {
  font-size: var(--font-size-xl);
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: var(--spacing-xs);
}

.subject-code {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  margin-bottom: var(--spacing-xs);
}

.description-section {
  margin-top: var(--spacing-md);
}

.info-label {
  font-size: var(--font-size-xs);
  color: var(--text-secondary);
  margin-bottom: var(--spacing-xs);
}

.info-value {
  font-size: var(--font-size-base);
  color: var(--text-primary);
  line-height: 1.6;
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

.mobile-btn {
  @media (max-width: 599px) {
    min-width: 0;
    padding: var(--spacing-sm);
  }
}
</style>

