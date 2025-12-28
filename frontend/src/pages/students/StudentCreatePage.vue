<template>
  <q-page class="q-pa-lg">
    <div class="row items-center q-mb-lg">
      <q-btn
        flat
        icon="arrow_back"
        label="Back"
        @click="router.back()"
        class="q-mr-md"
      />
      <div>
        <div class="text-h5 text-weight-bold">Add New Student</div>
        <div class="text-body2 text-grey-7">Create a new student record</div>
      </div>
    </div>

    <q-card class="widget-card">
      <q-card-section>
        <q-form @submit="saveStudent" class="q-gutter-md">
          <div class="row q-col-gutter-md q-pa-md">
            <!-- Left Column -->
            <div class="col-12 col-md-6">
              <div class="text-subtitle2 q-mb-sm">Basic Information</div>
              
              <q-banner rounded class="bg-info text-white q-mb-md">
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

            <!-- Right Column -->
            <div class="col-12 col-md-6">
              <div class="text-subtitle2 q-mb-sm">Additional Information</div>

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
          </div>

          <q-separator class="q-my-md" />

          <div class="text-subtitle2 q-mb-sm">Parent Information (Optional)</div>
          <div class="row q-col-gutter-md q-pa-md">
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
              class="col-12 col-md-4"
            />
          </div>

          <q-card-actions align="right" class="q-pt-md">
            <q-btn flat label="Cancel" @click="router.back()" />
            <q-btn color="primary" label="Save Student" type="submit" :loading="saving" />
          </q-card-actions>
        </q-form>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
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
.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}
</style>
