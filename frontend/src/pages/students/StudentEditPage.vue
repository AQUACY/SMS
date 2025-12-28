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
        <div class="text-h5 text-weight-bold">Edit Student</div>
        <div class="text-body2 text-grey-7">Update student information</div>
      </div>
    </div>

    <div v-if="loading" class="row justify-center q-pa-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <q-card v-else class="widget-card">
      <q-card-section>
        <q-form @submit="updateStudent" class="q-gutter-md">
          <div class="row q-col-gutter-md q-pa-md" >
            <!-- Left Column -->
            <div class="col-12 col-md-6">
              <div class="text-subtitle2 q-mb-sm">Basic Information</div>
              
              <q-input
                v-model="studentForm.student_number"
                label="Student Number"
                outlined
                hint="Auto-generated identifier (cannot be changed)"
                readonly
                :disable="true"
              />

              <div class="row q-col-gutter-sm">
                <q-input
                  v-model="studentForm.first_name"
                  label="First Name *"
                  outlined
                  class="col"
                  :rules="[val => !!val || 'First name is required']"
                  :disable="saving"
                />
                <q-input
                  v-model="studentForm.middle_name"
                  label="Middle Name"
                  outlined
                  class="col"
                  :disable="saving"
                />
                <q-input
                  v-model="studentForm.last_name"
                  label="Last Name *"
                  outlined
                  class="col"
                  :rules="[val => !!val || 'Last name is required']"
                  :disable="saving"
                />
              </div>

              <q-input
                v-model="studentForm.date_of_birth"
                label="Date of Birth"
                outlined
                class="q-mb-md"
                type="date"
                :disable="saving"
              />

              <q-select
                v-model="studentForm.gender"
                :options="genderOptions"
                label="Gender"
                class="q-mb-md"
                outlined
                emit-value
                map-options
                :disable="saving"
              />

              <q-input
                v-model="studentForm.email"
                label="Email"
                outlined
                type="email"
                :rules="[val => !val || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val) || 'Please enter a valid email']"
                :disable="saving"
              />

              <q-input
                v-model="studentForm.phone"
                label="Phone"
                outlined
                :disable="saving"
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
                :disable="saving"
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
                :disable="saving"
                hint="Select the class the student is currently enrolled in"
                clearable
                class="q-mb-md"
              />

              <q-toggle
                v-model="studentForm.is_active"
                label="Active"
                :true-value="true"
                :false-value="false"
                :disable="saving"
              />
            </div>
          </div>

          <q-separator class="q-my-md" />

          <!-- Current Guardians Section -->
          <div class="text-subtitle2 q-mb-sm">Current Guardians</div>
          <div v-if="guardians.length > 0" class="q-mb-md">
            <q-list bordered separator>
              <q-item v-for="guardian in guardians" :key="guardian.id">
                <q-item-section avatar>
                  <q-avatar size="40px" class="bg-primary">
                    <q-icon name="person" color="white" />
                  </q-avatar>
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ guardian.user?.full_name || 'Unknown' }}</q-item-label>
                  <q-item-label caption>
                    {{ guardian.pivot?.relationship || 'Guardian' }}
                    <span v-if="guardian.pivot?.is_primary" class="q-ml-xs">
                      <q-badge color="primary" label="Primary" />
                    </span>
                  </q-item-label>
                  <q-item-label caption>{{ guardian.user?.email || '' }}</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
            <div class="text-caption text-grey-7 q-mt-sm">
              <q-icon name="info" size="16px" class="q-mr-xs" />
              To add or remove guardians, go to the student detail page after saving.
            </div>
          </div>
          <div v-else class="text-body2 text-grey-7 q-mb-md">
            No guardians linked. You can add guardians from the student detail page.
          </div>

          <q-card-actions align="right" class="q-pt-md">
            <q-btn
              flat
              label="Cancel"
              @click="router.back()"
              :disable="saving"
            />
            <q-btn
              color="primary"
              label="Update Student"
              type="submit"
              :loading="saving"
              icon="save"
            />
          </q-card-actions>
        </q-form>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import api from 'src/services/api';

const route = useRoute();
const router = useRouter();
const $q = useQuasar();

const loading = ref(false);
const saving = ref(false);
const loadingClasses = ref(false);
const classes = ref([]);
const guardians = ref([]);

const genderOptions = [
  { label: 'Male', value: 'male' },
  { label: 'Female', value: 'female' },
  { label: 'Other', value: 'other' },
];

const classOptions = ref([]);

const studentForm = ref({
  student_number: '',
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

const studentId = computed(() => route.params.id);

onMounted(() => {
  fetchStudent();
  fetchClasses();
});

async function fetchStudent() {
  loading.value = true;
  try {
    const response = await api.get(`/students/${studentId.value}`);
    if (response.data.success) {
      const student = response.data.data;
      
      // Populate form
      studentForm.value = {
        student_number: student.student_number || '',
        first_name: student.first_name || '',
        middle_name: student.middle_name || '',
        last_name: student.last_name || '',
        date_of_birth: student.date_of_birth 
          ? (typeof student.date_of_birth === 'string' 
              ? student.date_of_birth.split('T')[0] 
              : new Date(student.date_of_birth).toISOString().split('T')[0])
          : '',
        gender: student.gender || null,
        email: student.email || '',
        phone: student.phone || '',
        address: student.address || '',
        class_id: student.active_enrollment?.class_id || null,
        is_active: student.is_active !== undefined ? student.is_active : true,
      };

      // Set guardians
      guardians.value = student.parents || [];
    }
  } catch (error) {
    console.error('Failed to fetch student:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch student details',
      position: 'top',
    });
    router.push('/app/students');
  } finally {
    loading.value = false;
  }
}

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

async function updateStudent() {
  saving.value = true;
  try {
    const payload = {
      ...studentForm.value,
    };

    const response = await api.put(`/students/${studentId.value}`, payload);

    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Student updated successfully',
        position: 'top',
      });
      
      // Navigate to student detail page
      router.push(`/app/students/${studentId.value}`);
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to update student',
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
