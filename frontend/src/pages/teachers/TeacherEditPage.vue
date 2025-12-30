<template>
  <q-page class="form-page">
    <MobilePageHeader
      title="Edit Teacher"
      subtitle="Update teacher information"
      :show-back="true"
      @back="router.back()"
    />

    <div v-if="loading" class="detail-loading">
      <MobileCard variant="default" padding="md">
        <q-skeleton type="rect" height="200px" class="q-mb-md" />
        <q-skeleton type="text" width="60%" />
        <q-skeleton type="text" width="40%" />
      </MobileCard>
    </div>

    <div v-else class="form-content">
      <MobileCard variant="default" padding="md">
        <q-form @submit="updateTeacher" class="form">
          <div class="form-section">
            <div class="section-title">Basic Information</div>
            
            <div class="form-grid">
              <q-input
                v-model="teacherForm.first_name"
                label="First Name *"
                outlined
                :rules="[val => !!val || 'First name is required']"
                :disable="saving"
              />
              <q-input
                v-model="teacherForm.last_name"
                label="Last Name *"
                outlined
                :rules="[val => !!val || 'Last name is required']"
                :disable="saving"
              />
            </div>

            <q-input
              v-model="teacherForm.email"
              label="Email *"
              outlined
              type="email"
              :rules="[
                val => !!val || 'Email is required',
                val => !val || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val) || 'Please enter a valid email'
              ]"
              :disable="saving"
              class="q-mb-md"
            />

            <q-input
              v-model="teacherForm.staff_number"
              label="Staff Number"
              outlined
              hint="Unique identifier for the teacher"
              :disable="saving"
            />
          </div>

          <div class="form-section">
            <div class="section-title">Professional Information</div>

            <q-input
              v-model="teacherForm.qualification"
              label="Qualification"
              outlined
              hint="e.g., B.Ed, M.Ed, PhD"
              :disable="saving"
              class="q-mb-md"
            />

            <q-input
              v-model="teacherForm.specialization"
              label="Specialization"
              outlined
              hint="e.g., Mathematics, Science, English"
              :disable="saving"
              class="q-mb-md"
            />

            <q-input
              v-model="teacherForm.hire_date"
              label="Hire Date"
              outlined
              type="date"
              :disable="saving"
            />
          </div>

          <div class="form-actions">
            <q-btn
              flat
              label="Cancel"
              @click="router.back()"
              :disable="saving"
              class="q-mr-sm"
            />
            <q-btn
              color="primary"
              label="Update Teacher"
              type="submit"
              :loading="saving"
              icon="save"
            />
          </div>
        </q-form>
      </MobileCard>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import api from 'src/services/api';

const route = useRoute();
const router = useRouter();
const $q = useQuasar();

const loading = ref(false);
const saving = ref(false);

const teacherId = computed(() => route.params.id);

const teacherForm = ref({
  first_name: '',
  last_name: '',
  email: '',
  staff_number: '',
  qualification: '',
  specialization: '',
  hire_date: '',
});

onMounted(() => {
  fetchTeacher();
});

async function fetchTeacher() {
  loading.value = true;
  try {
    const response = await api.get(`/teachers/${teacherId.value}`);
    if (response.data.success) {
      const teacher = response.data.data;
      
      teacherForm.value = {
        first_name: teacher.user?.first_name || '',
        last_name: teacher.user?.last_name || '',
        email: teacher.user?.email || '',
        staff_number: teacher.staff_number || '',
        qualification: teacher.qualification || '',
        specialization: teacher.specialization || '',
        hire_date: teacher.hire_date 
          ? (typeof teacher.hire_date === 'string' 
              ? teacher.hire_date.split('T')[0] 
              : new Date(teacher.hire_date).toISOString().split('T')[0])
          : '',
      };
    }
  } catch (error) {
    console.error('Failed to fetch teacher:', error);
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

async function updateTeacher() {
  saving.value = true;
  try {
    const response = await api.put(`/teachers/${teacherId.value}`, teacherForm.value);

    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Teacher updated successfully',
        position: 'top',
      });
      
      router.push(`/app/teachers/${teacherId.value}`);
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to update teacher',
      position: 'top',
    });
  } finally {
    saving.value = false;
  }
}
</script>

<style lang="scss" scoped>
.form-page {
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

.form-content {
  max-width: 900px;
  margin: 0 auto;
}

.form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.form-section {
  margin-bottom: var(--spacing-lg);
}

.section-title {
  font-size: var(--font-size-base);
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: var(--spacing-md);
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

