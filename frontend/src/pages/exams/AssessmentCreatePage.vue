<template>
  <q-page class="q-pa-lg">
    <div class="row items-center q-mb-lg">
      <q-btn
        flat
        icon="arrow_back"
        @click="$router.push('/app/assessments')"
        class="q-mr-md"
      />
      <div class="text-h5 text-weight-bold">Create Assessment</div>
    </div>

    <q-card class="widget-card q-pa-md">
      <q-card-section>
        <div class="text-body2 text-grey-7 q-mb-md">
          Create a new assessment (exam, quiz, assignment, project, etc.) for a specific class, subject, and term.
        </div>

        <q-form @submit="onSubmit" class="q-gutter-md">
          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-6">
              <q-select
                v-model="form.type"
                :options="typeOptions"
                option-label="label"
                option-value="value"
                emit-value
                map-options
                label="Assessment Type *"
                outlined
                :rules="[(val) => !!val || 'Assessment type is required']"
                hint="Select the type of assessment"
              />
            </div>

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
                v-model="form.class_id"
                :options="classes"
                option-label="name"
                option-value="id"
                emit-value
                map-options
                label="Class *"
                outlined
                :rules="[(val) => !!val || 'Class is required']"
                :loading="loadingClasses"
                :disable="!form.term_id"
                @update:model-value="onClassChange"
              />
            </div>

            <div class="col-12 col-md-6">
              <q-select
                v-model="form.class_subject_id"
                :options="classSubjects"
                option-label="label"
                option-value="id"
                emit-value
                map-options
                label="Subject *"
                outlined
                :rules="[(val) => !!val || 'Subject is required']"
                :loading="loadingClassSubjects"
                :disable="!form.class_id"
                hint="Select the subject for this assessment"
              >
                <template v-slot:option="scope">
                  <q-item v-bind="scope.itemProps">
                    <q-item-section>
                      <q-item-label>{{ scope.opt.subject?.name || 'Unknown' }}</q-item-label>
                      <q-item-label caption v-if="scope.opt.teacher?.user">
                        Teacher: {{ scope.opt.teacher.user.first_name }} {{ scope.opt.teacher.user.last_name }}
                      </q-item-label>
                    </q-item-section>
                  </q-item>
                </template>
              </q-select>
            </div>

            <div class="col-12 col-md-6">
              <q-input
                v-model="form.name"
                label="Assessment Name *"
                outlined
                hint="e.g., Mid-Term Exam, Quiz 1, Assignment 2"
                :rules="[(val) => !!val || 'Assessment name is required']"
              />
            </div>

            <div class="col-12 col-md-6">
              <q-input
                v-model.number="form.total_marks"
                label="Total Marks *"
                type="number"
                outlined
                hint="Maximum score possible for this assessment (e.g., 100, 50, 30)"
                :rules="[
                  (val) => val !== null && val !== '' || 'Total marks is required',
                  (val) => val > 0 || 'Total marks must be greater than 0',
                  (val) => val <= 999.99 || 'Total marks cannot exceed 999.99',
                ]"
              />
              <div class="text-caption text-grey-7 q-mt-xs">
                Example: If the assessment is out of 100 marks, enter 100. This is the maximum score a student can get.
              </div>
            </div>

            <div class="col-12 col-md-6">
              <q-input
                v-model.number="form.weight"
                label="Weight (%) *"
                type="number"
                outlined
                hint="How much this assessment contributes to the final term grade (0-100)"
                :rules="[
                  (val) => val !== null && val !== '' || 'Weight is required',
                  (val) => val >= 0 || 'Weight cannot be negative',
                  (val) => val <= 100 || 'Weight cannot exceed 100%',
                ]"
              />
              <div class="text-caption text-grey-7 q-mt-xs">
                Example: If this assessment is worth 30% of the term grade, enter 30. All assessments in a term should total 100%.
              </div>
            </div>

            <div class="col-12 col-md-6">
              <q-input
                v-model="form.assessment_date"
                label="Assessment Date *"
                type="date"
                outlined
                :rules="[(val) => !!val || 'Assessment date is required']"
              />
            </div>

            <div class="col-12 col-md-6">
              <q-input
                v-model="form.due_date"
                label="Due Date"
                type="date"
                outlined
                hint="Optional: Date when assessment should be completed/submitted"
                :rules="[
                  (val) => !val || !form.assessment_date || val >= form.assessment_date || 'Due date must be on or after assessment date',
                ]"
              />
            </div>
          </div>

          <div class="row justify-end q-mt-lg">
            <q-btn
              flat
              label="Cancel"
              @click="$router.push('/app/assessments')"
              class="q-mr-sm"
            />
            <q-btn
              type="submit"
              color="primary"
              label="Create Assessment"
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
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import api from 'src/services/api';

const router = useRouter();
const $q = useQuasar();

const submitting = ref(false);
const loadingTerms = ref(false);
const loadingClasses = ref(false);
const loadingClassSubjects = ref(false);
const terms = ref([]);
const classes = ref([]);
const classSubjects = ref([]);

const typeOptions = [
  { label: 'Exam', value: 'exam' },
  { label: 'Quiz', value: 'quiz' },
  { label: 'Assignment', value: 'assignment' },
  { label: 'Project', value: 'project' },
  { label: 'Other', value: 'other' },
];

const form = ref({
  type: null,
  term_id: null,
  class_id: null,
  class_subject_id: null,
  name: '',
  total_marks: null,
  weight: null,
  assessment_date: '',
  due_date: '',
});

onMounted(() => {
  fetchTerms();
  fetchClasses();
});

async function fetchTerms() {
  loadingTerms.value = true;
  try {
    const response = await api.get('/terms', { params: { per_page: 100 } });
    if (response.data.success) {
      terms.value = (response.data.data || []).filter(term => 
        term.status === 'draft' || term.status === 'active'
      );
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

async function fetchClasses() {
  loadingClasses.value = true;
  try {
    const response = await api.get('/classes', { params: { per_page: 100 } });
    if (response.data.success) {
      classes.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch classes:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to fetch classes',
      position: 'top',
    });
  } finally {
    loadingClasses.value = false;
  }
}

async function onTermChange() {
  // Reset class and subject when term changes
  form.value.class_id = null;
  form.value.class_subject_id = null;
  classSubjects.value = [];
}

async function onClassChange() {
  // Reset subject when class changes
  form.value.class_subject_id = null;
  await fetchClassSubjects();
}

async function fetchClassSubjects() {
  if (!form.value.class_id) {
    classSubjects.value = [];
    return;
  }

  loadingClassSubjects.value = true;
  try {
    const response = await api.get(`/classes/${form.value.class_id}/subjects`);
    if (response.data.success) {
      // Format class subjects for the select
      classSubjects.value = (response.data.data || []).map(cs => ({
        ...cs,
        label: `${cs.subject?.name || 'Unknown'} - ${cs.class?.name || ''}`,
      }));
    }
  } catch (error) {
    console.error('Failed to fetch class subjects:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to fetch subjects for this class',
      position: 'top',
    });
  } finally {
    loadingClassSubjects.value = false;
  }
}

async function onSubmit() {
  submitting.value = true;
  try {
    const response = await api.post('/assessments', form.value);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Assessment created successfully',
        position: 'top',
      });
      router.push(`/app/assessments/${response.data.data.id}`);
    }
  } catch (error) {
    console.error('Failed to create assessment:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to create assessment',
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
