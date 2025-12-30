<template>
  <q-page class="class-subjects-page">
    <MobilePageHeader
      :title="classData?.name || 'Class Subjects'"
      :subtitle="`${classData?.academic_year?.name || ''} - ${subjects.length} subjects`"
      :show-back="true"
      @back="router.push(`/app/classes/${classId}`)"
    >
      <template #actions>
        <q-btn
          v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
          flat
          round
          dense
          icon="add"
          color="primary"
          @click="showAddDialog = true"
        >
          <q-tooltip>Add Subject</q-tooltip>
        </q-btn>
      </template>
    </MobilePageHeader>

    <div class="page-content">
      <MobileCard variant="default" padding="md">
        <div class="card-title">Subjects List</div>
        
        <!-- Mobile View: Card List -->
        <div class="mobile-only">
          <div v-if="loading" class="text-center q-pa-xl">
            <q-spinner color="primary" size="3em" />
          </div>
          <div v-else-if="subjects.length === 0" class="empty-state">
            <q-icon name="book" size="64px" color="grey-5" />
            <div class="empty-text">No Subjects</div>
            <div class="empty-subtext">No subjects assigned to this class.</div>
          </div>
          <div v-else class="subjects-list">
            <MobileListCard
              v-for="subject in subjects"
              :key="subject.id"
              :title="subject.subject?.name || 'Unknown Subject'"
              :subtitle="subject.teacher && subject.teacher.user ? `Teacher: ${getTeacherName(subject.teacher)}` : 'No teacher assigned'"
              icon="book"
              :clickable="true"
              @click="viewSubject(subject.subject?.id)"
            >
              <template #extra>
                <div class="card-actions">
                  <q-btn
                    flat
                    dense
                    round
                    icon="visibility"
                    size="sm"
                    color="primary"
                    @click.stop="viewSubject(subject.subject?.id)"
                    class="q-mr-xs"
                  />
                  <q-btn
                    v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
                    flat
                    dense
                    round
                    icon="delete"
                    size="sm"
                    color="negative"
                    @click.stop="removeSubject(subject.id)"
                  />
                </div>
              </template>
            </MobileListCard>
          </div>
        </div>

        <!-- Desktop View: List -->
        <div class="desktop-only">
          <q-list v-if="subjects.length > 0" separator>
          <q-item v-for="subject in subjects" :key="subject.id">
            <q-item-section avatar>
              <q-icon name="book" color="primary" size="md" />
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
            <q-item-section side>
              <q-btn
                flat
                dense
                icon="visibility"
                color="primary"
                @click="viewSubject(subject.subject?.id)"
                class="q-mr-xs"
              />
              <q-btn
                v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
                flat
                dense
                icon="delete"
                color="negative"
                @click="removeSubject(subject.id)"
              />
            </q-item-section>
          </q-item>
          </q-list>
          <div v-else class="text-body2 text-grey-7 text-center q-pa-lg">
            No subjects assigned to this class
          </div>
        </div>
      </MobileCard>
    </div>

    <!-- Add Subject Dialog -->
    <q-dialog v-model="showAddDialog" persistent>
      <q-card style="min-width: 500px">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Add Subject to Class</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <q-form @submit="onAssignSubject" class="q-gutter-md">
            <q-select
              v-model="form.subject_id"
              :options="availableSubjects"
              option-label="name"
              option-value="id"
              emit-value
              map-options
              label="Select Subject *"
              outlined
              :rules="[(val) => !!val || 'Subject is required']"
              :loading="loadingSubjects"
            >
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section>
                    <q-item-label>{{ scope.opt.name }}</q-item-label>
                    <q-item-label caption>{{ scope.opt.code || 'No code' }}</q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-badge
                      :color="scope.opt.is_core ? 'primary' : 'grey'"
                      :label="scope.opt.is_core ? 'Core' : 'Elective'"
                    />
                  </q-item-section>
                </q-item>
              </template>
            </q-select>

            <q-select
              v-model="form.teacher_id"
              :options="teachers"
              option-label="name"
              option-value="id"
              emit-value
              map-options
              label="Assign Teacher (Optional)"
              outlined
              clearable
              :loading="loadingTeachers"
              hint="You can assign a teacher now or later"
            />

            <q-card-actions align="right" class="q-pt-md">
              <q-btn flat label="Cancel" v-close-popup />
              <q-btn color="primary" label="Assign Subject" type="submit" :loading="assigning" />
            </q-card-actions>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'src/stores/auth';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import MobileListCard from 'src/components/mobile/MobileListCard.vue';
import api from 'src/services/api';

const route = useRoute();
const router = useRouter();
const $q = useQuasar();
const authStore = useAuthStore();

const classId = route.params.id;
const loading = ref(false);
const loadingSubjects = ref(false);
const loadingTeachers = ref(false);
const assigning = ref(false);
const classData = ref(null);
const subjects = ref([]);
const availableSubjects = ref([]);
const teachers = ref([]);
const showAddDialog = ref(false);

const form = ref({
  subject_id: null,
  teacher_id: null,
});

const getTeacherName = (teacher) => {
  if (teacher && teacher.user) {
    return `${teacher.user.first_name} ${teacher.user.last_name}`;
  }
  return 'Unknown';
};

const fetchClass = async () => {
  try {
    const response = await api.get(`/classes/${classId}`);
    if (response.data.success && response.data.data) {
      classData.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to fetch class:', error);
  }
};

const fetchSubjects = async () => {
  loading.value = true;
  try {
    const response = await api.get(`/classes/${classId}/subjects`);
    if (response.data.success) {
      subjects.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch subjects:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to load subjects. Please try again.',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
};

const fetchAvailableSubjects = async () => {
  loadingSubjects.value = true;
  try {
    const response = await api.get('/subjects', {
      params: { per_page: 1000 },
    });
    if (response.data.success && response.data.data) {
      // Filter out subjects that are already assigned to this class
      const assignedSubjectIds = subjects.value.map((s) => s.subject?.id).filter(Boolean);
      availableSubjects.value = (response.data.data || []).filter(
        (subject) => !assignedSubjectIds.includes(subject.id)
      );
    }
  } catch (error) {
    console.error('Failed to fetch subjects:', error);
  } finally {
    loadingSubjects.value = false;
  }
};

const fetchTeachers = async () => {
  loadingTeachers.value = true;
  try {
    const response = await api.get('/teachers', {
      params: { per_page: 100 },
    });
    if (response.data.success && response.data.data) {
      teachers.value = (response.data.data || [])
        .filter((teacher) => teacher.user?.is_active !== false)
        .map((teacher) => ({
          id: teacher.id,
          name: teacher.user
            ? `${teacher.user.first_name || ''} ${teacher.user.last_name || ''}`.trim()
            : `Teacher #${teacher.id}`,
        }));
    }
  } catch (error) {
    console.error('Failed to fetch teachers:', error);
  } finally {
    loadingTeachers.value = false;
  }
};

const onAssignSubject = async () => {
  assigning.value = true;
  try {
    const response = await api.post(`/classes/${classId}/subjects`, form.value);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Subject assigned successfully',
        position: 'top',
      });
      showAddDialog.value = false;
      form.value = {
        subject_id: null,
        teacher_id: null,
      };
      fetchSubjects();
      fetchAvailableSubjects();
    }
  } catch (error) {
    console.error('Failed to assign subject:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to assign subject. Please try again.',
      position: 'top',
    });
  } finally {
    assigning.value = false;
  }
};

const removeSubject = async (classSubjectId) => {
  $q.dialog({
    title: 'Confirm Removal',
    message: 'Are you sure you want to remove this subject from the class?',
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      const response = await api.delete(`/classes/${classId}/subjects/${classSubjectId}`);
      if (response.data.success) {
        $q.notify({
          type: 'positive',
          message: 'Subject removed successfully',
          position: 'top',
        });
        fetchSubjects();
        fetchAvailableSubjects();
      }
    } catch (error) {
      console.error('Failed to remove subject:', error);
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to remove subject. Please try again.',
        position: 'top',
      });
    }
  });
};

const viewSubject = (id) => {
  if (id) {
    router.push(`/app/subjects/${id}`);
  }
};

onMounted(() => {
  fetchClass();
  fetchSubjects();
  fetchAvailableSubjects();
  fetchTeachers();
});
</script>

<style lang="scss" scoped>
.class-subjects-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.mobile-only {
  display: block;
  
  @media (min-width: 768px) {
    display: none;
  }
}

.desktop-only {
  display: none;
  
  @media (min-width: 768px) {
    display: block;
  }
}

.page-content {
  max-width: 1200px;
  margin: 0 auto;
}

.card-title {
  font-size: var(--font-size-lg);
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: var(--spacing-md);
}

.subjects-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.card-actions {
  display: flex;
  gap: var(--spacing-xs);
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
}
</style>

