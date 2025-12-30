<template>
  <q-page class="form-page">
    <MobilePageHeader
      title="Add New Subject"
      subtitle="Create a new subject"
      :show-back="true"
      @back="$router.push('/app/subjects')"
    />

    <div class="form-content">
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
              @click="$router.push('/app/subjects')"
              class="q-mr-sm"
            />
            <q-btn
              type="submit"
              color="primary"
              label="Create Subject"
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
  code: '',
  description: '',
  is_core: false,
});

const onSubmit = async () => {
  submitting.value = true;
  try {
    const response = await api.post('/subjects', form.value);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Subject created successfully',
        position: 'top',
      });
      router.push('/app/subjects');
    }
  } catch (error) {
    console.error('Failed to create subject:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to create subject. Please try again.',
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
