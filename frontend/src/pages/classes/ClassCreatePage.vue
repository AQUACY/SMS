<template>
  <q-page class="form-page">
    <MobilePageHeader
      title="Add New Class"
      subtitle="Create a new class and assign a class teacher to manage it"
      :show-back="true"
      @back="$router.push('/app/classes')"
    />

    <div class="form-content">
      <MobileCard variant="default" padding="md">
        <q-form @submit="onSubmit" class="form">
          <div class="form-grid">
            <q-input
              v-model="form.name"
              label="Class Name *"
              outlined
              :rules="[(val) => !!val || 'Class name is required']"
            />
            <q-input
              v-model="form.level"
              label="Level *"
              outlined
              :rules="[(val) => !!val || 'Level is required']"
            />
            <q-input
              v-model="form.section"
              label="Section"
              outlined
            />
            <q-input
              v-model.number="form.capacity"
              label="Capacity *"
              type="number"
              outlined
              :rules="[
                (val) => !!val || 'Capacity is required',
                (val) => val > 0 || 'Capacity must be greater than 0',
                (val) => val <= 100 || 'Capacity cannot exceed 100',
              ]"
            />
            <q-select
              v-model="form.academic_year_id"
              :options="academicYears"
              option-label="name"
              option-value="id"
              emit-value
              map-options
              label="Academic Year *"
              outlined
              :rules="[(val) => !!val || 'Academic year is required']"
              :loading="loadingAcademicYears"
            />
            <q-select
              v-model="form.class_teacher_id"
              :options="teachers"
              option-label="name"
              option-value="id"
              emit-value
              map-options
              label="Class Teacher"
              outlined
              clearable
              :loading="loadingTeachers"
            />
            <div class="col-12">
              <q-toggle
                v-model="form.is_active"
                label="Active"
              />
            </div>
          </div>

          <div class="form-actions">
            <q-btn
              flat
              label="Cancel"
              @click="$router.push('/app/classes')"
              class="q-mr-sm"
            />
            <q-btn
              type="submit"
              color="primary"
              label="Create Class"
              :loading="submitting"
            />
          </div>
        </q-form>
      </MobileCard>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import api from 'src/services/api';

const router = useRouter();
const $q = useQuasar();

const submitting = ref(false);
const loadingAcademicYears = ref(false);
const loadingTeachers = ref(false);
const academicYears = ref([]);
const teachers = ref([]);

const form = ref({
  name: '',
  level: '',
  section: '',
  capacity: null,
  academic_year_id: null,
  class_teacher_id: null,
  is_active: true,
});

const fetchAcademicYears = async () => {
  loadingAcademicYears.value = true;
  try {
    const response = await api.get('/academic-years', {
      params: { per_page: 100 },
    });
    if (response.data.success && response.data.data) {
      academicYears.value = (response.data.data || []).map((ay) => ({
        id: ay.id,
        name: ay.name,
      }));
    }
  } catch (error) {
    console.error('Failed to fetch academic years:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to load academic years. Please try again.',
      position: 'top',
    });
  } finally {
    loadingAcademicYears.value = false;
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
    $q.notify({
      type: 'negative',
      message: 'Failed to load teachers. Please try again.',
      position: 'top',
    });
  } finally {
    loadingTeachers.value = false;
  }
};

const onSubmit = async () => {
  submitting.value = true;
  try {
    const response = await api.post('/classes', form.value);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Class created successfully',
        position: 'top',
      });
      router.push('/app/classes');
    }
  } catch (error) {
    console.error('Failed to create class:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to create class. Please try again.',
      position: 'top',
    });
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  fetchAcademicYears();
  fetchTeachers();
});
</script>

<style lang="scss" scoped>
.form-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.form-content {
  max-width: 900px;
  margin: 0 auto;
}

.form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-md);
  
  @media (min-width: 600px) {
    grid-template-columns: repeat(2, 1fr);
  }
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-sm);
  margin-top: var(--spacing-lg);
  padding-top: var(--spacing-md);
  border-top: 1px solid var(--border-light);
}
</style>
