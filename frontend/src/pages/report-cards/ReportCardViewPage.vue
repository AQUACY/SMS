<template>
  <q-page class="q-pa-lg" v-if="reportCard">
    <div class="row items-center q-mb-lg">
      <q-btn
        flat
        icon="arrow_back"
        @click="$router.push('/app/report-cards')"
        class="q-mr-md"
      />
      <div>
        <div class="text-h5 text-weight-bold">Report Card</div>
        <div class="text-body2 text-grey-7">
          {{ reportCard.student?.full_name || 'N/A' }} - {{ reportCard.term?.name || 'N/A' }}
        </div>
      </div>
      <q-space />
      <q-btn
        flat
        label="Preview PDF"
        color="primary"
        icon="visibility"
        :loading="generatingPdf"
        @click="previewPdf"
        class="q-mr-sm"
      />
      <q-btn
        flat
        label="Download PDF"
        color="negative"
        icon="download"
        :loading="generatingPdf"
        @click="downloadPdf"
      />
    </div>

    <div class="row q-col-gutter-md">
      <!-- Student Information -->
      <div class="col-12 col-md-4">
        <q-card class="widget-card">
          <q-card-section>
            <div class="text-h6 q-mb-md">Student Information</div>
            <div class="q-gutter-sm">
              <div>
                <div class="text-caption text-grey-7">Name</div>
                <div class="text-body1">{{ reportCard.student?.full_name || 'N/A' }}</div>
              </div>
              <div>
                <div class="text-caption text-grey-7">Student Number</div>
                <div class="text-body1">{{ reportCard.student?.student_number || 'N/A' }}</div>
              </div>
              <div v-if="reportCard.student?.date_of_birth">
                <div class="text-caption text-grey-7">Date of Birth</div>
                <div class="text-body1">{{ formatDate(reportCard.student.date_of_birth) }}</div>
              </div>
              <div v-if="reportCard.student?.gender">
                <div class="text-caption text-grey-7">Gender</div>
                <div class="text-body1">{{ reportCard.student.gender }}</div>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <q-card class="widget-card q-mt-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">Term Information</div>
            <div class="q-gutter-sm">
              <div>
                <div class="text-caption text-grey-7">Term</div>
                <div class="text-body1">{{ reportCard.term?.name || 'N/A' }}</div>
              </div>
              <div>
                <div class="text-caption text-grey-7">Academic Year</div>
                <div class="text-body1">{{ reportCard.term?.academic_year?.name || 'N/A' }}</div>
              </div>
              <div v-if="reportCard.term?.start_date">
                <div class="text-caption text-grey-7">Start Date</div>
                <div class="text-body1">{{ formatDate(reportCard.term.start_date) }}</div>
              </div>
              <div v-if="reportCard.term?.end_date">
                <div class="text-caption text-grey-7">End Date</div>
                <div class="text-body1">{{ formatDate(reportCard.term.end_date) }}</div>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Results and Statistics -->
      <div class="col-12 col-md-8">
        <!-- Summary Statistics -->
        <q-card class="widget-card q-mb-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">Summary</div>
            <div class="row q-col-gutter-md">
              <div class="col-6 col-md-3">
                <div class="text-caption text-grey-7">Total Subjects</div>
                <div class="text-h6 text-weight-bold">{{ reportCard.statistics?.total_subjects || 0 }}</div>
              </div>
              <div class="col-6 col-md-3">
                <div class="text-caption text-grey-7">Total Marks</div>
                <div class="text-h6 text-weight-bold">
                  {{ reportCard.statistics?.obtained_marks || 0 }} / {{ reportCard.statistics?.total_marks || 0 }}
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="text-caption text-grey-7">Average</div>
                <div class="text-h6 text-weight-bold">{{ reportCard.statistics?.average_percentage || 0 }}%</div>
              </div>
              <div class="col-6 col-md-3">
                <div class="text-caption text-grey-7">Overall Grade</div>
                <div class="text-h6">
                  <q-badge
                    v-if="reportCard.statistics?.grade"
                    :color="getGradeColor(reportCard.statistics.grade)"
                    :label="reportCard.statistics.grade"
                    style="font-size: 16px; padding: 8px 16px;"
                  />
                  <span v-else class="text-grey-7">-</span>
                </div>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Results Table -->
        <q-card class="widget-card">
          <q-card-section>
            <div class="text-h6 q-mb-md">Subject Results</div>
            <q-table
              v-if="reportCard.results && reportCard.results.length > 0"
              :rows="reportCard.results"
              :columns="resultColumns"
              row-key="id"
              flat
            >
              <template v-slot:body-cell-subject="props">
                <q-td :props="props">
                  <div class="text-body2">{{ props.row.assessment?.subject?.name || 'N/A' }}</div>
                  <div class="text-caption text-grey-7" v-if="props.row.assessment?.subject?.code">
                    {{ props.row.assessment.subject.code }}
                  </div>
                </q-td>
              </template>

              <template v-slot:body-cell-assessment="props">
                <q-td :props="props">
                  <div class="text-body2">{{ props.row.assessment?.name || 'N/A' }}</div>
                  <div class="text-caption text-grey-7">{{ props.row.assessment?.type || '' }}</div>
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
            </q-table>

            <div v-else class="text-body2 text-grey-7 text-center q-pa-lg">
              No results found for this term.
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>

  <q-page v-else class="q-pa-lg flex flex-center">
    <q-spinner color="primary" size="3em" />
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'src/stores/auth';
import api from 'src/services/api';

const route = useRoute();
const router = useRouter();
const $q = useQuasar();
const authStore = useAuthStore();

const loading = ref(false);
const generatingPdf = ref(false);
const reportCard = ref(null);

const resultColumns = [
  { name: 'subject', label: 'Subject', field: 'subject', align: 'left' },
  { name: 'assessment', label: 'Assessment', field: 'assessment', align: 'left' },
  { name: 'marks', label: 'Marks', field: 'marks', align: 'left' },
  { name: 'grade', label: 'Grade', field: 'grade', align: 'center' },
  { name: 'remarks', label: 'Remarks', field: 'remarks', align: 'left' },
];

onMounted(() => {
  fetchReportCard();
});

async function fetchReportCard() {
  loading.value = true;
  try {
    const studentId = route.params.studentId;
    const termId = route.params.termId;
    const response = await api.get(`/report-cards/student/${studentId}/term/${termId}`);
    if (response.data.success) {
      reportCard.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to fetch report card:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch report card',
      position: 'top',
    });
    router.push('/app/report-cards');
  } finally {
    loading.value = false;
  }
}

function buildPdfUrl(action) {
  const baseURL = api.defaults.baseURL || 'http://localhost:8000/api';
  const studentId = route.params.studentId;
  const termId = route.params.termId;
  return `${baseURL}/report-cards/pdf?student_id=${studentId}&term_id=${termId}&action=${action}`;
}

const previewPdf = async () => {
  generatingPdf.value = true;
  
  try {
    const token = authStore.token;
    let url = buildPdfUrl('preview');
    
    // Add token to URL for authentication (temporary for preview only)
    url += `&token=${encodeURIComponent(token)}`;

    // Open the backend URL directly in a new tab
    const newWindow = window.open(url, '_blank');
    
    if (!newWindow) {
      throw new Error('Popup blocked. Please allow popups for this site to preview PDFs.');
    }

    $q.notify({
      type: 'positive',
      message: 'PDF opened in new tab',
      position: 'top',
    });
  } catch (error) {
    console.error('Failed to preview PDF:', error);
    $q.notify({
      type: 'negative',
      message: error.message || 'Failed to preview PDF. Please try again.',
      position: 'top',
    });
  } finally {
    generatingPdf.value = false;
  }
};

const downloadPdf = async () => {
  generatingPdf.value = true;
  
  try {
    const token = authStore.token;
    const url = buildPdfUrl('download'); // Action is 'download'

    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/pdf',
      },
    });

    // Check if response is actually a PDF
    const contentType = response.headers.get('content-type');
    if (!response.ok || !contentType || !contentType.includes('application/pdf')) {
      // Try to get error message from response
      let errorMessage = 'Failed to generate PDF';
      try {
        const errorText = await response.text();
        // Check if it's HTML (error page)
        if (errorText.includes('<!DOCTYPE') || errorText.includes('<html')) {
          errorMessage = 'Server error occurred while generating PDF. Please check the server logs.';
        } else {
          // Try to parse as JSON
          const errorJson = JSON.parse(errorText);
          errorMessage = errorJson.message || errorMessage;
        }
      } catch (e) {
        // If we can't parse the error, use default message
        errorMessage = `Server returned error: ${response.status} ${response.statusText}`;
      }
      throw new Error(errorMessage);
    }

    const blob = await response.blob();
    const blobUrl = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = blobUrl;
    const studentId = route.params.studentId;
    const termId = route.params.termId;
    link.download = `report_card_${studentId}_${termId}_${new Date().toISOString().split('T')[0]}.pdf`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => {
      window.URL.revokeObjectURL(blobUrl);
    }, 100);

    $q.notify({
      type: 'positive',
      message: 'PDF downloaded successfully',
      position: 'top',
    });
  } catch (error) {
    console.error('Failed to download PDF:', error);
    $q.notify({
      type: 'negative',
      message: error.message || 'Failed to download PDF. Please try again.',
      position: 'top',
    });
  } finally {
    generatingPdf.value = false;
  }
};

function formatDate(date) {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
}

function calculatePercentage(obtained, total) {
  if (!total || total === 0) return 0;
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
</script>

<style lang="scss" scoped>
.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}
</style>
