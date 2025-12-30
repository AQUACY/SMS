<template>
  <q-page class="class-students-page">
    <!-- Mobile Header -->
    <div class="mobile-only">
      <MobilePageHeader
        :title="classData?.name || 'Class Students'"
        :subtitle="`${classData?.academic_year?.name || ''} - ${students.length} students`"
        :show-back="true"
        @back="router.push(`/app/classes/${classId}`)"
      >
        <template #actions>
          <q-btn
            v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
            flat
            round
            dense
            icon="download"
            color="secondary"
            @click="exportToExcel"
            :loading="exporting"
            class="q-mr-xs"
          >
            <q-tooltip>Export Excel</q-tooltip>
          </q-btn>
          <q-btn
            v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
            flat
            round
            dense
            icon="upload"
            color="secondary"
            @click="showImportDialog = true"
            class="q-mr-xs"
          >
            <q-tooltip>Import Excel</q-tooltip>
          </q-btn>
          <q-btn
            v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
            flat
            round
            dense
            icon="add"
            color="primary"
            @click="showAddDialog = true"
          >
            <q-tooltip>Add Student</q-tooltip>
          </q-btn>
        </template>
      </MobilePageHeader>
    </div>

    <!-- Desktop Header -->
    <div class="desktop-only q-pa-lg">
      <div class="row items-center q-mb-lg">
        <q-btn
          flat
          icon="arrow_back"
          label="Back"
          @click="router.push(`/app/classes/${classId}`)"
          class="q-mr-md"
        />
        <div>
          <div class="text-h5 text-weight-bold">{{ classData?.name || 'Class Students' }}</div>
          <div class="text-body2 text-grey-7">
            {{ classData?.academic_year?.name || '' }} - {{ students.length }} students
          </div>
        </div>
        <q-space />
        <div>
          <q-btn
            v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
            color="secondary"
            label="Export Excel"
            icon="download"
            unelevated
            @click="exportToExcel"
            :loading="exporting"
            class="q-mr-sm"
          />
          <q-btn
            v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
            color="secondary"
            label="Import Excel"
            icon="upload"
            unelevated
            @click="showImportDialog = true"
            class="q-mr-sm"
          />
          <q-btn
            v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
            color="primary"
            label="Add Student"
            icon="add"
            unelevated
            @click="showAddDialog = true"
          />
        </div>
      </div>
    </div>

    <div class="page-content">
      <MobileCard variant="default" padding="md">
        <div class="card-title">Students List</div>
        
        <!-- Mobile View: Card List -->
        <div class="mobile-only">
          <div v-if="loading" class="text-center q-pa-xl">
            <q-spinner color="primary" size="3em" />
          </div>
          <div v-else-if="students.length === 0" class="empty-state">
            <q-icon name="people" size="64px" color="grey-5" />
            <div class="empty-text">No Students</div>
            <div class="empty-subtext">No students enrolled in this class.</div>
          </div>
          <div v-else class="students-list">
            <MobileListCard
              v-for="student in students"
              :key="student.id"
              :title="getStudentName(student)"
              :subtitle="student.student_number || 'N/A'"
              :description="`${formatDate(student.date_of_birth)} | ${student.gender || 'N/A'}`"
              icon="person"
              :clickable="true"
              @click="viewStudent(student.id)"
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
                    @click.stop="viewStudent(student.id)"
                  />
                </div>
              </template>
            </MobileListCard>
          </div>
        </div>

        <!-- Desktop View: Table -->
        <div class="desktop-only">
          <q-table
          :rows="students"
          :columns="columns"
          row-key="id"
          :loading="loading"
          flat
        >
          <template v-slot:body-cell-name="props">
            <q-td :props="props">
              <div class="text-weight-medium">{{ props.value }}</div>
              <div class="text-caption text-grey-7">{{ props.row.student_number }}</div>
            </q-td>
          </template>

          <template v-slot:body-cell-guardians="props">
            <q-td :props="props">
              <div v-if="props.value && props.value.length > 0">
                <div v-for="(guardian, idx) in props.value" :key="idx" class="text-caption">
                  {{ guardian.user?.first_name }} {{ guardian.user?.last_name }}
                  <q-badge v-if="guardian.pivot?.is_primary" color="primary" class="q-ml-xs">Primary</q-badge>
                </div>
              </div>
              <span v-else class="text-grey-6">No guardians</span>
            </q-td>
          </template>

          <template v-slot:body-cell-actions="props">
            <q-td :props="props">
              <q-btn
                flat
                dense
                icon="visibility"
                color="primary"
                @click="viewStudent(props.row.id)"
                class="q-mr-xs"
              />
            </q-td>
          </template>
          </q-table>
        </div>
      </MobileCard>
    </div>

    <!-- Add Student Dialog -->
    <q-dialog v-model="showAddDialog" persistent>
      <q-card style="min-width: 600px">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Add Student to Class</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <StudentForm
            ref="studentFormRef"
            :class-id="classId"
            @saved="handleStudentAdded"
            @cancel="showAddDialog = false"
          />
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Excel Import Dialog -->
    <ExcelImportDialog
      v-model="showImportDialog"
      type="class-students"
      :class-id="classId"
      title="Import Students from Excel"
      description="Download the template, fill it with student data, and upload it here to bulk import students for this class."
      @imported="handleImportSuccess"
    />
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
import ExcelImportDialog from 'src/components/ExcelImportDialog.vue';
import StudentForm from 'src/components/StudentForm.vue';
import api from 'src/services/api';

const route = useRoute();
const router = useRouter();
const $q = useQuasar();
const authStore = useAuthStore();

const classId = route.params.id;
const loading = ref(false);
const exporting = ref(false);
const classData = ref(null);
const students = ref([]);
const showAddDialog = ref(false);
const showImportDialog = ref(false);
const studentFormRef = ref(null);

const columns = [
  {
    name: 'name',
    label: 'Name',
    field: (row) => {
      const parts = [row.first_name, row.middle_name, row.last_name].filter(Boolean);
      return parts.join(' ') || 'Unknown';
    },
    align: 'left',
    sortable: true,
  },
  {
    name: 'date_of_birth',
    label: 'Date of Birth',
    field: 'date_of_birth',
    align: 'left',
    format: (val) => val ? new Date(val).toLocaleDateString() : 'N/A',
  },
  {
    name: 'gender',
    label: 'Gender',
    field: 'gender',
    align: 'center',
  },
  {
    name: 'phone',
    label: 'Phone',
    field: 'phone',
    align: 'left',
  },
  {
    name: 'email',
    label: 'Email',
    field: 'email',
    align: 'left',
  },
  {
    name: 'guardians',
    label: 'Guardians',
    field: (row) => row.parents || [],
    align: 'left',
  },
  {
    name: 'actions',
    label: 'Actions',
    field: 'actions',
    align: 'center',
  },
];

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

const fetchStudents = async () => {
  loading.value = true;
  try {
    const response = await api.get(`/classes/${classId}/students`);
    if (response.data.success) {
      students.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch students:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to load students. Please try again.',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
};

const exportToExcel = async () => {
  exporting.value = true;
  try {
    const response = await api.get('/excel/export/class-students', {
      params: { class_id: classId },
      responseType: 'blob',
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `class_${classData.value?.name}_students_${new Date().toISOString().split('T')[0]}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);

    $q.notify({
      type: 'positive',
      message: 'Students exported successfully',
      position: 'top',
    });
  } catch (error) {
    console.error('Failed to export students:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to export students. Please try again.',
      position: 'top',
    });
  } finally {
    exporting.value = false;
  }
};

const handleStudentAdded = () => {
  showAddDialog.value = false;
  fetchStudents();
  $q.notify({
    type: 'positive',
    message: 'Student added successfully',
    position: 'top',
  });
};

const handleImportSuccess = () => {
  fetchStudents();
};

const viewStudent = (id) => {
  router.push(`/app/students/${id}`);
};

const getStudentName = (student) => {
  const parts = [student.first_name, student.middle_name, student.last_name].filter(Boolean);
  return parts.join(' ') || 'Unknown';
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
};

onMounted(() => {
  fetchClass();
  fetchStudents();
});
</script>

<style lang="scss" scoped>
.class-students-page {
  padding: 0;
  
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
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: 0;
  }
}

.card-title {
  font-size: var(--font-size-lg);
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: var(--spacing-md);
}

.students-list {
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

