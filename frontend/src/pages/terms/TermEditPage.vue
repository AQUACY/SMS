<template>
  <q-page class="q-pa-lg">
    <div class="row items-center q-mb-lg">
      <q-btn
        flat
        icon="arrow_back"
        @click="$router.push(`/app/terms/${termId}`)"
        class="q-mr-md"
      />
      <div class="text-h5 text-weight-bold">Edit Term</div>
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
                label="Term Name *"
                outlined
                hint="e.g., First Term, Second Term"
                :rules="[(val) => !!val || 'Term name is required']"
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
          </div>

          <q-banner v-if="!canEdit" class="bg-warning text-white q-mt-md">
            <template v-slot:avatar>
              <q-icon name="warning" />
            </template>
            This term cannot be edited because it is {{ term?.status }}.
          </q-banner>

          <div class="row justify-end q-mt-lg">
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
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
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
.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}
</style>

