<template>
  <q-page class="form-page">
    <MobilePageHeader
      title="Add New Academic Year"
      subtitle="Create a new academic year"
      :show-back="true"
      @back="$router.push('/app/academic-years')"
    />

    <div class="form-content">
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

          <div class="form-actions">
            <q-btn
              flat
              label="Cancel"
              @click="$router.push('/app/academic-years')"
              class="q-mr-sm"
            />
            <q-btn
              type="submit"
              color="primary"
              label="Create Academic Year"
              :loading="submitting"
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

const submitting = ref(false);

const form = ref({
  name: '',
  start_date: '',
  end_date: '',
  is_active: false,
});

const onSubmit = async () => {
  submitting.value = true;
  try {
    const response = await api.post('/academic-years', form.value);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Academic year created successfully',
        position: 'top',
      });
      router.push('/app/academic-years');
    }
  } catch (error) {
    console.error('Failed to create academic year:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to create academic year. Please try again.',
      position: 'top',
    });
  } finally {
    submitting.value = false;
  }
};
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

