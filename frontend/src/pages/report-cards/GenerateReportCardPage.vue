<template>
  <q-page class="q-pa-lg">
    <div class="row items-center q-mb-lg">
      <q-btn
        flat
        icon="arrow_back"
        @click="$router.push('/app/report-cards')"
        class="q-mr-md"
      />
      <div>
        <div class="text-h5 text-weight-bold">Generate Report Card</div>
        <div class="text-body2 text-grey-7">Select student and term to generate report card</div>
      </div>
    </div>

    <q-card class="widget-card">
      <q-card-section>
        <q-form @submit="generateReportCard" class="q-gutter-md">
          <div class="row q-col-gutter-md">
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

          <div class="row q-mt-lg">
            <div class="col-12">
              <q-btn
                type="submit"
                color="primary"
                label="Generate Report Card"
                icon="description"
                unelevated
                :loading="submitting"
                :disable="!form.term_id || !form.student_id"
              />
              <q-btn
                flat
                label="Cancel"
                @click="$router.push('/app/report-cards')"
                class="q-ml-sm"
              />
            </div>
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
.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}
</style>
