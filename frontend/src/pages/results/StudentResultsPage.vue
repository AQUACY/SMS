<template>
  <q-page class="student-results-page" v-if="student">
    <MobilePageHeader
      :title="getStudentName(student)"
      :subtitle="`Student Number: ${student.student_number || 'N/A'}`"
      :show-back="true"
      @back="router.push('/app/results')"
    >
      <template #actions>
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
          label="Term"
          @update:model-value="fetchResults"
          :loading="loadingTerms"
          style="min-width: 150px;"
          class="mobile-only"
        />
      </template>
    </MobilePageHeader>

    <div class="desktop-only q-pa-lg">
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
    </div>

    <div class="page-content">
      <!-- Statistics Card -->
      <div class="stats-grid q-mb-md" v-if="statistics">
        <MobileCard variant="default" padding="md">
          <div class="stat-label">Total Assessments</div>
          <div class="stat-value">{{ statistics.total_assessments || 0 }}</div>
        </MobileCard>
        <MobileCard variant="default" padding="md">
          <div class="stat-label">Average Percentage</div>
          <div class="stat-value">{{ statistics.average_percentage || 0 }}%</div>
        </MobileCard>
        <MobileCard variant="default" padding="md">
          <div class="stat-label">Overall Grade</div>
          <div class="stat-value">
            <q-badge
              v-if="statistics.overall_grade"
              :color="getGradeColor(statistics.overall_grade)"
              :label="statistics.overall_grade"
              style="font-size: 1.2em; padding: 8px 16px;"
            />
            <span v-else class="text-grey-7">-</span>
          </div>
        </MobileCard>
        <MobileCard variant="default" padding="md">
          <div class="stat-label">Total Marks</div>
          <div class="stat-value">
            {{ statistics.total_obtained || 0 }} / {{ statistics.total_marks || 0 }}
          </div>
        </MobileCard>
      </div>

      <!-- Results by Subject -->
      <MobileCard variant="default" padding="md" class="q-mb-md" v-if="resultsBySubject.length > 0">
        <div class="card-title">Results by Subject</div>
        <!-- Mobile View: Card List -->
        <div class="mobile-only">
          <q-list separator>
            <q-expansion-item
              v-for="(subjectGroup, index) in resultsBySubject"
              :key="index"
              :label="subjectGroup.subject"
              :caption="`${subjectGroup.results.length} assessment(s)`"
              header-class="text-primary"
            >
              <div class="subject-results-list">
                <MobileListCard
                  v-for="result in subjectGroup.results"
                  :key="result.id"
                  :title="result.assessment?.name || 'N/A'"
                  :subtitle="`${formatType(result.assessment?.type)} | Weight: ${result.assessment?.weight || 0}%`"
                  :description="`Term: ${result.assessment?.term?.name || 'N/A'} | Date: ${formatDate(result.assessment?.assessment_date)}`"
                  icon="assignment"
                  :badge="result.grade || '-'"
                  :badge-color="result.grade ? getGradeColor(result.grade) : 'grey'"
                >
                  <template #extra>
                    <div class="result-marks">
                      <div class="marks-display">
                        {{ result.marks_obtained || '-' }} / {{ result.assessment?.total_marks || '-' }}
                      </div>
                      <div v-if="result.marks_obtained && result.assessment?.total_marks" class="percentage">
                        {{ calculatePercentage(result.marks_obtained, result.assessment.total_marks) }}%
                      </div>
                    </div>
                  </template>
                </MobileListCard>
              </div>
            </q-expansion-item>
          </q-list>
        </div>

        <!-- Desktop View: Expansion Items with Tables -->
        <div class="desktop-only">
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
        </div>
      </MobileCard>

      <!-- All Results Table -->
      <MobileCard variant="default" padding="md">
        <div class="card-title">All Results</div>
        
        <!-- Mobile View: Card List -->
        <div class="mobile-only">
          <div v-if="results.length === 0" class="empty-state">
            <q-icon name="assignment" size="64px" color="grey-5" />
            <div class="empty-text">No Results</div>
            <div class="empty-subtext">No results found for this student.</div>
          </div>
          <div v-else class="results-list">
            <MobileListCard
              v-for="result in results"
              :key="result.id"
              :title="result.assessment?.name || 'N/A'"
              :subtitle="result.assessment?.class_subject?.subject?.name || 'N/A'"
              :description="`${result.assessment?.class_subject?.class?.name || ''} | Term: ${result.assessment?.term?.name || 'N/A'}`"
              icon="assignment"
              :badge="result.grade || '-'"
              :badge-color="result.grade ? getGradeColor(result.grade) : 'grey'"
            >
              <template #extra>
                <div class="result-marks">
                  <div class="marks-display">
                    {{ result.marks_obtained || '-' }} / {{ result.assessment?.total_marks || '-' }}
                  </div>
                  <div v-if="result.marks_obtained && result.assessment?.total_marks" class="percentage">
                    {{ calculatePercentage(result.marks_obtained, result.assessment.total_marks) }}%
                  </div>
                </div>
              </template>
            </MobileListCard>
          </div>
        </div>

        <!-- Desktop View: Table -->
        <div class="desktop-only">
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
        </div>
      </MobileCard>
    </div>
  </q-page>

  <q-page v-else class="detail-loading">
    <div class="loading-center">
      <q-spinner color="primary" size="3em" />
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import MobileListCard from 'src/components/mobile/MobileListCard.vue';
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
.student-results-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.detail-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
}

.loading-center {
  display: flex;
  align-items: center;
  justify-content: center;
}

.mobile-only {
  display: block;
  
  @media (min-width: 768px) {
    display: none;
  }
}

.desktop-only {
  display: none;
  
  @media (min-width: 768px) {
    display: block;
  }
}

.page-content {
  max-width: 1400px;
  margin: 0 auto;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--spacing-md);
  
  @media (min-width: 768px) {
    grid-template-columns: repeat(4, 1fr);
  }
}

.stat-label {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  margin-bottom: var(--spacing-xs);
}

.stat-value {
  font-size: var(--font-size-lg);
  font-weight: 700;
  color: var(--text-primary);
}

.card-title {
  font-size: var(--font-size-lg);
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: var(--spacing-md);
}

.subject-results-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm);
}

.results-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.result-marks {
  text-align: right;
}

.marks-display {
  font-size: var(--font-size-base);
  font-weight: 600;
  color: var(--text-primary);
}

.percentage {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  margin-top: 2px;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-2xl);
  text-align: center;
}

.empty-text {
  font-size: var(--font-size-lg);
  font-weight: 600;
  color: var(--text-primary);
  margin-top: var(--spacing-md);
}

.empty-subtext {
  font-size: var(--font-size-base);
  color: var(--text-secondary);
  margin-top: var(--spacing-sm);
}
</style>
