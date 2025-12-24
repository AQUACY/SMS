<template>
  <q-page class="q-pa-lg">
    <div class="row items-center q-mb-lg">
      <q-btn
        flat
        icon="arrow_back"
        @click="$router.push('/app/terms')"
        class="q-mr-md"
      />
      <div class="text-h5 text-weight-bold">Add New Term</div>
    </div>

    <q-card class="widget-card q-pa-md">
      <q-card-section>
        <q-form @submit="onSubmit" class="q-gutter-md">
          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-6">
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
            </div>
            <div class="col-12 col-md-6">
              <q-input
                v-model="form.name"
                label="Term Name *"
                outlined
                hint="e.g., First Term, Second Term"
                :rules="[(val) => !!val || 'Term name is required']"
              />
            </div>
            <div class="col-12 col-md-6">
              <q-select
                v-model="form.term_number"
                :options="termNumberOptions"
                option-label="label"
                option-value="value"
                emit-value
                map-options
                label="Term Number *"
                outlined
                :rules="[(val) => val !== null || 'Term number is required']"
              />
            </div>
            <div class="col-12 col-md-6">
              <q-input
                v-model.number="form.grace_period_days"
                label="Grace Period Days"
                type="number"
                outlined
                hint="Number of days after term ends before closing (0-30)"
                :rules="[
                  (val) => val === null || val === '' || (val >= 0 && val <= 30) || 'Must be between 0 and 30',
                ]"
              />
            </div>
            <div class="col-12 col-md-6">
              <q-input
                v-model="form.start_date"
                label="Start Date *"
                type="date"
                outlined
                :rules="[(val) => !!val || 'Start date is required']"
              />
            </div>
            <div class="col-12 col-md-6">
              <q-input
                v-model="form.end_date"
                label="End Date *"
                type="date"
                outlined
                :rules="[
                  (val) => !!val || 'End date is required',
                  (val) => !form.start_date || val > form.start_date || 'End date must be after start date',
                ]"
              />
            </div>
          </div>

          <div class="row justify-end q-mt-lg">
            <q-btn
              flat
              label="Cancel"
              @click="$router.push('/app/terms')"
              class="q-mr-sm"
            />
            <q-btn
              type="submit"
              color="primary"
              label="Create Term"
              :loading="submitting"
            />
          </div>
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

const submitting = ref(false);
const loadingAcademicYears = ref(false);
const academicYears = ref([]);

const termNumberOptions = [
  { label: 'First Term (1)', value: 1 },
  { label: 'Second Term (2)', value: 2 },
  { label: 'Third Term (3)', value: 3 },
];

const form = ref({
  academic_year_id: null,
  name: '',
  term_number: null,
  start_date: '',
  end_date: '',
  grace_period_days: null,
});

const fetchAcademicYears = async () => {
  loadingAcademicYears.value = true;
  try {
    const response = await api.get('/academic-years', {
      params: { per_page: 100 },
    });
    if (response.data.success) {
      // BaseApiController paginated() returns data as array and meta for pagination
      const academicYearsData = response.data.data || [];
      academicYears.value = academicYearsData.map((ay) => ({
        id: ay.id,
        name: ay.name,
      }));
      
      if (academicYears.value.length === 0) {
        $q.notify({
          type: 'info',
          message: 'No academic years found. Please create an academic year first.',
          position: 'top',
        });
      }
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

const onSubmit = async () => {
  submitting.value = true;
  try {
    const response = await api.post('/terms', form.value);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Term created successfully',
        position: 'top',
      });
      router.push('/app/terms');
    }
  } catch (error) {
    console.error('Failed to create term:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to create term. Please try again.',
      position: 'top',
    });
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  fetchAcademicYears();
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
