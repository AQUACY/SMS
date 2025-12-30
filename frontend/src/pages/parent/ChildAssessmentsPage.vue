<template>
  <q-page class="child-assessments-page">
    <MobilePageHeader
      title="Assessments"
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
          @update:model-value="fetchAssessments"
          class="q-mb-sm"
        />
        <div v-if="selectedTerm" class="text-caption text-grey-7">
          {{ selectedTerm.academic_year?.name }} - {{ selectedTerm.name }}
        </div>
      </MobileCard>

      <!-- Skeleton Loading -->
      <div v-if="loading" class="detail-loading">
        <MobileCard v-for="i in 2" :key="i" variant="default" padding="md" class="q-mb-md">
          <q-skeleton type="rect" height="150px" class="q-mb-md" />
          <q-skeleton type="text" width="60%" />
          <q-skeleton type="text" width="40%" />
        </MobileCard>
      </div>

      <!-- Assessments List -->
      <div v-else-if="assessments.length > 0" class="assessments-list">
        <MobileCard
          v-for="assessment in assessments"
          :key="assessment.id"
          variant="default"
          padding="md"
          class="assessment-card q-mb-md"
        >
            <div class="row items-start q-mb-md">
              <q-icon :name="getTypeIcon(assessment.type)" size="32px" :color="getTypeColor(assessment.type)" class="q-mr-md" />
              <div class="col">
                <div class="text-h6 text-weight-medium q-mb-xs">{{ assessment.name }}</div>
                <div class="text-caption text-grey-7">
                  <q-badge :color="getTypeColor(assessment.type)" :label="assessment.type?.toUpperCase() || 'Assessment'" size="sm" class="q-mr-sm" />
                  <span v-if="assessment.subject">{{ assessment.subject.name }}</span>
                </div>
              </div>
            </div>

            <q-separator class="q-my-md" />

            <div class="row q-gutter-md q-mb-md">
              <div class="col-6 col-sm-3">
                <div class="info-item">
                  <div class="text-caption text-grey-7">Total Marks</div>
                  <div class="text-body1 text-weight-medium">{{ assessment.total_marks }}</div>
                </div>
              </div>
              <div class="col-6 col-sm-3" v-if="assessment.weight">
                <div class="info-item">
                  <div class="text-caption text-grey-7">Weight</div>
                  <div class="text-body1 text-weight-medium">{{ assessment.weight }}%</div>
                </div>
              </div>
              <div class="col-6 col-sm-3" v-if="assessment.date">
                <div class="info-item">
                  <div class="text-caption text-grey-7">Date</div>
                  <div class="text-body1">{{ formatDate(assessment.date) }}</div>
                </div>
              </div>
              <div class="col-6 col-sm-3" v-if="assessment.due_date">
                <div class="info-item">
                  <div class="text-caption text-grey-7">Due Date</div>
                  <div class="text-body1">{{ formatDate(assessment.due_date) }}</div>
                </div>
              </div>
            </div>

            <div v-if="assessment.description" class="q-mb-md">
              <div class="text-caption text-grey-7 q-mb-xs">Description</div>
              <div class="text-body2">{{ assessment.description }}</div>
            </div>

            <!-- Student Result -->
            <div v-if="assessment.student_result" class="result-section q-pa-md">
              <q-separator class="q-mb-md" />
              <div class="text-body1 text-weight-medium q-mb-sm">Your Child's Result</div>
              <div class="row items-center justify-between">
                <div class="col">
                  <div class="text-body2 text-grey-7">Marks Obtained</div>
                  <div class="text-h6 text-weight-bold">
                    {{ assessment.student_result.marks_obtained }} / {{ assessment.total_marks }}
                  </div>
                </div>
                <div class="text-right">
                  <div class="text-body2 text-grey-7 q-mb-xs">Grade</div>
                  <q-badge
                    :color="getGradeColor(assessment.student_result.grade)"
                    :label="assessment.student_result.grade"
                    size="lg"
                  />
                </div>
              </div>
              <div v-if="assessment.student_result.remarks" class="text-caption text-grey-7 q-mt-sm">
                {{ assessment.student_result.remarks }}
              </div>
            </div>

            <div v-else class="result-section q-pa-md">
              <q-separator class="q-mb-md" />
              <div class="text-center text-grey-7">
                <q-icon name="pending" size="24px" class="q-mb-xs" />
                <div class="text-body2">Result not yet available</div>
              </div>
            </div>
        </MobileCard>
      </div>

      <!-- Empty State -->
      <MobileCard v-else-if="!loading && selectedTermId" variant="default" padding="lg">
        <div class="empty-state">
          <q-icon name="quiz" size="64px" color="grey-5" />
          <div class="empty-text">No Assessments Found</div>
          <div class="empty-subtext">
            No assessments available for this term yet.
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
          No subscription found for any term. Please subscribe to view assessments.
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
const assessments = ref([]);
const selectedTermId = ref(null);
const availableTerms = ref([]);
const selectedTerm = ref(null);

onMounted(async () => {
  await fetchChildInfo();
  await fetchAvailableTerms();
  if (availableTerms.value.length > 0) {
    selectedTermId.value = availableTerms.value[0].value;
    await fetchAssessments();
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

async function fetchAssessments() {
  if (!selectedTermId.value) return;
  
  loading.value = true;
  try {
    // Fetch assessments for the term
    const assessmentsResponse = await api.get('/assessments', {
      params: {
        term_id: selectedTermId.value,
        student_id: studentId.value,
      },
    });
    
    if (assessmentsResponse.data.success) {
      let allAssessments = assessmentsResponse.data.data || [];
      
      // If paginated, get all pages
      if (assessmentsResponse.data.meta) {
        const totalPages = assessmentsResponse.data.meta.last_page || 1;
        const promises = [];
        for (let page = 2; page <= totalPages; page++) {
          promises.push(
            api.get('/assessments', {
              params: {
                term_id: selectedTermId.value,
                student_id: studentId.value,
                page: page,
              },
            })
          );
        }
        const responses = await Promise.all(promises);
        responses.forEach(response => {
          if (response.data.success && response.data.data) {
            allAssessments = allAssessments.concat(response.data.data);
          }
        });
      }
      
      // Fetch results for each assessment
      const assessmentsWithResults = await Promise.all(
        allAssessments.map(async (assessment) => {
          try {
            const resultsResponse = await api.get(`/results/student/${studentId.value}`, {
              params: {
                term_id: selectedTermId.value,
              },
            });
            
            if (resultsResponse.data.success) {
              const results = resultsResponse.data.data || [];
              const studentResult = results.find(r => r.assessment_id === assessment.id);
              assessment.student_result = studentResult || null;
            }
          } catch (error) {
            console.error(`Failed to fetch result for assessment ${assessment.id}:`, error);
            assessment.student_result = null;
          }
          
          return assessment;
        })
      );
      
      assessments.value = assessmentsWithResults;
      
      const termOption = availableTerms.value.find(t => t.value === selectedTermId.value);
      selectedTerm.value = termOption?.term || null;
    }
  } catch (error) {
    console.error('Failed to fetch assessments:', error);
    if (error.response?.status === 403) {
      $q.notify({
        type: 'warning',
        message: 'Subscription required to view assessments for this term',
        position: 'top',
      });
    } else {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to fetch assessments',
        position: 'top',
      });
    }
  } finally {
    loading.value = false;
  }
}

function formatDate(date) {
  if (!date) return 'N/A';
  try {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    if (isNaN(dateObj.getTime())) return 'N/A';
    
    return new Date(dateObj).toLocaleDateString('en-GB', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    });
  } catch (error) {
    return 'N/A';
  }
}

function getTypeIcon(type) {
  const icons = {
    'exam': 'quiz',
    'test': 'assignment',
    'quiz': 'help',
    'assignment': 'description',
    'project': 'folder',
    'homework': 'home',
  };
  return icons[type?.toLowerCase()] || 'quiz';
}

function getTypeColor(type) {
  const colors = {
    'exam': 'negative',
    'test': 'warning',
    'quiz': 'info',
    'assignment': 'primary',
    'project': 'purple',
    'homework': 'orange',
  };
  return colors[type?.toLowerCase()] || 'grey';
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
.child-assessments-page {
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

.assessments-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.assessment-card {
  transition: all var(--transition-base);

  &:active {
    transform: scale(0.98);
  }
}

.card-title {
  font-size: var(--font-size-lg);
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: var(--spacing-md);
}

.info-item {
  padding: var(--spacing-xs) 0;
}

.result-section {
  background: var(--bg-secondary);
  border-radius: var(--radius-md);
  padding: var(--spacing-md);
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

