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
        <div class="text-h5 text-weight-bold">Edit Teacher</div>
        <div class="text-body2 text-grey-7">Update teacher information</div>
      </div>
    </div>

    <div v-if="loading" class="row justify-center q-pa-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <q-card v-else class="widget-card q-pa-md">
      <q-card-section>
        <q-form @submit="updateTeacher" class="q-gutter-md">
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
                v-model="teacherForm.staff_number"
                label="Staff Number"
                outlined
                hint="Unique identifier for the teacher"
                :disable="saving"
              />
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
              label="Update Teacher"
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
.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}
</style>

