<template>
  <q-page class="q-pa-lg">
    <div class="row items-center q-mb-lg">
      <q-btn
        flat
        icon="arrow_back"
        label="Back"
        @click="router.push('/app/teachers')"
        class="q-mr-md"
      />
      <div>
        <div class="text-h5 text-weight-bold">Teacher Details</div>
        <div class="text-body2 text-grey-7">View and manage teacher information</div>
      </div>
      <q-space />
      <q-btn
        v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
        color="primary"
        label="Edit Teacher"
        icon="edit"
        unelevated
        @click="router.push(`/app/teachers/${teacherId}/edit`)"
      />
    </div>

    <div v-if="loading" class="row justify-center q-pa-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <div v-else-if="teacher" class="row q-col-gutter-md">
      <!-- Left Column - Teacher Information -->
      <div class="col-12 col-md-8">
        <!-- Basic Information Card -->
        <q-card class="widget-card q-mb-md">
          <q-card-section>
            <div class="row items-center q-mb-md">
              <q-avatar size="80px" class="bg-primary q-mr-md">
                <q-icon name="person" size="48px" color="white" />
              </q-avatar>
              <div>
                <div class="text-h5 text-weight-bold q-mb-xs">{{ getTeacherFullName(teacher) }}</div>
                <div class="text-body2 text-grey-7 q-mb-xs">Staff ID: {{ teacher.staff_number || 'Not assigned' }}</div>
                <q-badge
                  :color="teacher.user?.is_active ? 'positive' : 'negative'"
                  :label="teacher.user?.is_active ? 'Active' : 'Inactive'"
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
          </q-card-section>
        </q-card>

        <!-- Classes Card -->
        <q-card class="widget-card q-mb-md">
          <q-card-section>
            <div class="row items-center justify-between q-mb-md">
              <div class="text-h6">Assigned Classes</div>
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
            <div v-else class="text-body2 text-grey-7">No classes assigned</div>
          </q-card-section>
        </q-card>

        <!-- Subjects Card -->
        <q-card class="widget-card">
          <q-card-section>
            <div class="row items-center justify-between q-mb-md">
              <div class="text-h6">Assigned Subjects</div>
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
            <div v-else class="text-body2 text-grey-7">No subjects assigned</div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Right Column - Quick Actions -->
      <div class="col-12 col-md-4">
        <!-- Quick Actions Card -->
        <q-card class="widget-card">
          <q-card-section>
            <div class="text-h6 q-mb-md">Quick Actions</div>
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
          </q-card-section>
        </q-card>
      </div>
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
.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}
</style>
