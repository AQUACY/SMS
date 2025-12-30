<template>
  <q-page class="form-page">
    <MobilePageHeader
      title="Edit Subject"
      subtitle="Update subject information"
      :show-back="true"
      @back="$router.push(`/app/subjects/${subjectId}`)"
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
            <q-input
              v-model="form.name"
              label="Subject Name *"
              outlined
              :rules="[(val) => !!val || 'Subject name is required']"
            />
            <q-input
              v-model="form.code"
              label="Subject Code"
              outlined
              hint="Optional subject code (e.g., MATH, ENG)"
            />
            <div class="col-12">
              <q-input
                v-model="form.description"
                label="Description"
                type="textarea"
                outlined
                rows="3"
              />
            </div>
            <div class="col-12">
              <q-toggle
                v-model="form.is_core"
                label="Core Subject"
                hint="Core subjects are mandatory for all students"
              />
            </div>
          </div>

          <div class="form-actions">
            <q-btn
              flat
              label="Cancel"
              @click="$router.push(`/app/subjects/${subjectId}`)"
              class="q-mr-sm"
            />
            <q-btn
              type="submit"
              color="primary"
              label="Update Subject"
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

const subjectId = route.params.id;
const loading = ref(false);
const submitting = ref(false);

const form = ref({
  name: '',
  code: '',
  description: '',
  is_core: false,
});

const fetchSubject = async () => {
  loading.value = true;
  try {
    const response = await api.get(`/subjects/${subjectId}`);
    if (response.data.success && response.data.data) {
      const subjectData = response.data.data;
      form.value = {
        name: subjectData.name || '',
        code: subjectData.code || '',
        description: subjectData.description || '',
        is_core: subjectData.is_core !== undefined ? subjectData.is_core : false,
      };
    }
  } catch (error) {
    console.error('Failed to fetch subject:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to load subject. Please try again.',
      position: 'top',
    });
    router.push('/app/subjects');
  } finally {
    loading.value = false;
  }
};

const onSubmit = async () => {
  submitting.value = true;
  try {
    const response = await api.put(`/subjects/${subjectId}`, form.value);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Subject updated successfully',
        position: 'top',
      });
      router.push(`/app/subjects/${subjectId}`);
    }
  } catch (error) {
    console.error('Failed to update subject:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to update subject. Please try again.',
      position: 'top',
    });
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  fetchSubject();
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

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-sm);
  margin-top: var(--spacing-lg);
  padding-top: var(--spacing-md);
  border-top: 1px solid var(--border-light);
}
</style>

