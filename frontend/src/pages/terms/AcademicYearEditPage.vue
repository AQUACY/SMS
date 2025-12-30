<template>
  <q-page class="form-page">
    <MobilePageHeader
      title="Edit Academic Year"
      subtitle="Update academic year information"
      :show-back="true"
      @back="$router.push(`/app/academic-years/${academicYearId}`)"
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
        <q-form @submit="onSubmit" class="form">
          <div class="form-grid">
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

          <q-banner v-if="academicYear?.is_active && !form.is_active" class="warning-banner bg-warning text-white q-mt-md">
            <template v-slot:avatar>
              <q-icon name="warning" />
            </template>
            Deactivating the active academic year may affect system operations.
          </q-banner>

          <div class="form-actions">
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
      </MobileCard>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
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

.form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-md);
  
  @media (min-width: 600px) {
    grid-template-columns: repeat(2, 1fr);
  }
}

.warning-banner {
  margin-top: var(--spacing-md);
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

