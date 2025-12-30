<template>
  <q-page class="child-results-page">
    <MobilePageHeader
      title="Results"
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
          @update:model-value="fetchResults"
          class="q-mb-sm"
        />
        <div v-if="selectedTerm" class="text-caption text-grey-7">
          {{ selectedTerm.academic_year?.name }} - {{ selectedTerm.name }}
        </div>
      </MobileCard>

      <!-- Skeleton Loading -->
      <div v-if="loading" class="detail-loading">
        <MobileCard v-for="i in 2" :key="i" variant="default" padding="md" class="q-mb-md">
          <q-skeleton type="rect" height="100px" class="q-mb-md" />
          <q-skeleton type="text" width="60%" />
          <q-skeleton type="text" width="40%" />
        </MobileCard>
      </div>

      <!-- Results by Subject -->
      <div v-else-if="resultsBySubject.length > 0" class="results-list">
        <MobileCard
          v-for="subjectGroup in resultsBySubject"
          :key="subjectGroup.subject.id"
          variant="default"
          padding="md"
          class="q-mb-md"
        >
            <div class="row items-center q-mb-md">
              <q-icon name="menu_book" size="24px" color="primary" class="q-mr-sm" />
              <div class="col">
                <div class="text-h6 text-weight-medium">{{ subjectGroup.subject.name }}</div>
                <div class="text-caption text-grey-7" v-if="subjectGroup.subject.code">
                  Code: {{ subjectGroup.subject.code }}
                </div>
              </div>
            </div>

            <q-separator class="q-mb-md" />

            <div class="q-gutter-sm">
              <div
                v-for="result in subjectGroup.results"
                :key="result.id"
                class="result-item q-pa-md q-mb-sm"
              >
                <div class="row items-center justify-between">
                  <div class="col">
                    <div class="text-body1 text-weight-medium">{{ result.assessment.name }}</div>
                    <div class="text-caption text-grey-7">
                      {{ result.assessment.type?.toUpperCase() || 'Assessment' }}
                      <span v-if="result.assessment.weight"> • Weight: {{ result.assessment.weight }}%</span>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="text-h6 text-weight-bold">
                      {{ result.marks_obtained }} / {{ result.assessment.total_marks }}
                    </div>
                    <q-badge
                      :color="getGradeColor(result.grade)"
                      :label="result.grade"
                      size="md"
                    />
                  </div>
                </div>
                <div v-if="result.remarks" class="text-caption text-grey-7 q-mt-sm">
                  {{ result.remarks }}
                </div>
              </div>
            </div>

            <!-- Subject Summary -->
            <q-separator class="q-my-md" />
            <div class="row items-center justify-between">
              <div class="text-body1 text-weight-medium">Subject Average</div>
              <div class="text-h6 text-weight-bold text-primary">
                {{ calculateSubjectAverage(subjectGroup.results) }}%
              </div>
            </div>
        </MobileCard>

        <!-- Overall Summary -->
        <MobileCard variant="default" padding="md" class="q-mb-md">
          <div class="card-title">Overall Summary</div>
            <div class="row q-gutter-md">
              <div class="col-6">
                <div class="stat-item">
                  <div class="text-caption text-grey-7">Total Subjects</div>
                  <div class="text-h6 text-weight-bold">{{ resultsBySubject.length }}</div>
                </div>
              </div>
              <div class="col-6">
                <div class="stat-item">
                  <div class="text-caption text-grey-7">Overall Average</div>
                  <div class="text-h6 text-weight-bold text-primary">
                    {{ calculateOverallAverage() }}%
                  </div>
                </div>
              </div>
            </div>
        </MobileCard>
      </div>

      <!-- Empty State -->
      <MobileCard v-else-if="!loading && selectedTermId" variant="default" padding="lg">
        <div class="empty-state">
          <q-icon name="assessment" size="64px" color="grey-5" />
          <div class="empty-text">No Results Found</div>
          <div class="empty-subtext">
            No results available for this term yet.
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
          No subscription found for any term. Please subscribe to view results.
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
const results = ref([]);
const selectedTermId = ref(null);
const availableTerms = ref([]);
const selectedTerm = ref(null);

const resultsBySubject = computed(() => {
  const grouped = {};
  
  results.value.forEach(result => {
    const subject = result.assessment?.class_subject?.subject;
    if (!subject) return;
    
    if (!grouped[subject.id]) {
      grouped[subject.id] = {
        subject: subject,
        results: [],
      };
    }
    
    grouped[subject.id].results.push(result);
  });
  
  // Sort results by assessment date/name
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
    await fetchResults();
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

    // Add current term from child's enrollment
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

async function fetchResults() {
  if (!selectedTermId.value) return;
  
  loading.value = true;
  try {
    const response = await api.get(`/results/student/${studentId.value}/term/${selectedTermId.value}`);
    
    if (response.data.success) {
      results.value = response.data.data || [];
      
      const termOption = availableTerms.value.find(t => t.value === selectedTermId.value);
      selectedTerm.value = termOption?.term || null;
    }
  } catch (error) {
    console.error('Failed to fetch results:', error);
    if (error.response?.status === 403) {
      $q.notify({
        type: 'warning',
        message: 'Subscription required to view results for this term',
        position: 'top',
      });
    } else {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to fetch results',
        position: 'top',
      });
    }
  } finally {
    loading.value = false;
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

function calculateOverallAverage() {
  if (resultsBySubject.value.length === 0) return 0;
  
  const subjectAverages = resultsBySubject.value.map(group => 
    calculateSubjectAverage(group.results)
  );
  
  const sum = subjectAverages.reduce((acc, avg) => acc + avg, 0);
  return Math.round(sum / subjectAverages.length);
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
.child-results-page {
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

.results-list {
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

.stat-item {
  padding: var(--spacing-md);
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
  text-align: center;
}

.result-item {
  border-radius: var(--radius-md);
  border: 1px solid var(--border-light);
  background: var(--bg-secondary);
  transition: all var(--transition-base);
  padding: var(--spacing-md);
  margin-bottom: var(--spacing-sm);
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

