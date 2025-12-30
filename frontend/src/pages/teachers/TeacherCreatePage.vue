<template>
  <q-page class="form-page">
    <MobilePageHeader
      title="Add New Teacher"
      subtitle="Create a new teacher record"
      :show-back="true"
      @back="router.back()"
    />

    <div class="form-content">
      <MobileCard variant="default" padding="md">
        <q-form @submit="saveTeacher" class="form">
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
              v-model="teacherForm.password"
              label="Password *"
              outlined
              type="password"
              :rules="[val => !!val || 'Password is required', val => val.length >= 8 || 'Password must be at least 8 characters']"
              hint="Minimum 8 characters"
              :disable="saving"
              class="q-mb-md"
            />

            <q-banner rounded class="info-banner bg-info text-white q-mt-md">
              <template v-slot:avatar>
                <q-icon name="info" />
              </template>
              Staff number will be auto-generated based on your school code (e.g., B12-TEA001)
            </q-banner>
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
              label="Save Teacher"
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
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import api from 'src/services/api';

const router = useRouter();
const $q = useQuasar();

const saving = ref(false);

const teacherForm = ref({
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  qualification: '',
  specialization: '',
  hire_date: '',
});

async function saveTeacher() {
  saving.value = true;
  try {
    const response = await api.post('/teachers', teacherForm.value);

    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Teacher created successfully',
        position: 'top',
      });
      router.push('/app/teachers');
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to create teacher',
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

.info-banner {
  margin-top: var(--spacing-md);
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
