<template>
  <q-page class="teacher-detail-page">
    <MobilePageHeader
      title="Teacher Details"
      subtitle="View and manage teacher information"
      :show-back="true"
      @back="router.push('/app/teachers')"
    >
      <template v-slot:actions>
        <q-btn
          v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
          color="primary"
          :label="$q.screen.gt.xs ? 'Edit Teacher' : ''"
          icon="edit"
          unelevated
          @click="router.push(`/app/teachers/${teacherId}/edit`)"
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

    <div v-else-if="teacher" class="detail-content">
      <!-- Basic Information Card -->
      <MobileCard variant="default" padding="lg" class="info-card q-mb-md">
        <div class="teacher-header">
          <q-avatar size="80px" class="teacher-avatar bg-primary">
            <q-icon name="person" size="48px" color="white" />
          </q-avatar>
          <div class="teacher-info">
            <div class="teacher-name">{{ getTeacherFullName(teacher) }}</div>
            <div class="teacher-id">Staff ID: {{ teacher.staff_number || 'Not assigned' }}</div>
            <q-badge
              :color="teacher.user?.is_active ? 'positive' : 'negative'"
              :label="teacher.user?.is_active ? 'Active' : 'Inactive'"
              class="q-mt-xs"
            />
          </div>
        </div>

            <q-separator class="q-my-md" />

            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <div class="text-caption text-grey-7 q-mb-xs">Email</div>
                <div class="text-body1">{{ teacher.user?.email || 'Not provided' }}</div>
              </div>
              <div class="col-12 col-sm-6">
                <div class="text-caption text-grey-7 q-mb-xs">Hire Date</div>
                <div class="text-body1">{{ formatDate(teacher.hire_date) }}</div>
              </div>
              <div class="col-12 col-sm-6">
                <div class="text-caption text-grey-7 q-mb-xs">Qualification</div>
                <div class="text-body1">{{ teacher.qualification || 'Not provided' }}</div>
              </div>
              <div class="col-12 col-sm-6">
                <div class="text-caption text-grey-7 q-mb-xs">Specialization</div>
                <div class="text-body1">{{ teacher.specialization || 'Not provided' }}</div>
              </div>
            </div>
      </MobileCard>

      <!-- Classes Card -->
      <MobileCard variant="default" padding="md" class="q-mb-md">
        <div class="row items-center justify-between q-mb-md">
          <div class="card-title">Assigned Classes</div>
              <q-btn
                v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
                flat
                dense
                round
                icon="add"
                color="primary"
                @click="showAssignClassDialog = true"
              />
            </div>
            <q-list v-if="teacher.classes && teacher.classes.length > 0" separator>
              <q-item v-for="classItem in teacher.classes" :key="classItem.id">
                <q-item-section avatar>
                  <q-icon name="class" color="primary" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ classItem.name || 'Unknown Class' }}</q-item-label>
                  <q-item-label caption>
                    {{ classItem.academic_year?.name || 'Unknown Year' }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <div class="row items-center q-gutter-sm">
                    <q-badge :color="classItem.is_active ? 'positive' : 'grey'">
                      {{ classItem.is_active ? 'Active' : 'Inactive' }}
                    </q-badge>
                    <q-btn
                      v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
                      flat
                      dense
                      round
                      icon="close"
                      color="negative"
                      size="sm"
                      @click="removeClass(classItem.id)"
                    />
                  </div>
                </q-item-section>
              </q-item>
            </q-list>
        <div v-else class="empty-text">No classes assigned</div>
      </MobileCard>

      <!-- Subjects Card -->
      <MobileCard variant="default" padding="md" class="q-mb-md">
        <div class="row items-center justify-between q-mb-md">
          <div class="card-title">Assigned Subjects</div>
              <q-btn
                v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
                flat
                dense
                round
                icon="add"
                color="primary"
                @click="showAssignSubjectDialog = true"
              />
            </div>
            <q-list v-if="teacher.class_subjects && teacher.class_subjects.length > 0" separator>
              <q-item v-for="classSubject in teacher.class_subjects" :key="classSubject.id">
                <q-item-section avatar>
                  <q-icon name="book" color="primary" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ classSubject.subject?.name || 'Unknown Subject' }}</q-item-label>
                  <q-item-label caption>
                    Class: {{ classSubject.class?.name || 'Unknown Class' }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-btn
                    v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
                    flat
                    dense
                    round
                    icon="close"
                    color="negative"
                    size="sm"
                    @click="removeSubject(classSubject.id)"
                  />
                </q-item-section>
              </q-item>
            </q-list>
        <div v-else class="empty-text">No subjects assigned</div>
      </MobileCard>

      <!-- Quick Actions Card -->
      <MobileCard variant="default" padding="md">
        <div class="card-title">Quick Actions</div>
            <q-list>
              <q-item clickable @click="router.push(`/app/classes?teacher=${teacherId}`)">
                <q-item-section avatar>
                  <q-icon name="class" color="primary" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>View Classes</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-icon name="chevron_right" />
                </q-item-section>
              </q-item>
              <q-item clickable @click="router.push(`/app/subjects?teacher=${teacherId}`)">
                <q-item-section avatar>
                  <q-icon name="book" color="primary" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>View Subjects</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-icon name="chevron_right" />
                </q-item-section>
              </q-item>
              <q-item clickable @click="router.push(`/app/assessments?teacher=${teacherId}`)">
                <q-item-section avatar>
                  <q-icon name="quiz" color="primary" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>View Assessments</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-icon name="chevron_right" />
                </q-item-section>
              </q-item>
            </q-list>
      </MobileCard>
    </div>

    <!-- Assign Class Dialog -->
    <q-dialog v-model="showAssignClassDialog" persistent>
      <q-card style="min-width: 500px">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Assign Class</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <q-select
            v-model="selectedClassId"
            :options="classOptions"
            label="Select Class"
            outlined
            emit-value
            map-options
            :loading="loadingClasses"
            hint="Select a class to assign this teacher as class teacher"
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" color="grey" v-close-popup />
          <q-btn
            color="primary"
            label="Assign Class"
            @click="assignClass"
            :loading="assigningClass"
            :disable="!selectedClassId"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Assign Subject Dialog -->
    <q-dialog v-model="showAssignSubjectDialog" persistent>
      <q-card style="min-width: 500px">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Assign Subject</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <q-select
            v-model="selectedClassIdForSubject"
            :options="classOptions"
            label="Select Class *"
            outlined
            emit-value
            map-options
            :loading="loadingClasses"
            :rules="[val => !!val || 'Class is required']"
            class="q-mb-md"
          />

          <q-select
            v-model="selectedSubjectId"
            :options="subjectOptions"
            label="Select Subject *"
            outlined
            emit-value
            map-options
            :loading="loadingSubjects"
            :rules="[val => !!val || 'Subject is required']"
            class="q-mb-md"
          />

          <q-select
            v-model="selectedAcademicYearId"
            :options="academicYearOptions"
            label="Select Academic Year *"
            outlined
            emit-value
            map-options
            :loading="loadingAcademicYears"
            :rules="[val => !!val || 'Academic year is required']"
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" color="grey" v-close-popup />
          <q-btn
            color="primary"
            label="Assign Subject"
            @click="assignSubject"
            :loading="assigningSubject"
            :disable="!selectedClassIdForSubject || !selectedSubjectId || !selectedAcademicYearId"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
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

const teacherId = computed(() => route.params.id);
const loading = ref(false);
const teacher = ref(null);
const showAssignClassDialog = ref(false);
const showAssignSubjectDialog = ref(false);
const selectedClassId = ref(null);
const selectedClassIdForSubject = ref(null);
const selectedSubjectId = ref(null);
const selectedAcademicYearId = ref(null);
const loadingClasses = ref(false);
const loadingSubjects = ref(false);
const loadingAcademicYears = ref(false);
const assigningClass = ref(false);
const assigningSubject = ref(false);
const classes = ref([]);
const subjects = ref([]);
const academicYears = ref([]);
const classOptions = ref([]);
const subjectOptions = ref([]);
const academicYearOptions = ref([]);

onMounted(() => {
  fetchTeacherDetails();
  fetchClasses();
  fetchSubjects();
  fetchAcademicYears();
});

async function fetchTeacherDetails() {
  loading.value = true;
  try {
    const response = await api.get(`/teachers/${teacherId.value}`);
    if (response.data.success) {
      teacher.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to fetch teacher details:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch teacher details',
      position: 'top',
    });
    router.push('/app/teachers');
  } finally {
    loading.value = false;
  }
}

function getTeacherFullName(teacher) {
  if (!teacher || !teacher.user) return 'Unknown';
  const firstName = teacher.user.first_name || '';
  const lastName = teacher.user.last_name || '';
  return `${firstName} ${lastName}`.trim() || 'Unknown';
}

function formatDate(date) {
  if (!date) return 'Not provided';
  
  try {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    if (isNaN(dateObj.getTime())) return 'Not provided';
    
    const day = dateObj.getDate();
    const monthNames = [
      'January', 'February', 'March', 'April', 'May', 'June',
      'July', 'August', 'September', 'October', 'November', 'December'
    ];
    const month = monthNames[dateObj.getMonth()];
    const year = dateObj.getFullYear();
    
    const getOrdinalSuffix = (n) => {
      const s = ['th', 'st', 'nd', 'rd'];
      const v = n % 100;
      return s[(v - 20) % 10] || s[v] || s[0];
    };
    
    return `${day}${getOrdinalSuffix(day)} ${month}, ${year}`;
  } catch (error) {
    return 'Not provided';
  }
}

async function fetchClasses() {
  loadingClasses.value = true;
  try {
    const response = await api.get('/classes');
    if (response.data.success) {
      const classesData = response.data.data?.data || response.data.data || [];
      classes.value = classesData;
      classOptions.value = classesData.map(cls => ({
        label: cls.name,
        value: cls.id,
      }));
    }
  } catch (error) {
    console.error('Failed to fetch classes:', error);
  } finally {
    loadingClasses.value = false;
  }
}

async function fetchSubjects() {
  loadingSubjects.value = true;
  try {
    const response = await api.get('/subjects');
    if (response.data.success) {
      const subjectsData = response.data.data?.data || response.data.data || [];
      subjects.value = subjectsData;
      subjectOptions.value = subjectsData.map(subject => ({
        label: subject.name,
        value: subject.id,
      }));
    }
  } catch (error) {
    console.error('Failed to fetch subjects:', error);
  } finally {
    loadingSubjects.value = false;
  }
}

async function fetchAcademicYears() {
  loadingAcademicYears.value = true;
  try {
    const response = await api.get('/academic-years');
    if (response.data.success) {
      const yearsData = response.data.data?.data || response.data.data || [];
      academicYears.value = yearsData;
      academicYearOptions.value = yearsData.map(year => ({
        label: year.name,
        value: year.id,
      }));
    }
  } catch (error) {
    console.error('Failed to fetch academic years:', error);
  } finally {
    loadingAcademicYears.value = false;
  }
}

async function assignClass() {
  if (!selectedClassId.value) {
    $q.notify({
      type: 'warning',
      message: 'Please select a class',
      position: 'top',
    });
    return;
  }

  assigningClass.value = true;
  try {
    const response = await api.post(`/teachers/${teacherId.value}/assign-class`, {
      class_id: selectedClassId.value,
    });

    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Class assigned successfully',
        position: 'top',
      });
      showAssignClassDialog.value = false;
      selectedClassId.value = null;
      fetchTeacherDetails(); // Refresh teacher data
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to assign class',
      position: 'top',
    });
  } finally {
    assigningClass.value = false;
  }
}

async function assignSubject() {
  if (!selectedClassIdForSubject.value || !selectedSubjectId.value || !selectedAcademicYearId.value) {
    $q.notify({
      type: 'warning',
      message: 'Please fill all required fields',
      position: 'top',
    });
    return;
  }

  assigningSubject.value = true;
  try {
    const response = await api.post(`/teachers/${teacherId.value}/assign-subject`, {
      class_id: selectedClassIdForSubject.value,
      subject_id: selectedSubjectId.value,
      academic_year_id: selectedAcademicYearId.value,
    });

    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Subject assigned successfully',
        position: 'top',
      });
      showAssignSubjectDialog.value = false;
      selectedClassIdForSubject.value = null;
      selectedSubjectId.value = null;
      selectedAcademicYearId.value = null;
      fetchTeacherDetails(); // Refresh teacher data
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to assign subject',
      position: 'top',
    });
  } finally {
    assigningSubject.value = false;
  }
}

async function removeClass(classId) {
  $q.dialog({
    title: 'Confirm Remove',
    message: 'Are you sure you want to remove this class assignment?',
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      const response = await api.delete(`/teachers/${teacherId.value}/classes/${classId}`);

      if (response.data.success) {
        $q.notify({
          type: 'positive',
          message: 'Class assignment removed successfully',
          position: 'top',
        });
        fetchTeacherDetails(); // Refresh teacher data
      }
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to remove class assignment',
        position: 'top',
      });
    }
  });
}

async function removeSubject(classSubjectId) {
  $q.dialog({
    title: 'Confirm Remove',
    message: 'Are you sure you want to remove this subject assignment?',
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      const response = await api.delete(`/teachers/${teacherId.value}/subjects/${classSubjectId}`);

      if (response.data.success) {
        $q.notify({
          type: 'positive',
          message: 'Subject assignment removed successfully',
          position: 'top',
        });
        fetchTeacherDetails(); // Refresh teacher data
      }
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to remove subject assignment',
        position: 'top',
      });
    }
  });
}
</script>

<style lang="scss" scoped>
.teacher-detail-page {
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

.teacher-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-md);
  border-bottom: 1px solid var(--border-light);
}

.teacher-avatar {
  flex-shrink: 0;
}

.teacher-info {
  flex: 1;
}

.teacher-name {
  font-size: var(--font-size-xl);
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: var(--spacing-xs);
}

.teacher-id {
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
