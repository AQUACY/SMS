<template>
  <q-page class="q-pa-lg" v-if="student">
    <div class="row items-center q-mb-lg">
      <q-btn
        flat
        icon="arrow_back"
        @click="router.push('/app/results')"
        class="q-mr-md"
      />
      <div>
        <div class="text-h5 text-weight-bold">{{ getStudentName(student) }}</div>
        <div class="text-body2 text-grey-7">
          Student Number: {{ student.student_number || 'N/A' }}
        </div>
      </div>
      <q-space />
      <q-select
        v-model="selectedTermId"
        :options="terms"
        option-label="name"
        option-value="id"
        emit-value
        map-options
        outlined
        dense
        clearable
        label="Filter by Term"
        @update:model-value="fetchResults"
        :loading="loadingTerms"
        style="min-width: 200px;"
      />
    </div>

    <!-- Statistics Card -->
    <div class="row q-col-gutter-md q-mb-md" v-if="statistics">
      <div class="col-12 col-md-3">
        <q-card class="widget-card">
          <q-card-section>
            <div class="text-caption text-grey-7">Total Assessments</div>
            <div class="text-h6">{{ statistics.total_assessments || 0 }}</div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-3">
        <q-card class="widget-card">
          <q-card-section>
            <div class="text-caption text-grey-7">Average Percentage</div>
            <div class="text-h6">{{ statistics.average_percentage || 0 }}%</div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-3">
        <q-card class="widget-card">
          <q-card-section>
            <div class="text-caption text-grey-7">Overall Grade</div>
            <div class="text-h6">
              <q-badge
                v-if="statistics.overall_grade"
                :color="getGradeColor(statistics.overall_grade)"
                :label="statistics.overall_grade"
                style="font-size: 1.2em; padding: 8px 16px;"
              />
              <span v-else class="text-grey-7">-</span>
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-3">
        <q-card class="widget-card">
          <q-card-section>
            <div class="text-caption text-grey-7">Total Marks</div>
            <div class="text-h6">
              {{ statistics.total_obtained || 0 }} / {{ statistics.total_marks || 0 }}
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Results by Subject -->
    <q-card class="widget-card q-mb-md" v-if="resultsBySubject.length > 0">
      <q-card-section>
        <div class="text-h6 q-mb-md">Results by Subject</div>
        <q-list separator>
          <q-expansion-item
            v-for="(subjectGroup, index) in resultsBySubject"
            :key="index"
            :label="subjectGroup.subject"
            :caption="`${subjectGroup.results.length} assessment(s)`"
            header-class="text-primary"
          >
            <q-card>
              <q-card-section>
                <q-table
                  :rows="subjectGroup.results"
                  :columns="columns"
                  row-key="id"
                  flat
                  :pagination="{ rowsPerPage: 0 }"
                >
                  <template v-slot:body-cell-assessment="props">
                    <q-td :props="props">
                      <div class="text-body2">{{ props.row.assessment?.name || 'N/A' }}</div>
                      <div class="text-caption text-grey-7">
                        {{ formatType(props.row.assessment?.type) }} | 
                        Weight: {{ props.row.assessment?.weight }}%
                      </div>
                    </q-td>
                  </template>

                  <template v-slot:body-cell-marks="props">
                    <q-td :props="props">
                      <div class="text-body2">
                        {{ props.row.marks_obtained || '-' }} / {{ props.row.assessment?.total_marks || '-' }}
                      </div>
                      <div class="text-caption text-grey-7" v-if="props.row.marks_obtained && props.row.assessment?.total_marks">
                        {{ calculatePercentage(props.row.marks_obtained, props.row.assessment.total_marks) }}%
                      </div>
                    </q-td>
                  </template>

                  <template v-slot:body-cell-grade="props">
                    <q-td :props="props">
                      <q-badge
                        v-if="props.row.grade"
                        :color="getGradeColor(props.row.grade)"
                        :label="props.row.grade"
                      />
                      <span v-else class="text-grey-7">-</span>
                    </q-td>
                  </template>

                  <template v-slot:body-cell-term="props">
                    <q-td :props="props">
                      <div class="text-body2">{{ props.row.assessment?.term?.name || 'N/A' }}</div>
                      <div class="text-caption text-grey-7">
                        {{ formatDate(props.row.assessment?.assessment_date) }}
                      </div>
                    </q-td>
                  </template>
                </q-table>
              </q-card-section>
            </q-card>
          </q-expansion-item>
        </q-list>
      </q-card-section>
    </q-card>

    <!-- All Results Table -->
    <q-card class="widget-card">
      <q-card-section>
        <div class="text-h6 q-mb-md">All Results</div>
        <q-table
          :rows="results"
          :columns="columns"
          row-key="id"
          :loading="loading"
          flat
        >
          <template v-slot:body-cell-assessment="props">
            <q-td :props="props">
              <div class="text-body2">{{ props.row.assessment?.name || 'N/A' }}</div>
              <div class="text-caption text-grey-7">
                {{ props.row.assessment?.class_subject?.subject?.name || '' }} - 
                {{ props.row.assessment?.class_subject?.class?.name || '' }}
              </div>
            </q-td>
          </template>

          <template v-slot:body-cell-marks="props">
            <q-td :props="props">
              <div class="text-body2">
                {{ props.row.marks_obtained || '-' }} / {{ props.row.assessment?.total_marks || '-' }}
              </div>
              <div class="text-caption text-grey-7" v-if="props.row.marks_obtained && props.row.assessment?.total_marks">
                {{ calculatePercentage(props.row.marks_obtained, props.row.assessment.total_marks) }}%
              </div>
            </q-td>
          </template>

          <template v-slot:body-cell-grade="props">
            <q-td :props="props">
              <q-badge
                v-if="props.row.grade"
                :color="getGradeColor(props.row.grade)"
                :label="props.row.grade"
              />
              <span v-else class="text-grey-7">-</span>
            </q-td>
          </template>

          <template v-slot:body-cell-term="props">
            <q-td :props="props">
              <div class="text-body2">{{ props.row.assessment?.term?.name || 'N/A' }}</div>
              <div class="text-caption text-grey-7">
                {{ formatDate(props.row.assessment?.assessment_date) }}
              </div>
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>
  </q-page>

  <q-page v-else class="q-pa-lg flex flex-center">
    <q-spinner color="primary" size="3em" />
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

const loading = ref(false);
const loadingTerms = ref(false);
const student = ref(null);
const results = ref([]);
const terms = ref([]);
const selectedTermId = ref(null);
const statistics = ref(null);

const columns = [
  { name: 'assessment', label: 'Assessment', field: 'assessment', align: 'left' },
  { name: 'marks', label: 'Marks', field: 'marks', align: 'left' },
  { name: 'grade', label: 'Grade', field: 'grade', align: 'center' },
  { name: 'term', label: 'Term / Date', field: 'term', align: 'left' },
  { name: 'remarks', label: 'Remarks', field: 'remarks', align: 'left' },
];

const resultsBySubject = computed(() => {
  const grouped = {};
  
  results.value.forEach(result => {
    const subjectName = result.assessment?.class_subject?.subject?.name || 'Unknown';
    if (!grouped[subjectName]) {
      grouped[subjectName] = {
        subject: subjectName,
        results: [],
      };
    }
    grouped[subjectName].results.push(result);
  });

  return Object.values(grouped);
});

onMounted(() => {
  fetchStudent();
  fetchTerms();
  fetchResults();
});

async function fetchStudent() {
  try {
    const response = await api.get(`/students/${route.params.studentId}`);
    if (response.data.success) {
      student.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to fetch student:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to fetch student information',
      position: 'top',
    });
    router.push('/app/results');
  }
}

async function fetchTerms() {
  loadingTerms.value = true;
  try {
    const response = await api.get('/terms', { params: { per_page: 100 } });
    if (response.data.success) {
      terms.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch terms:', error);
  } finally {
    loadingTerms.value = false;
  }
}

async function fetchResults() {
  loading.value = true;
  try {
    const params = {};
    if (selectedTermId.value) {
      params.term_id = selectedTermId.value;
    }

    const response = await api.get(`/results/student/${route.params.studentId}`, { params });
    if (response.data.success) {
      results.value = response.data.data || [];
      calculateStatistics();
    }
  } catch (error) {
    console.error('Failed to fetch results:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch results',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
}

function calculateStatistics() {
  if (results.value.length === 0) {
    statistics.value = {
      total_assessments: 0,
      average_percentage: 0,
      overall_grade: null,
      total_obtained: 0,
      total_marks: 0,
    };
    return;
  }

  let totalObtained = 0;
  let totalMarks = 0;
  let totalPercentage = 0;
  let count = 0;

  results.value.forEach(result => {
    if (result.marks_obtained !== null && result.assessment?.total_marks) {
      totalObtained += parseFloat(result.marks_obtained);
      totalMarks += parseFloat(result.assessment.total_marks);
      const percentage = (result.marks_obtained / result.assessment.total_marks) * 100;
      totalPercentage += percentage;
      count++;
    }
  });

  const averagePercentage = count > 0 ? totalPercentage / count : 0;
  const overallGrade = calculateGradeFromPercentage(averagePercentage);

  statistics.value = {
    total_assessments: results.value.length,
    average_percentage: averagePercentage.toFixed(1),
    overall_grade: overallGrade,
    total_obtained: totalObtained.toFixed(1),
    total_marks: totalMarks.toFixed(1),
  };
}

function calculateGradeFromPercentage(percentage) {
  if (percentage >= 80) return 'A';
  if (percentage >= 70) return 'B';
  if (percentage >= 60) return 'C';
  if (percentage >= 50) return 'D';
  if (percentage >= 40) return 'E';
  return 'F';
}

function calculatePercentage(obtained, total) {
  if (!obtained || !total) return '0';
  return ((obtained / total) * 100).toFixed(1);
}

function getGradeColor(grade) {
  const colors = {
    A: 'positive',
    B: 'positive',
    C: 'info',
    D: 'warning',
    E: 'negative',
    F: 'negative',
  };
  return colors[grade] || 'grey';
}

function getStudentName(student) {
  if (!student) return 'N/A';
  if (student.full_name) return student.full_name;
  return `${student.first_name || ''} ${student.last_name || ''}`.trim() || 'N/A';
}

function formatDate(date) {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
}

function formatType(type) {
  if (!type) return '';
  return type.charAt(0).toUpperCase() + type.slice(1);
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
