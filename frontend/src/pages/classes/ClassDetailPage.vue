<template>
  <q-page class="q-pa-lg">
    <div class="row items-center q-mb-lg">
      <q-btn
        flat
        icon="arrow_back"
        label="Back"
        @click="router.push('/app/classes')"
        class="q-mr-md"
      />
      <div>
        <div class="text-h5 text-weight-bold">Class Details</div>
        <div class="text-body2 text-grey-7">View and manage class information</div>
      </div>
      <q-space />
      <q-btn
        v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
        color="primary"
        label="Edit Class"
        icon="edit"
        unelevated
        @click="router.push(`/app/classes/${classId}/edit`)"
      />
    </div>

    <div v-if="loading" class="row justify-center q-pa-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <div v-else-if="classData" class="row q-col-gutter-md">
      <!-- Left Column - Class Information -->
      <div class="col-12 col-md-8">
        <!-- Basic Information Card -->
        <q-card class="widget-card q-mb-md">
          <q-card-section>
            <div class="row items-center q-mb-md">
              <q-avatar size="80px" class="bg-primary q-mr-md">
                <q-icon name="class" size="48px" color="white" />
              </q-avatar>
              <div>
                <div class="text-h5 text-weight-bold q-mb-xs">{{ classData.name }}</div>
                <div class="text-body2 text-grey-7 q-mb-xs">
                  Level: {{ classData.level }} {{ classData.section ? `- ${classData.section}` : '' }}
                </div>
                <q-badge
                  :color="classData.is_active ? 'positive' : 'negative'"
                  :label="classData.is_active ? 'Active' : 'Inactive'"
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
          </q-card-section>
        </q-card>

        <!-- Students Card -->
        <q-card class="widget-card q-mb-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">Students ({{ students.length }})</div>
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
            <div v-else class="text-body2 text-grey-7">No students enrolled</div>
          </q-card-section>
        </q-card>

        <!-- Subjects Card -->
        <q-card class="widget-card">
          <q-card-section>
            <div class="text-h6 q-mb-md">Subjects ({{ subjects.length }})</div>
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
            <div v-else class="text-body2 text-grey-7">No subjects assigned</div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Right Column - Quick Actions -->
      <div class="col-12 col-md-4">
        <q-card class="widget-card">
          <q-card-section>
            <div class="text-h6 q-mb-md">Quick Actions</div>
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
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'src/stores/auth';
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
.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}
</style>
