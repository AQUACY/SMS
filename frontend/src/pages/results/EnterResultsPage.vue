<template>
  <q-page class="enter-results-page">
    <MobilePageHeader
      title="Enter Results"
      subtitle="Enter marks for students in an assessment"
      :show-back="true"
      @back="router.push('/app/results')"
    />

    <div class="page-content">
      <!-- Assessment Selection -->
      <MobileCard variant="default" padding="md" class="q-mb-md">
        <div class="text-h6 q-mb-md">Select Assessment</div>
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-6">
            <q-select
              v-model="selectedAssessmentId"
              :options="assessments"
              option-label="label"
              option-value="id"
              emit-value
              map-options
              label="Assessment *"
              outlined
              :loading="loadingAssessments"
              @update:model-value="onAssessmentChange"
            >
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section>
                    <q-item-label>{{ scope.opt.name }}</q-item-label>
                    <q-item-label caption>
                      {{ scope.opt.class_subject?.subject?.name || '' }} - 
                      {{ scope.opt.class_subject?.class?.name || '' }} | 
                      {{ scope.opt.term?.name || '' }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
          </div>
          <div class="col-12 col-md-6" v-if="selectedAssessment">
            <div class="text-caption text-grey-7">Assessment Details</div>
            <div class="text-body2">
              Total Marks: <strong>{{ selectedAssessment.total_marks }}</strong> | 
              Weight: <strong>{{ selectedAssessment.weight }}%</strong>
            </div>
            <div class="text-caption text-grey-7 q-mt-xs">
              Date: {{ formatDate(selectedAssessment.assessment_date) }}
            </div>
          </div>
        </div>
      </MobileCard>

      <!-- Results Entry Form -->
      <MobileCard v-if="students.length > 0 && selectedAssessment" variant="default" padding="md">
        <div class="row items-center justify-between q-mb-md">
          <div class="text-h6">Enter Marks for Students</div>
          <div>
            <q-btn
              color="primary"
              label="Clear All"
              icon="clear_all"
              size="sm"
              outline
              @click="clearAllMarks"
              class="q-mr-sm"
            />
            <q-btn
              color="primary"
              label="Save Results"
              icon="save"
              unelevated
              :loading="submitting"
              @click="submitResults"
            />
          </div>
        </div>

        <q-table
          :rows="resultsData"
          :columns="columns"
          row-key="student_id"
          flat
          :pagination="{ rowsPerPage: 0 }"
        >
          <template v-slot:body-cell-student="props">
            <q-td :props="props">
              <div class="text-body2">{{ getStudentName(props.row.student) }}</div>
              <div class="text-caption text-grey-7">{{ props.row.student?.student_number || '' }}</div>
            </q-td>
          </template>

          <template v-slot:body-cell-marks="props">
            <q-td :props="props">
              <q-input
                v-model.number="props.row.marks_obtained"
                type="number"
                outlined
                dense
                :max="selectedAssessment.total_marks"
                :min="0"
                :step="0.01"
                :hint="`Max: ${selectedAssessment.total_marks}`"
                @update:model-value="() => calculateGradeForRow(props.row)"
                style="min-width: 120px;"
              />
            </q-td>
          </template>

          <template v-slot:body-cell-percentage="props">
            <q-td :props="props">
              <div class="text-body2">
                {{ props.row.percentage || '-' }}%
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

          <template v-slot:body-cell-remarks="props">
            <q-td :props="props">
              <q-input
                v-model="props.row.remarks"
                outlined
                dense
                placeholder="Optional remarks"
                style="min-width: 200px;"
              />
            </q-td>
          </template>
        </q-table>
      </MobileCard>

      <!-- Empty State -->
      <MobileCard v-else-if="selectedAssessmentId && !loadingStudents" variant="default" padding="lg">
        <div class="empty-state">
          <q-icon name="info" size="48px" color="grey-6" />
          <div class="empty-text">No students found</div>
          <div class="empty-subtext">No students found for this assessment's class</div>
        </div>
      </MobileCard>

      <!-- Loading State -->
      <MobileCard v-if="loadingStudents" variant="default" padding="lg">
        <div class="loading-state">
          <q-spinner color="primary" size="3em" />
          <div class="loading-text">Loading students...</div>
        </div>
      </MobileCard>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import api from 'src/services/api';

const route = useRoute();
const router = useRouter();
const $q = useQuasar();

const loadingAssessments = ref(false);
const loadingStudents = ref(false);
const submitting = ref(false);
const assessments = ref([]);
const students = ref([]);
const selectedAssessmentId = ref(null);
const selectedAssessment = ref(null);
const resultsData = ref([]);
const gradingScale = ref(null);

const columns = [
  { name: 'student', label: 'Student', field: 'student', align: 'left' },
  { name: 'marks', label: 'Marks Obtained', field: 'marks_obtained', align: 'left' },
  { name: 'percentage', label: 'Percentage', field: 'percentage', align: 'left' },
  { name: 'grade', label: 'Grade', field: 'grade', align: 'center' },
  { name: 'remarks', label: 'Remarks', field: 'remarks', align: 'left' },
];

onMounted(() => {
  fetchAssessments();
  fetchGradingScale();
  
  // Check if assessment_id is provided in query params (e.g., from exam detail page)
  if (route.query.assessment_id) {
    selectedAssessmentId.value = parseInt(route.query.assessment_id);
  }
  if (route.query.exam_id) {
    selectedAssessmentId.value = parseInt(route.query.exam_id);
  }
});

watch(selectedAssessmentId, (newVal) => {
  if (newVal) {
    onAssessmentChange();
  }
});

async function fetchAssessments() {
  loadingAssessments.value = true;
  try {
    // Fetch both assessments and exams to have a complete list
    const [assessmentsResponse, examsResponse] = await Promise.all([
      api.get('/assessments', { params: { per_page: 200 } }),
      api.get('/exams', { params: { per_page: 200 } }),
    ]);

    const allAssessments = [];
    
    if (assessmentsResponse.data.success) {
      allAssessments.push(...(assessmentsResponse.data.data || []));
    }
    
    if (examsResponse.data.success) {
      allAssessments.push(...(examsResponse.data.data || []));
    }

    // Remove duplicates (exams are also in assessments)
    const uniqueAssessments = allAssessments.filter((assessment, index, self) =>
      index === self.findIndex(a => a.id === assessment.id)
    );

    assessments.value = uniqueAssessments.map(assessment => ({
      ...assessment,
      label: `${assessment.name} - ${assessment.class_subject?.subject?.name || ''} (${assessment.class_subject?.class?.name || ''})`,
    }));
  } catch (error) {
    console.error('Failed to fetch assessments:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to fetch assessments',
      position: 'top',
    });
  } finally {
    loadingAssessments.value = false;
  }
}

async function onAssessmentChange() {
  if (!selectedAssessmentId.value) {
    selectedAssessment.value = null;
    students.value = [];
    resultsData.value = [];
    return;
  }

  // Fetch full assessment details to ensure all relationships are loaded
  try {
    // Check if it's an exam (from the list) to use the correct endpoint
    const assessmentFromList = assessments.value.find(a => a.id === selectedAssessmentId.value);
    const isExam = assessmentFromList?.type === 'exam';
    
    const endpoint = isExam ? `/exams/${selectedAssessmentId.value}` : `/assessments/${selectedAssessmentId.value}`;
    const response = await api.get(endpoint);
    
    if (response.data.success) {
      selectedAssessment.value = response.data.data;
      console.log('Loaded assessment:', selectedAssessment.value);
      console.log('Class subject:', selectedAssessment.value?.class_subject);
      console.log('Class:', selectedAssessment.value?.class_subject?.class);
      
      // Fetch students for the class
      await fetchStudents();
    } else {
      $q.notify({
        type: 'negative',
        message: 'Failed to load assessment details',
        position: 'top',
      });
    }
  } catch (error) {
    console.error('Failed to fetch assessment details:', error);
    console.error('Error details:', error.response?.data);
    
    // Fallback: try to find from list
    selectedAssessment.value = assessments.value.find(a => a.id === selectedAssessmentId.value);
    if (selectedAssessment.value) {
      console.log('Using assessment from list:', selectedAssessment.value);
      await fetchStudents();
    } else {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to load assessment details',
        position: 'top',
      });
    }
  }
}

async function fetchStudents() {
  // Try to get class_id from different possible paths
  let classId = null;
  
  // First try: class_subject.class.id (when class relationship is loaded)
  if (selectedAssessment.value?.class_subject?.class?.id) {
    classId = selectedAssessment.value.class_subject.class.id;
  }
  // Second try: class_subject.class_id (direct field)
  else if (selectedAssessment.value?.class_subject?.class_id) {
    classId = selectedAssessment.value.class_subject.class_id;
  }
  // Third try: if we have class_subject_id but not the relationship, fetch it
  else if (selectedAssessment.value?.class_subject_id) {
    console.warn('Class relationship not loaded, attempting to fetch class subject...');
    // The assessment should have been fetched with relationships, but if not, we'll handle it
    $q.notify({
      type: 'warning',
      message: 'Class information not fully loaded. Please try selecting the assessment again.',
      position: 'top',
    });
    return;
  }

  if (!classId) {
    console.error('Could not determine class ID from assessment:', selectedAssessment.value);
    $q.notify({
      type: 'negative',
      message: 'Could not determine class for this assessment. Please ensure the assessment has a valid class assignment.',
      position: 'top',
    });
    return;
  }

  loadingStudents.value = true;
  try {
    const response = await api.get(`/classes/${classId}/students`);
    
    if (response.data.success) {
      students.value = response.data.data || [];
      
      // Initialize results data
      resultsData.value = students.value.map(student => {
        // Check if result already exists
        const existingResult = null; // Could fetch existing results here
        
        return {
          student_id: student.id,
          student: student,
          marks_obtained: existingResult?.marks_obtained || null,
          grade: existingResult?.grade || null,
          percentage: existingResult ? calculatePercentage(existingResult.marks_obtained, selectedAssessment.value.total_marks) : null,
          remarks: existingResult?.remarks || '',
        };
      });

      // Fetch existing results if any
      await fetchExistingResults();
    }
  } catch (error) {
    console.error('Failed to fetch students:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to fetch students for this class',
      position: 'top',
    });
  } finally {
    loadingStudents.value = false;
  }
}

async function fetchExistingResults() {
  if (!selectedAssessmentId.value) return;

  try {
    const response = await api.get(`/assessments/${selectedAssessmentId.value}/results`);
    if (response.data.success) {
      const existingResults = response.data.data || [];
      
      // Update resultsData with existing results
      resultsData.value = resultsData.value.map(row => {
        const existing = existingResults.find(r => r.student_id === row.student_id);
        if (existing) {
          return {
            ...row,
            marks_obtained: existing.marks_obtained,
            grade: existing.grade,
            percentage: calculatePercentage(existing.marks_obtained, selectedAssessment.value.total_marks),
            remarks: existing.remarks || '',
          };
        }
        return row;
      });
    }
  } catch (error) {
    console.error('Failed to fetch existing results:', error);
  }
}

function calculateGradeForRow(row) {
  if (!row.marks_obtained || !selectedAssessment.value?.total_marks) {
    row.percentage = null;
    row.grade = null;
    return;
  }

  row.percentage = calculatePercentage(row.marks_obtained, selectedAssessment.value.total_marks);
  row.grade = calculateGrade(row.marks_obtained, selectedAssessment.value.total_marks);
}

function calculatePercentage(obtained, total) {
  if (!obtained || !total) return null;
  return ((obtained / total) * 100).toFixed(1);
}

// Fetch grading scale for preview (backend will calculate final grade)
async function fetchGradingScale() {
  try {
    const response = await api.get('/grading-scales');
    if (response.data.success) {
      // Find the default grading scale
      gradingScale.value = (response.data.data || []).find(scale => scale.is_default && scale.is_active);
    }
  } catch (error) {
    console.error('Failed to fetch grading scale:', error);
  }
}

function calculateGrade(marksObtained, totalMarks) {
  const percentage = (marksObtained / totalMarks) * 100;
  
  // Use configured grading scale if available
  if (gradingScale.value && gradingScale.value.grade_levels) {
    const gradeLevels = [...gradingScale.value.grade_levels].sort((a, b) => 
      (b.min_percentage || 0) - (a.min_percentage || 0)
    );
    
    for (const level of gradeLevels) {
      if (percentage >= level.min_percentage) {
        if (level.max_percentage === null || percentage <= level.max_percentage) {
          return level.grade;
        }
      }
    }
    
    // If no match, return lowest grade
    const lowestGrade = gradeLevels[gradeLevels.length - 1];
    return lowestGrade ? lowestGrade.grade : 'F';
  }
  
  // Fallback to default grading
  if (percentage >= 80) return 'A';
  if (percentage >= 70) return 'B';
  if (percentage >= 60) return 'C';
  if (percentage >= 50) return 'D';
  if (percentage >= 40) return 'E';
  return 'F';
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

function clearAllMarks() {
  $q.dialog({
    title: 'Clear All Marks',
    message: 'Are you sure you want to clear all entered marks?',
    cancel: true,
    persistent: true,
  }).onOk(() => {
    resultsData.value.forEach(row => {
      row.marks_obtained = null;
      row.grade = null;
      row.percentage = null;
      row.remarks = '';
    });
  });
}

async function submitResults() {
  if (!selectedAssessment.value) {
    $q.notify({
      type: 'negative',
      message: 'Please select an assessment first',
      position: 'top',
    });
    return;
  }

  // Validate that at least some marks are entered
  const hasMarks = resultsData.value.some(row => row.marks_obtained !== null && row.marks_obtained !== '');
  if (!hasMarks) {
    $q.notify({
      type: 'negative',
      message: 'Please enter marks for at least one student',
      position: 'top',
    });
    return;
  }

  // Validate marks don't exceed total
  const invalidRows = resultsData.value.filter(row => 
    row.marks_obtained !== null && 
    row.marks_obtained > selectedAssessment.value.total_marks
  );

  if (invalidRows.length > 0) {
    $q.notify({
      type: 'negative',
      message: `Marks cannot exceed ${selectedAssessment.value.total_marks} for some students`,
      position: 'top',
    });
    return;
  }

  submitting.value = true;
  try {
    const payload = {
      assessment_id: selectedAssessment.value.id,
      total_marks: selectedAssessment.value.total_marks,
      results: resultsData.value
        .filter(row => row.marks_obtained !== null && row.marks_obtained !== '')
        .map(row => ({
          student_id: row.student_id,
          marks_obtained: parseFloat(row.marks_obtained),
          remarks: row.remarks || null,
        })),
    };

    const response = await api.post('/results/enter', payload);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Results entered successfully',
        position: 'top',
      });
      
      // Optionally navigate back or refresh
      router.push('/app/results');
    }
  } catch (error) {
    console.error('Failed to submit results:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to enter results',
      position: 'top',
    });
  } finally {
    submitting.value = false;
  }
}

function formatDate(date) {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
}
</script>

<style lang="scss" scoped>
.enter-results-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.page-content {
  max-width: 1200px;
  margin: 0 auto;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-xl);
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

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-xl);
  text-align: center;
}

.loading-text {
  font-size: var(--font-size-base);
  color: var(--text-secondary);
  margin-top: var(--spacing-md);
}
</style>
