<template>
  <q-page class="q-pa-lg">
    <div class="row items-center q-mb-lg">
      <q-btn
        flat
        icon="arrow_back"
        label="Back"
        @click="router.push('/app/subjects')"
        class="q-mr-md"
      />
      <div>
        <div class="text-h5 text-weight-bold">Subject Details</div>
        <div class="text-body2 text-grey-7">View and manage subject information</div>
      </div>
      <q-space />
      <q-btn
        v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
        color="primary"
        label="Edit Subject"
        icon="edit"
        unelevated
        @click="router.push(`/app/subjects/${subjectId}/edit`)"
      />
    </div>

    <div v-if="loading" class="row justify-center q-pa-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <div v-else-if="subject" class="row q-col-gutter-md">
      <!-- Left Column - Subject Information -->
      <div class="col-12 col-md-8">
        <!-- Basic Information Card -->
        <q-card class="widget-card q-mb-md">
          <q-card-section>
            <div class="row items-center q-mb-md">
              <q-avatar size="80px" class="bg-primary q-mr-md">
                <q-icon name="book" size="48px" color="white" />
              </q-avatar>
              <div>
                <div class="text-h5 text-weight-bold q-mb-xs">{{ subject.name }}</div>
                <div class="text-body2 text-grey-7 q-mb-xs" v-if="subject.code">
                  Code: {{ subject.code }}
                </div>
                <q-badge
                  :color="subject.is_core ? 'primary' : 'grey'"
                  :label="subject.is_core ? 'Core Subject' : 'Elective Subject'"
                />
              </div>
            </div>

            <q-separator class="q-my-md" />

            <div class="row q-col-gutter-md">
              <div class="col-12">
                <div class="text-caption text-grey-7 q-mb-xs">Description</div>
                <div class="text-body1">{{ subject.description || 'No description provided' }}</div>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Classes Card -->
        <q-card class="widget-card">
          <q-card-section>
            <div class="text-h6 q-mb-md">Assigned Classes ({{ classes.length }})</div>
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
            <div v-else class="text-body2 text-grey-7">Not assigned to any classes</div>
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
                icon="class"
                label="View All Classes"
                class="full-width"
                @click="router.push('/app/classes')"
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
.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}
</style>

