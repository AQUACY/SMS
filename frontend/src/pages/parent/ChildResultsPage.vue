<template>
  <q-page class="parent-page">
    <!-- Mobile Header -->
    <div class="parent-header q-pa-md">
      <div class="row items-center">
        <q-btn
          flat
          round
          icon="arrow_back"
          @click="router.back()"
          class="q-mr-sm"
          size="md"
        />
        <div class="col">
          <div class="text-h6 text-weight-bold">Results</div>
          <div class="text-caption text-grey-7">{{ childName }}</div>
        </div>
      </div>
    </div>

    <!-- Content Area -->
    <div class="parent-content q-pa-md">
      <!-- Term Selection -->
      <q-card class="info-card q-mb-md" v-if="availableTerms.length > 0">
        <q-card-section class="q-pa-md">
          <div class="text-body1 text-weight-medium q-mb-sm">Select Term</div>
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
        </q-card-section>
      </q-card>

      <!-- Skeleton Loading -->
      <div v-if="loading" class="q-gutter-md">
        <q-card class="info-card">
          <q-card-section>
            <q-skeleton type="rect" height="100px" class="q-mb-md" />
            <q-skeleton type="text" width="60%" />
            <q-skeleton type="text" width="40%" />
          </q-card-section>
        </q-card>
      </div>

      <!-- Results by Subject -->
      <div v-else-if="resultsBySubject.length > 0" class="q-gutter-md">
        <q-card
          v-for="subjectGroup in resultsBySubject"
          :key="subjectGroup.subject.id"
          class="info-card q-mt-lg"
        >
          <q-card-section class="q-pa-md">
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
          </q-card-section>
        </q-card>

        <!-- Overall Summary -->
        <q-card class="info-card">
          <q-card-section class="q-pa-md">
            <div class="text-h6 q-mb-md">Overall Summary</div>
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
          </q-card-section>
        </q-card>
      </div>

      <!-- Empty State -->
      <q-card v-else-if="!loading && selectedTermId" class="info-card">
        <q-card-section class="text-center q-pa-xl">
          <q-icon name="assessment" size="64px" color="grey-5" class="q-mb-md" />
          <div class="text-h6 text-grey-7 q-mb-sm">No Results Found</div>
          <div class="text-body2 text-grey-6">
            No results available for this term yet.
          </div>
        </q-card-section>
      </q-card>

      <!-- Subscription Required -->
      <q-banner
        v-if="!loading && !selectedTermId && availableTerms.length === 0"
        dense
        rounded
        class="bg-warning text-white q-ma-md"
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
.parent-page {
  background: #f5f5f5;
  min-height: 100vh;
}

.parent-header {
  background: white;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  position: sticky;
  top: 0;
  z-index: 100;
}

.parent-content {
  max-width: 1200px;
  margin: 0 auto;
}

.info-card {
  border-radius: 16px;
  border: none;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  background: white;
}

.stat-item {
  padding: 12px;
  background: rgba(0, 0, 0, 0.02);
  border-radius: 12px;
  text-align: center;
}

.result-item {
  border-radius: 12px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  background: rgba(0, 0, 0, 0.02);
  transition: all 0.2s ease;
}

// Mobile optimizations
@media (max-width: 600px) {
  .parent-header {
    padding: 12px 16px;
  }

  .parent-content {
    padding: 12px;
  }

  .stat-item {
    padding: 10px;
  }
}
</style>

