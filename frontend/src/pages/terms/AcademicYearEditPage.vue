<template>
  <q-page class="q-pa-lg">
    <div class="row items-center q-mb-lg">
      <q-btn
        flat
        icon="arrow_back"
        @click="$router.push(`/app/academic-years/${academicYearId}`)"
        class="q-mr-md"
      />
      <div class="text-h5 text-weight-bold">Edit Academic Year</div>
    </div>

    <div v-if="loading" class="row justify-center q-pa-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <q-card v-else class="widget-card q-pa-md">
      <q-card-section>
        <q-form @submit="onSubmit" class="q-gutter-md">
          <div class="row q-col-gutter-md">
            <div class="col-12">
              <q-input
                v-model="form.name"
                label="Academic Year Name *"
                outlined
                hint="e.g., 2024/2025, Academic Year 2024"
                :rules="[(val) => !!val || 'Academic year name is required']"
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
            <div class="col-12">
              <q-toggle
                v-model="form.is_active"
                label="Set as Active Academic Year"
                hint="Activating this will deactivate other academic years"
              />
            </div>
          </div>

          <q-banner v-if="academicYear?.is_active && !form.is_active" class="bg-warning text-white q-mt-md">
            <template v-slot:avatar>
              <q-icon name="warning" />
            </template>
            Deactivating the active academic year may affect system operations.
          </q-banner>

          <div class="row justify-end q-mt-lg">
            <q-btn
              flat
              label="Cancel"
              @click="$router.push(`/app/academic-years/${academicYearId}`)"
              class="q-mr-sm"
            />
            <q-btn
              type="submit"
              color="primary"
              label="Update Academic Year"
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
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import api from 'src/services/api';

const route = useRoute();
const router = useRouter();
const $q = useQuasar();

const academicYearId = route.params.id;
const loading = ref(false);
const submitting = ref(false);
const academicYear = ref(null);

const form = ref({
  name: '',
  start_date: '',
  end_date: '',
  is_active: false,
});

const fetchAcademicYear = async () => {
  loading.value = true;
  try {
    const response = await api.get(`/academic-years/${academicYearId}`);
    if (response.data.success && response.data.data) {
      academicYear.value = response.data.data;
      form.value = {
        name: academicYear.value.name || '',
        start_date: academicYear.value.start_date ? academicYear.value.start_date.split('T')[0] : '',
        end_date: academicYear.value.end_date ? academicYear.value.end_date.split('T')[0] : '',
        is_active: academicYear.value.is_active !== undefined ? academicYear.value.is_active : false,
      };
    }
  } catch (error) {
    console.error('Failed to fetch academic year:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to load academic year. Please try again.',
      position: 'top',
    });
    router.push('/app/academic-years');
  } finally {
    loading.value = false;
  }
};

const onSubmit = async () => {
  submitting.value = true;
  try {
    const response = await api.put(`/academic-years/${academicYearId}`, form.value);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Academic year updated successfully',
        position: 'top',
      });
      router.push(`/app/academic-years/${academicYearId}`);
    }
  } catch (error) {
    console.error('Failed to update academic year:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to update academic year. Please try again.',
      position: 'top',
    });
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  fetchAcademicYear();
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

