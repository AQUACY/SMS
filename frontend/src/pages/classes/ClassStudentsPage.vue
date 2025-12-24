<template>
  <q-page class="q-pa-lg">
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

    <q-card class="widget-card">
      <q-card-section>
        <div class="text-h6 q-mb-md">Students List</div>
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
      </q-card-section>
    </q-card>

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

onMounted(() => {
  fetchClass();
  fetchStudents();
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

