<template>
  <q-page class="form-page">
    <MobilePageHeader
      title="Generate Report Card"
      subtitle="Select student and term to generate report card"
      :show-back="true"
      @back="$router.push('/app/report-cards')"
    />

    <div class="form-content">
      <MobileCard variant="default" padding="md">
        <q-form @submit="generateReportCard" class="form">
          <div class="form-grid">
            <div class="col-12 col-md-6">
              <q-select
                v-model="form.term_id"
                :options="terms"
                option-label="name"
                option-value="id"
                emit-value
                map-options
                label="Term *"
                outlined
                :rules="[(val) => !!val || 'Term is required']"
                :loading="loadingTerms"
                @update:model-value="onTermChange"
              >
                <template v-slot:option="scope">
                  <q-item v-bind="scope.itemProps">
                    <q-item-section>
                      <q-item-label>{{ scope.opt.name }}</q-item-label>
                      <q-item-label caption>
                        {{ scope.opt.academic_year?.name || '' }} - 
                        {{ formatDate(scope.opt.start_date) }} to {{ formatDate(scope.opt.end_date) }}
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                </template>
              </q-select>
            </div>

            <div class="col-12 col-md-6">
              <q-select
                v-model="form.student_id"
                :options="students"
                option-label="full_name"
                option-value="id"
                emit-value
                map-options
                label="Student *"
                outlined
                :rules="[(val) => !!val || 'Student is required']"
                :loading="loadingStudents"
                :disable="!form.term_id"
                use-input
                input-debounce="300"
                @filter="filterStudents"
              >
                <template v-slot:option="scope">
                  <q-item v-bind="scope.itemProps">
                    <q-item-section>
                      <q-item-label>{{ scope.opt.full_name }}</q-item-label>
                      <q-item-label caption>{{ scope.opt.student_number || '' }}</q-item-label>
                    </q-item-section>
                  </q-item>
                </template>
                <template v-slot:no-option>
                  <q-item>
                    <q-item-section class="text-grey">
                      No students found
                    </q-item-section>
                  </q-item>
                </template>
              </q-select>
            </div>
          </div>

          <div class="form-actions">
            <q-btn
              flat
              label="Cancel"
              @click="$router.push('/app/report-cards')"
              class="q-mr-sm"
            />
            <q-btn
              type="submit"
              color="primary"
              label="Generate Report Card"
              icon="description"
              :loading="submitting"
              :disable="!form.term_id || !form.student_id"
            />
          </div>
        </q-form>
      </MobileCard>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import api from 'src/services/api';

const router = useRouter();
const $q = useQuasar();

const submitting = ref(false);
const loadingTerms = ref(false);
const loadingStudents = ref(false);
const terms = ref([]);
const students = ref([]);
const allStudents = ref([]);

const form = ref({
  term_id: null,
  student_id: null,
});

onMounted(() => {
  fetchTerms();
});

async function fetchTerms() {
  loadingTerms.value = true;
  try {
    const response = await api.get('/terms', { params: { per_page: 100 } });
    if (response.data.success) {
      terms.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch terms:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to fetch terms',
      position: 'top',
    });
  } finally {
    loadingTerms.value = false;
  }
}

async function fetchStudents() {
  if (!form.value.term_id) {
    students.value = [];
    allStudents.value = [];
    return;
  }

  loadingStudents.value = true;
  try {
    const response = await api.get('/students', { params: { per_page: 1000 } });
    if (response.data.success) {
      allStudents.value = response.data.data || [];
      students.value = allStudents.value;
    }
  } catch (error) {
    console.error('Failed to fetch students:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to fetch students',
      position: 'top',
    });
  } finally {
    loadingStudents.value = false;
  }
}

function filterStudents(val, update) {
  if (val === '') {
    update(() => {
      students.value = allStudents.value;
    });
    return;
  }

  update(() => {
    const needle = val.toLowerCase();
    students.value = allStudents.value.filter(
      (student) =>
        student.full_name?.toLowerCase().includes(needle) ||
        student.student_number?.toLowerCase().includes(needle)
    );
  });
}

function onTermChange() {
  form.value.student_id = null;
  fetchStudents();
}

async function generateReportCard() {
  submitting.value = true;
  try {
    const response = await api.post('/report-cards/generate', form.value);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Report card generated successfully',
        position: 'top',
      });
      // Navigate to view the report card
      router.push(`/app/report-cards/${form.value.student_id}/${form.value.term_id}`);
    }
  } catch (error) {
    console.error('Failed to generate report card:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to generate report card',
      position: 'top',
    });
  } finally {
    submitting.value = false;
  }
}

function formatDate(date) {
  if (!date) return '';
  return new Date(date).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
}
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
