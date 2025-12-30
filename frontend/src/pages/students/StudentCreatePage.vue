<template>
  <q-page class="form-page">
    <MobilePageHeader
      title="Add New Student"
      subtitle="Create a new student record"
      :show-back="true"
      @back="router.back()"
    />

    <div class="form-content">
      <MobileCard variant="default" padding="md">
        <q-form @submit="saveStudent" class="form">
          <div class="form-section">
            <div class="section-title">Basic Information</div>
            
            <q-banner rounded class="info-banner bg-info text-white q-mb-md">
              <template v-slot:avatar>
                <q-icon name="info" />
              </template>
              Student number will be auto-generated based on your school code (e.g., B12-STU001)
            </q-banner>

              <div class="row q-col-gutter-sm">
                <q-input
                  v-model="studentForm.first_name"
                  label="First Name *"
                  outlined
                  class="col"
                  :rules="[val => !!val || 'First name is required']"
                />
                <q-input
                  v-model="studentForm.middle_name"
                  label="Middle Name"
                  outlined
                  class="col"
                />
                <q-input
                  v-model="studentForm.last_name"
                  label="Last Name *"
                  outlined
                  class="col"
                  :rules="[val => !!val || 'Last name is required']"
                />
              </div>

              <q-input
                v-model="studentForm.date_of_birth"
                label="Date of Birth"
                outlined
                type="date"
                class="q-mb-md"
              />

              <q-select
                v-model="studentForm.gender"
                :options="genderOptions"
                label="Gender"
                outlined
                emit-value
                map-options
                class="q-mb-md"
              />

              <q-input
                v-model="studentForm.email"
                label="Email"
                outlined
                type="email"
                :rules="[val => !val || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val) || 'Please enter a valid email']"
              />

              <q-input
                v-model="studentForm.phone"
                label="Phone"
                outlined
              />
          </div>

          <div class="form-section">
            <div class="section-title">Additional Information</div>

              <q-input
                v-model="studentForm.address"
                label="Address"
                outlined
                type="textarea"
                rows="3"
                class="q-mb-md"
              />

              <q-select
                v-model="studentForm.class_id"
                :options="classOptions"
                label="Current Class"
                outlined
                emit-value
                map-options
                :loading="loadingClasses"
                hint="Select the class the student is currently enrolled in"
                class="q-mb-md"
              />

              <q-toggle
                v-model="studentForm.is_active"
                label="Active"
                :true-value="true"
                :false-value="false"
                class="q-mb-md"
              />
          </div>

          <q-separator class="q-my-md" />

          <div class="form-section">
            <div class="section-title">Parent Information (Optional)</div>
            <div class="form-grid">
            <q-input
              v-model="parentForm.email"
              label="Parent Email"
              outlined
              type="email"
              class="col-12 col-md-4"
              hint="If parent exists, enter their email"
            />
            <q-input
              v-model="parentForm.first_name"
              label="Parent First Name"
              outlined
              class="col-12 col-md-4"
            />
            <q-input
              v-model="parentForm.last_name"
              label="Parent Last Name"
              outlined
              class="col-12 col-md-4"
            />
            <q-input
              v-model="parentForm.phone"
              label="Parent Phone"
              outlined
            />
            </div>
          </div>

          <div class="form-actions">
            <q-btn flat label="Cancel" @click="router.back()" class="q-mr-sm" />
            <q-btn color="primary" label="Save Student" type="submit" :loading="saving" />
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

const saving = ref(false);
const loadingClasses = ref(false);
const classes = ref([]);

const genderOptions = [
  { label: 'Male', value: 'male' },
  { label: 'Female', value: 'female' },
  { label: 'Other', value: 'other' },
];

const classOptions = ref([]);

const studentForm = ref({
  first_name: '',
  middle_name: '',
  last_name: '',
  date_of_birth: '',
  gender: null,
  email: '',
  phone: '',
  address: '',
  class_id: null,
  is_active: true,
});

const parentForm = ref({
  email: '',
  first_name: '',
  last_name: '',
  phone: '',
});

onMounted(() => {
  fetchClasses();
});

async function fetchClasses() {
  loadingClasses.value = true;
  try {
    const response = await api.get('/classes');
    if (response.data.success) {
      classes.value = response.data.data;
      classOptions.value = classes.value.map(cls => ({
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

async function saveStudent() {
  saving.value = true;
  try {
    const payload = {
      ...studentForm.value,
      parent: parentForm.value.email ? parentForm.value : null,
    };

    const response = await api.post('/students', payload);

    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Student created successfully',
        position: 'top',
      });
      router.push('/app/students');
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to create student',
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
  margin-bottom: var(--spacing-md);
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-md);
  
  @media (min-width: 600px) {
    grid-template-columns: repeat(2, 1fr);
  }
  
  @media (min-width: 900px) {
    grid-template-columns: repeat(3, 1fr);
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
