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
        <div class="text-h5 text-weight-bold">Add New Teacher</div>
        <div class="text-body2 text-grey-7">Create a new teacher record</div>
      </div>
    </div>

    <q-card class="widget-card q-pa-md">
      <q-card-section>
        <q-form @submit="saveTeacher" class="q-gutter-md">
          <div class="row q-col-gutter-md">
            <!-- Left Column -->
            <div class="col-12 col-md-6">
              <div class="text-subtitle2 q-mb-sm">Basic Information</div>
              
              <div class="row q-col-gutter-sm">
                <q-input
                  v-model="teacherForm.first_name"
                  label="First Name *"
                  outlined
                  class="col"
                  :rules="[val => !!val || 'First name is required']"
                  :disable="saving"
                />
                <q-input
                  v-model="teacherForm.last_name"
                  label="Last Name *"
                  outlined
                  class="col"
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
              />

              <q-input
                v-model="teacherForm.password"
                label="Password *"
                outlined
                type="password"
                :rules="[val => !!val || 'Password is required', val => val.length >= 8 || 'Password must be at least 8 characters']"
                hint="Minimum 8 characters"
                :disable="saving"
              />

              <q-banner rounded class="bg-info text-white q-mt-md">
                <template v-slot:avatar>
                  <q-icon name="info" />
                </template>
                Staff number will be auto-generated based on your school code (e.g., B12-TEA001)
              </q-banner>
            </div>

            <!-- Right Column -->
            <div class="col-12 col-md-6">
              <div class="text-subtitle2 q-mb-sm">Professional Information</div>

              <q-input
                v-model="teacherForm.qualification"
                label="Qualification"
                outlined
                hint="e.g., B.Ed, M.Ed, PhD"
                :disable="saving"
              />

              <q-input
                v-model="teacherForm.specialization"
                label="Specialization"
                outlined
                hint="e.g., Mathematics, Science, English"
                :disable="saving"
              />

              <q-input
                v-model="teacherForm.hire_date"
                label="Hire Date"
                outlined
                type="date"
                :disable="saving"
              />
            </div>
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
              label="Save Teacher"
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
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
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
.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}
</style>
