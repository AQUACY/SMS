<template>
  <q-page class="q-pa-lg">
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">Report Cards</div>
        <div class="text-body2 text-grey-7">View and generate report cards</div>
      </div>
      <q-btn
        v-if="authStore.isTeacher || authStore.isSchoolAdmin || authStore.isSuperAdmin"
        color="primary"
        label="Generate Report Card"
        icon="description"
        unelevated
        to="/app/report-cards/generate"
      />
    </div>

    <q-card class="widget-card">
      <q-card-section>
        <div class="row q-mb-md">
          <div class="col-12 col-md-4">
            <q-select
              v-model="filterStudent"
              :options="students"
              option-label="full_name"
              option-value="id"
              emit-value
              map-options
              outlined
              dense
              clearable
              label="Filter by Student"
              @update:model-value="onFilter"
              :loading="loadingStudents"
            >
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section>
                    <q-item-label>{{ scope.opt.full_name }}</q-item-label>
                    <q-item-label caption>{{ scope.opt.student_number || '' }}</q-item-label>
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
          </div>
          <div class="col-12 col-md-4">
            <q-select
              v-model="filterTerm"
              :options="terms"
              option-label="name"
              option-value="id"
              emit-value
              map-options
              outlined
              dense
              clearable
              label="Filter by Term"
              @update:model-value="onFilter"
              :loading="loadingTerms"
            >
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section>
                    <q-item-label>{{ scope.opt.name }}</q-item-label>
                    <q-item-label caption>
                      {{ scope.opt.academic_year?.name || '' }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
          </div>
          <div class="col-12 col-md-4">
            <q-input
              v-model="searchQuery"
              outlined
              dense
              placeholder="Search by student name..."
              @update:model-value="onSearch"
              clearable
            >
              <template v-slot:prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>
        </div>

        <q-table
          :rows="reportCards"
          :columns="columns"
          row-key="id"
          :loading="loading"
          flat
        >
          <template v-slot:body-cell-student="props">
            <q-td :props="props">
              <div class="text-body2">{{ props.row.student?.full_name || 'N/A' }}</div>
              <div class="text-caption text-grey-7">{{ props.row.student?.student_number || '' }}</div>
            </q-td>
          </template>

          <template v-slot:body-cell-term="props">
            <q-td :props="props">
              <div class="text-body2">{{ props.row.term?.name || 'N/A' }}</div>
              <div class="text-caption text-grey-7">{{ props.row.term?.academic_year?.name || '' }}</div>
            </q-td>
          </template>

          <template v-slot:body-cell-statistics="props">
            <q-td :props="props">
              <div class="text-body2">
                <strong>Average:</strong> {{ props.row.statistics?.average_percentage || 0 }}%
              </div>
              <div class="text-caption text-grey-7">
                {{ props.row.statistics?.total_subjects || 0 }} subjects | 
                {{ props.row.statistics?.obtained_marks || 0 }}/{{ props.row.statistics?.total_marks || 0 }} marks
              </div>
            </q-td>
          </template>

          <template v-slot:body-cell-grade="props">
            <q-td :props="props">
              <q-badge
                v-if="props.row.statistics?.grade"
                :color="getGradeColor(props.row.statistics.grade)"
                :label="props.row.statistics.grade"
              />
              <span v-else class="text-grey-7">-</span>
            </q-td>
          </template>

          <template v-slot:body-cell-actions="props">
            <q-td :props="props">
              <q-btn
                flat
                dense
                round
                icon="visibility"
                color="primary"
                @click="viewReportCard(props.row)"
              >
                <q-tooltip>View Report Card</q-tooltip>
              </q-btn>
            </q-td>
          </template>
        </q-table>

        <div v-if="!loading && reportCards.length === 0" class="text-center q-pa-lg">
          <div class="text-body1 text-grey-7">No report cards found</div>
          <div class="text-caption text-grey-6 q-mt-sm">
            Report cards will appear here once results are entered for students.
          </div>
        </div>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'src/stores/auth';
import api from 'src/services/api';

const router = useRouter();
const $q = useQuasar();
const authStore = useAuthStore();

const loading = ref(false);
const loadingStudents = ref(false);
const loadingTerms = ref(false);
const reportCards = ref([]);
const students = ref([]);
const terms = ref([]);
const filterStudent = ref(null);
const filterTerm = ref(null);
const searchQuery = ref('');

const columns = [
  { name: 'student', label: 'Student', field: 'student', align: 'left', sortable: true },
  { name: 'term', label: 'Term', field: 'term', align: 'left', sortable: true },
  { name: 'statistics', label: 'Performance', field: 'statistics', align: 'left' },
  { name: 'grade', label: 'Grade', field: 'grade', align: 'center' },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'center' },
];

onMounted(() => {
  fetchReportCards();
  fetchStudents();
  fetchTerms();
});

async function fetchReportCards() {
  loading.value = true;
  try {
    const params = {};
    if (filterStudent.value) {
      params.student_id = filterStudent.value;
    }
    if (filterTerm.value) {
      params.term_id = filterTerm.value;
    }

    const response = await api.get('/report-cards', { params });
    if (response.data.success) {
      let cards = response.data.data || [];
      
      // Apply search filter
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        cards = cards.filter(card => 
          card.student?.full_name?.toLowerCase().includes(query) ||
          card.student?.student_number?.toLowerCase().includes(query)
        );
      }

      reportCards.value = cards;
    }
  } catch (error) {
    console.error('Failed to fetch report cards:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch report cards',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
}

async function fetchStudents() {
  loadingStudents.value = true;
  try {
    const response = await api.get('/students', { params: { per_page: 1000 } });
    if (response.data.success) {
      students.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch students:', error);
  } finally {
    loadingStudents.value = false;
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

function onFilter() {
  fetchReportCards();
}

function onSearch() {
  fetchReportCards();
}

function viewReportCard(card) {
  router.push(`/app/report-cards/${card.student_id}/${card.term_id}`);
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
