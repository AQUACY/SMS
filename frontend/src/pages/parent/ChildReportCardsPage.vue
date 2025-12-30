<template>
  <q-page class="child-report-cards-page">
    <MobilePageHeader
      title="Report Cards"
      :subtitle="childName"
      :show-back="true"
      @back="router.back()"
    />

    <div class="page-content">
      <!-- Term Selection -->
      <MobileCard v-if="availableTerms.length > 0" variant="default" padding="md" class="q-mb-md">
        <div class="card-title">Select Term</div>
        <q-select
          v-model="selectedTermId"
          :options="availableTerms"
          option-label="label"
          option-value="value"
          emit-value
          map-options
          outlined
          dense
          @update:model-value="fetchReportCard"
          class="q-mb-sm"
        />
        <div v-if="selectedTerm" class="text-caption text-grey-7">
          {{ selectedTerm.academic_year?.name }} - {{ selectedTerm.name }}
        </div>
      </MobileCard>

      <!-- Skeleton Loading -->
      <div v-if="loading" class="detail-loading">
        <MobileCard v-for="i in 2" :key="i" variant="default" padding="md" class="q-mb-md">
          <q-skeleton type="rect" height="200px" class="q-mb-md" />
          <q-skeleton type="text" width="60%" />
          <q-skeleton type="text" width="40%" />
        </MobileCard>
      </div>

      <!-- Report Card Content -->
      <div v-else-if="reportCard" class="report-card-content">
        <!-- Student Info Card -->
        <MobileCard variant="default" padding="md" class="q-mb-md">
          <div class="card-title">Student Information</div>
            <div class="row q-gutter-md">
              <div class="col-12 col-sm-6">
                <div class="info-row">
                  <div class="text-caption text-grey-7">Name</div>
                  <div class="text-body1 text-weight-medium">{{ reportCard.student.full_name }}</div>
                </div>
              </div>
              <div class="col-12 col-sm-6">
                <div class="info-row">
                  <div class="text-caption text-grey-7">Student Number</div>
                  <div class="text-body1">{{ reportCard.student.student_number }}</div>
                </div>
              </div>
              <div class="col-12 col-sm-6" v-if="reportCard.student.date_of_birth">
                <div class="info-row">
                  <div class="text-caption text-grey-7">Date of Birth</div>
                  <div class="text-body1">{{ formatDate(reportCard.student.date_of_birth) }}</div>
                </div>
              </div>
              <div class="col-12 col-sm-6" v-if="reportCard.student.gender">
                <div class="info-row">
                  <div class="text-caption text-grey-7">Gender</div>
                  <div class="text-body1">{{ reportCard.student.gender }}</div>
                </div>
              </div>
            </div>
        </MobileCard>

        <!-- Statistics Card -->
        <MobileCard v-if="reportCard.statistics" variant="default" padding="md" class="q-mb-md">
          <div class="card-title">Overall Performance</div>
            <div class="row q-gutter-md">
              <div class="col-6 col-sm-3">
                <div class="stat-item">
                  <div class="text-caption text-grey-7">Subjects</div>
                  <div class="text-h6 text-weight-bold">{{ reportCard.statistics.total_subjects }}</div>
                </div>
              </div>
              <div class="col-6 col-sm-3">
                <div class="stat-item">
                  <div class="text-caption text-grey-7">Total Marks</div>
                  <div class="text-h6 text-weight-bold">{{ reportCard.statistics.obtained_marks }}/{{ reportCard.statistics.total_marks }}</div>
                </div>
              </div>
              <div class="col-6 col-sm-3">
                <div class="stat-item">
                  <div class="text-caption text-grey-7">Average</div>
                  <div class="text-h6 text-weight-bold text-primary">
                    {{ reportCard.statistics.average_percentage }}%
                  </div>
                </div>
              </div>
              <div class="col-6 col-sm-3">
                <div class="stat-item">
                  <div class="text-caption text-grey-7">Grade</div>
                  <q-badge
                    :color="getGradeColor(reportCard.statistics.grade)"
                    :label="reportCard.statistics.grade"
                    size="lg"
                    class="q-mt-xs"
                  />
                </div>
              </div>
            </div>
        </MobileCard>

        <!-- Results by Subject -->
        <MobileCard v-if="resultsBySubject.length > 0" variant="default" padding="md" class="q-mb-md">
          <div class="card-title">Subject Results</div>
          <div class="subjects-list">
            <MobileCard
              v-for="subjectGroup in resultsBySubject"
              :key="subjectGroup.subject.id"
              variant="default"
              padding="md"
              class="subject-card q-mb-sm"
            >
                  <div class="row items-center q-mb-sm">
                    <q-icon name="menu_book" size="20px" color="primary" class="q-mr-sm" />
                    <div class="col">
                      <div class="text-body1 text-weight-medium">{{ subjectGroup.subject.name }}</div>
                      <div class="text-caption text-grey-7" v-if="subjectGroup.subject.code">
                        Code: {{ subjectGroup.subject.code }}
                      </div>
                    </div>
                  </div>

                  <q-separator class="q-my-sm" />

                  <div class="q-gutter-xs">
                    <div
                      v-for="result in subjectGroup.results"
                      :key="result.id"
                      class="result-row q-pa-sm"
                    >
                      <div class="row items-center justify-between">
                        <div class="col">
                          <div class="text-body2 text-weight-medium">{{ result.assessment.name }}</div>
                          <div class="text-caption text-grey-7">
                            {{ result.assessment.type?.toUpperCase() }}
                            <span v-if="result.assessment.weight"> • {{ result.assessment.weight }}%</span>
                          </div>
                        </div>
                        <div class="text-right">
                          <div class="text-body1 text-weight-bold">
                            {{ result.marks_obtained }}/{{ result.assessment.total_marks }}
                          </div>
                          <q-badge
                            :color="getGradeColor(result.grade)"
                            :label="result.grade"
                            size="sm"
                          />
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Subject Average -->
                  <q-separator class="q-my-sm" />
                  <div class="row items-center justify-between">
                    <div class="text-body2 text-weight-medium">Subject Average</div>
                    <div class="text-h6 text-weight-bold text-primary">
                      {{ calculateSubjectAverage(subjectGroup.results) }}%
                    </div>
                  </div>
            </MobileCard>
          </div>
        </MobileCard>

        <!-- Download PDF Button -->
        <MobileCard variant="default" padding="md">
          <q-btn
            unelevated
            color="primary"
            icon="picture_as_pdf"
            label="Download PDF"
            @click="downloadPDF"
            class="full-width"
            size="lg"
            :loading="downloading"
          />
        </MobileCard>
      </div>

      <!-- Empty State -->
      <MobileCard v-else-if="!loading && selectedTermId" variant="default" padding="lg">
        <div class="empty-state">
          <q-icon name="description" size="64px" color="grey-5" />
          <div class="empty-text">No Report Card Available</div>
          <div class="empty-subtext">
            Report card has not been generated for this term yet.
          </div>
        </div>
      </MobileCard>

      <!-- Subscription Required -->
      <q-banner
        v-if="!loading && !selectedTermId && availableTerms.length === 0"
        dense
        rounded
        class="subscription-banner bg-warning text-white q-ma-md"
      >
        <template v-slot:avatar>
          <q-icon name="lock" color="white" />
        </template>
        <div class="text-body2">
          No subscription found for any term. Please subscribe to view report cards.
        </div>
      </q-banner>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import api from 'src/services/api';

const route = useRoute();
const router = useRouter();
const $q = useQuasar();

const studentId = computed(() => route.params.id);
const childName = ref('');
const loading = ref(false);
const downloading = ref(false);
const reportCard = ref(null);
const selectedTermId = ref(null);
const availableTerms = ref([]);
const selectedTerm = ref(null);

const resultsBySubject = computed(() => {
  if (!reportCard.value?.results) return [];
  
  const grouped = {};
  
  reportCard.value.results.forEach(result => {
    const subject = result.subject;
    if (!subject) return;
    
    if (!grouped[subject.id]) {
      grouped[subject.id] = {
        subject: subject,
        results: [],
      };
    }
    
    grouped[subject.id].results.push(result);
  });
  
  Object.values(grouped).forEach(group => {
    group.results.sort((a, b) => {
      const dateA = new Date(a.assessment?.created_at || 0);
      const dateB = new Date(b.assessment?.created_at || 0);
      return dateB - dateA;
    });
  });
  
  return Object.values(grouped);
});

onMounted(async () => {
  await fetchChildInfo();
  await fetchAvailableTerms();
  if (availableTerms.value.length > 0) {
    selectedTermId.value = availableTerms.value[0].value;
    await fetchReportCard();
  }
});

async function fetchChildInfo() {
  try {
    const response = await api.get('/parent/children');
    if (response.data.success) {
      const children = response.data.data || [];
      const child = children.find(c => c.id === parseInt(studentId.value));
      if (child) {
        childName.value = child.full_name || `${child.first_name} ${child.last_name}`;
      }
    }
  } catch (error) {
    console.error('Failed to fetch child info:', error);
  }
}

async function fetchAvailableTerms() {
  try {
    // Fetch both subscriptions and payments
    const [subscriptionsResponse, paymentsResponse] = await Promise.all([
      api.get('/parent/subscriptions'),
      api.get('/parent/payments'),
    ]);
    
    const termMap = new Map();
    
    // Add terms from subscriptions
    if (subscriptionsResponse.data.success) {
      const subscriptions = subscriptionsResponse.data.data || [];
      subscriptions.forEach(sub => {
        if (sub.student_id === parseInt(studentId.value) && sub.term) {
          const term = sub.term;
          if (!termMap.has(term.id)) {
            termMap.set(term.id, {
              value: term.id,
              label: `${term.academic_year?.name || ''} - ${term.name}`,
              term: term,
            });
          }
        }
      });
    }
    
    // Add terms from completed subscription payments
    if (paymentsResponse.data.success) {
      const payments = paymentsResponse.data.data || [];
      payments.forEach(payment => {
        if (
          payment.student_id === parseInt(studentId.value) &&
          payment.payment_type === 'subscription_payment' &&
          payment.status === 'completed' &&
          payment.term
        ) {
          const term = payment.term;
          if (!termMap.has(term.id)) {
            termMap.set(term.id, {
              value: term.id,
              label: `${term.academic_year?.name || ''} - ${term.name}`,
              term: term,
            });
          }
        }
      });
    }

    const childrenResponse = await api.get('/parent/children');
    if (childrenResponse.data.success) {
      const children = childrenResponse.data.data || [];
      const child = children.find(c => c.id === parseInt(studentId.value));
      if (child?.active_enrollment?.class?.academic_year?.active_term) {
        const activeTerm = child.active_enrollment.class.academic_year.active_term;
        if (!termMap.has(activeTerm.id)) {
          termMap.set(activeTerm.id, {
            value: activeTerm.id,
            label: `${activeTerm.academic_year?.name || ''} - ${activeTerm.name} (Current)`,
            term: activeTerm,
          });
        }
      }
    }

    availableTerms.value = Array.from(termMap.values());
    availableTerms.value.sort((a, b) => {
      const dateA = new Date(a.term.start_date || 0);
      const dateB = new Date(b.term.start_date || 0);
      return dateB - dateA;
    });
  } catch (error) {
    console.error('Failed to fetch available terms:', error);
  }
}

async function fetchReportCard() {
  if (!selectedTermId.value) return;
  
  loading.value = true;
  try {
    const response = await api.get(`/report-cards/student/${studentId.value}/term/${selectedTermId.value}`);
    
    if (response.data.success) {
      reportCard.value = response.data.data || null;
      
      const termOption = availableTerms.value.find(t => t.value === selectedTermId.value);
      selectedTerm.value = termOption?.term || null;
    }
  } catch (error) {
    console.error('Failed to fetch report card:', error);
    if (error.response?.status === 403) {
      $q.notify({
        type: 'warning',
        message: 'Subscription required to view report card for this term',
        position: 'top',
      });
    } else {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to fetch report card',
        position: 'top',
      });
    }
    reportCard.value = null;
  } finally {
    loading.value = false;
  }
}

async function downloadPDF() {
  if (!selectedTermId.value) return;
  
  downloading.value = true;
  try {
    // Use the API service which handles authentication automatically
    const response = await api.get('/report-cards/pdf', {
      params: {
        student_id: studentId.value,
        term_id: selectedTermId.value,
        action: 'download',
      },
      responseType: 'blob', // Important: tell axios to expect a blob response
      validateStatus: function (status) {
        // Accept 200 status codes for blob responses
        return status >= 200 && status < 300;
      },
    });
    
    // Check if the response is actually a PDF or an error JSON
    const contentType = response.headers['content-type'] || '';
    if (contentType.includes('application/json')) {
      // If it's JSON, it's likely an error response
      const text = await new Blob([response.data]).text();
      const errorData = JSON.parse(text);
      throw new Error(errorData.message || 'Failed to download PDF');
    }
    
    // Create a blob URL and trigger download
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const blobUrl = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = blobUrl;
    link.download = `report_card_${studentId.value}_${selectedTermId.value}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Clean up the blob URL after a short delay
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
    
    // Check if it's a blob error response (JSON error in blob format)
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text();
        const errorData = JSON.parse(text);
        $q.notify({
          type: 'negative',
          message: errorData.message || 'Failed to download PDF',
          position: 'top',
        });
      } catch (parseError) {
        $q.notify({
          type: 'negative',
          message: error.message || 'Failed to download PDF',
          position: 'top',
        });
      }
    } else {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || error.message || 'Failed to download PDF',
        position: 'top',
      });
    }
  } finally {
    downloading.value = false;
  }
}

function formatDate(date) {
  if (!date) return 'N/A';
  try {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    if (isNaN(dateObj.getTime())) return 'N/A';
    
    return new Date(dateObj).toLocaleDateString('en-GB', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  } catch (error) {
    return 'N/A';
  }
}

function calculateSubjectAverage(subjectResults) {
  if (subjectResults.length === 0) return 0;
  
  let totalPercentage = 0;
  let totalWeight = 0;
  
  subjectResults.forEach(result => {
    const percentage = (result.marks_obtained / result.assessment.total_marks) * 100;
    const weight = result.assessment.weight || 1;
    totalPercentage += percentage * weight;
    totalWeight += weight;
  });
  
  return totalWeight > 0 ? Math.round(totalPercentage / totalWeight) : 0;
}

function getGradeColor(grade) {
  const gradeColors = {
    'A': 'positive',
    'B': 'primary',
    'C': 'info',
    'D': 'warning',
    'E': 'orange',
    'F': 'negative',
  };
  return gradeColors[grade] || 'grey';
}
</script>

<style lang="scss" scoped>
.child-report-cards-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.page-content {
  max-width: 1200px;
  margin: 0 auto;
}

.detail-loading {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.report-card-content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.card-title {
  font-size: var(--font-size-lg);
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: var(--spacing-md);
}

.info-row {
  padding: var(--spacing-xs) 0;
}

.stat-item {
  padding: var(--spacing-md);
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
  text-align: center;
}

.subjects-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

.subject-card {
  background: var(--bg-secondary);
}

.result-row {
  border-radius: var(--radius-sm);
  background: var(--bg-card);
  padding: var(--spacing-sm);
  margin-bottom: var(--spacing-xs);
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

.subscription-banner {
  margin: var(--spacing-md);
}
</style>

