<template>
  <q-page class="q-pa-lg">
    <div class="row items-center q-mb-lg">
      <q-btn
        flat
        icon="arrow_back"
        @click="$router.push(`/app/subjects/${subjectId}`)"
        class="q-mr-md"
      />
      <div class="text-h5 text-weight-bold">Edit Subject</div>
    </div>

    <div v-if="loading" class="row justify-center q-pa-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <q-card v-else class="widget-card q-pa-md">
      <q-card-section>
        <q-form @submit="onSubmit" class="q-gutter-md">
          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-6">
              <q-input
                v-model="form.name"
                label="Subject Name *"
                outlined
                :rules="[(val) => !!val || 'Subject name is required']"
              />
            </div>
            <div class="col-12 col-md-6">
              <q-input
                v-model="form.code"
                label="Subject Code"
                outlined
                hint="Optional subject code (e.g., MATH, ENG)"
              />
            </div>
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

          <div class="row justify-end q-mt-lg">
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
.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}
</style>

