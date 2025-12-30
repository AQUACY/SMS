<template>
  <q-page class="class-detail-page">
    <MobilePageHeader
      title="Class Details"
      subtitle="View and manage class information"
      :show-back="true"
      @back="router.push('/app/classes')"
    >
      <template v-slot:actions>
        <q-btn
          v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
          color="primary"
          :label="$q.screen.gt.xs ? 'Edit Class' : ''"
          icon="edit"
          unelevated
          @click="router.push(`/app/classes/${classId}/edit`)"
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

    <div v-else-if="classData" class="detail-content">
      <!-- Basic Information Card -->
      <MobileCard variant="default" padding="lg" class="info-card q-mb-md">
        <div class="class-header">
          <q-avatar size="80px" class="class-avatar bg-primary">
            <q-icon name="class" size="48px" color="white" />
          </q-avatar>
          <div class="class-info">
            <div class="class-name">{{ classData.name }}</div>
            <div class="class-level">
              Level: {{ classData.level }} {{ classData.section ? `- ${classData.section}` : '' }}
            </div>
            <q-badge
              :color="classData.is_active ? 'positive' : 'negative'"
              :label="classData.is_active ? 'Active' : 'Inactive'"
              class="q-mt-xs"
            />
          </div>
        </div>

            <q-separator class="q-my-md" />

            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <div class="text-caption text-grey-7 q-mb-xs">Capacity</div>
                <div class="text-body1">{{ classData.capacity }} students</div>
              </div>
              <div class="col-12 col-sm-6">
                <div class="text-caption text-grey-7 q-mb-xs">Academic Year</div>
                <div class="text-body1">{{ classData.academic_year?.name || 'Not assigned' }}</div>
              </div>
              <div class="col-12 col-sm-6">
                <div class="text-caption text-grey-7 q-mb-xs">Class Teacher</div>
                <div class="text-body1">
                  <span v-if="classData.class_teacher && classData.class_teacher.user">
                    {{ getTeacherName(classData.class_teacher) }}
                  </span>
                  <span v-else class="text-grey-6">Not assigned</span>
                </div>
              </div>
              <div class="col-12 col-sm-6">
                <div class="text-caption text-grey-7 q-mb-xs">Current Students</div>
                <div class="text-body1">{{ students.length }} enrolled</div>
              </div>
            </div>
      </MobileCard>

      <!-- Students Card -->
      <MobileCard variant="default" padding="md" class="q-mb-md">
        <div class="card-title">Students ({{ students.length }})</div>
            <q-list v-if="students.length > 0" separator>
              <q-item
                v-for="student in students"
                :key="student.id"
                clickable
                @click="router.push(`/app/students/${student.id}`)"
              >
                <q-item-section avatar>
                  <q-avatar size="40px" class="bg-primary">
                    <q-icon name="person" color="white" />
                  </q-avatar>
                </q-item-section>
                <q-item-section>
                  <q-item-label>
                    {{ getStudentName(student) }}
                  </q-item-label>
                  <q-item-label caption>
                    {{ student.student_number }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-icon name="chevron_right" color="grey-6" />
                </q-item-section>
              </q-item>
            </q-list>
        <div v-else class="empty-text">No students enrolled</div>
      </MobileCard>

      <!-- Subjects Card -->
      <MobileCard variant="default" padding="md" class="q-mb-md">
        <div class="card-title">Subjects ({{ subjects.length }})</div>
            <q-list v-if="subjects.length > 0" separator>
              <q-item v-for="subject in subjects" :key="subject.id">
                <q-item-section avatar>
                  <q-icon name="book" color="primary" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ subject.subject?.name || 'Unknown Subject' }}</q-item-label>
                  <q-item-label caption>
                    <span v-if="subject.teacher && subject.teacher.user">
                      Teacher: {{ getTeacherName(subject.teacher) }}
                    </span>
                    <span v-else>No teacher assigned</span>
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
        <div v-else class="empty-text">No subjects assigned</div>
      </MobileCard>

      <!-- Quick Actions Card -->
      <MobileCard variant="default" padding="md">
        <div class="card-title">Quick Actions</div>
            <div class="q-gutter-sm">
              <q-btn
                flat
                color="primary"
                icon="people"
                label="View All Students"
                @click="router.push(`/app/classes/${classId}/students`)"
                class="full-width"
              />
              <q-btn
                flat
                color="primary"
                icon="book"
                label="View Subjects"
                @click="router.push(`/app/classes/${classId}/subjects`)"
                class="full-width"
              />
              <q-btn
                flat
                color="primary"
                icon="event"
                label="View Attendance"
                @click="router.push(`/app/attendance?class_id=${classId}`)"
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

const classId = route.params.id;
const loading = ref(false);
const classData = ref(null);
const students = ref([]);
const subjects = ref([]);

const getTeacherName = (teacher) => {
  if (teacher && teacher.user) {
    return `${teacher.user.first_name} ${teacher.user.last_name}`;
  }
  return 'Unknown';
};

const getStudentName = (student) => {
  const parts = [student.first_name, student.middle_name, student.last_name].filter(Boolean);
  return parts.join(' ') || 'Unknown';
};

const fetchClass = async () => {
  loading.value = true;
  try {
    const response = await api.get(`/classes/${classId}`);
    if (response.data.success && response.data.data) {
      classData.value = response.data.data;
      
      // Fetch students
      const studentsResponse = await api.get(`/classes/${classId}/students`);
      if (studentsResponse.data.success) {
        students.value = studentsResponse.data.data || [];
      }
      
      // Fetch subjects
      const subjectsResponse = await api.get(`/classes/${classId}/subjects`);
      if (subjectsResponse.data.success) {
        subjects.value = subjectsResponse.data.data || [];
      }
    }
  } catch (error) {
    console.error('Failed to fetch class:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to load class details. Please try again.',
      position: 'top',
    });
    router.push('/app/classes');
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchClass();
});
</script>

<style lang="scss" scoped>
.class-detail-page {
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

.class-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-md);
  border-bottom: 1px solid var(--border-light);
}

.class-avatar {
  flex-shrink: 0;
}

.class-info {
  flex: 1;
}

.class-name {
  font-size: var(--font-size-xl);
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: var(--spacing-xs);
}

.class-level {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  margin-bottom: var(--spacing-xs);
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
