<template>
  <q-page class="form-page">
    <MobilePageHeader
      title="Edit Term"
      subtitle="Update term information"
      :show-back="true"
      @back="$router.push(`/app/terms/${termId}`)"
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
                label="Term Name *"
                outlined
                hint="e.g., First Term, Second Term"
                :rules="[(val) => !!val || 'Term name is required']"
              />
            </div>
            <q-input
              v-model="form.start_date"
              label="Start Date *"
              type="date"
              outlined
              :rules="[(val) => !!val || 'Start date is required']"
            />
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

          <q-banner v-if="!canEdit" class="warning-banner bg-warning text-white q-mt-md">
            <template v-slot:avatar>
              <q-icon name="warning" />
            </template>
            This term cannot be edited because it is {{ term?.status }}.
          </q-banner>

          <div class="form-actions">
            <q-btn
              flat
              label="Cancel"
              @click="$router.push(`/app/terms/${termId}`)"
              class="q-mr-sm"
            />
            <q-btn
              type="submit"
              color="primary"
              label="Update Term"
              :loading="submitting"
              :disable="!canEdit"
            />
          </div>
        </q-form>
      </MobileCard>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import api from 'src/services/api';

const route = useRoute();
const router = useRouter();
const $q = useQuasar();

const termId = route.params.id;
const loading = ref(false);
const submitting = ref(false);
const term = ref(null);

const canEdit = computed(() => {
  return term.value && !['closed', 'archived'].includes(term.value.status);
});

const form = ref({
  name: '',
  start_date: '',
  end_date: '',
  grace_period_days: null,
});

const fetchTerm = async () => {
  loading.value = true;
  try {
    const response = await api.get(`/terms/${termId}`);
    if (response.data.success && response.data.data) {
      term.value = response.data.data;
      form.value = {
        name: term.value.name || '',
        start_date: term.value.start_date ? term.value.start_date.split('T')[0] : '',
        end_date: term.value.end_date ? term.value.end_date.split('T')[0] : '',
        grace_period_days: term.value.grace_period_days || null,
      };
    }
  } catch (error) {
    console.error('Failed to fetch term:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to load term. Please try again.',
      position: 'top',
    });
    router.push('/app/terms');
  } finally {
    loading.value = false;
  }
};

const onSubmit = async () => {
  if (!canEdit.value) {
    $q.notify({
      type: 'warning',
      message: 'This term cannot be edited.',
      position: 'top',
    });
    return;
  }

  submitting.value = true;
  try {
    const response = await api.put(`/terms/${termId}`, form.value);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Term updated successfully',
        position: 'top',
      });
      router.push(`/app/terms/${termId}`);
    }
  } catch (error) {
    console.error('Failed to update term:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to update term. Please try again.',
      position: 'top',
    });
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  fetchTerm();
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

