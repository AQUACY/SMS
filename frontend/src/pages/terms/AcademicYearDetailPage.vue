<template>
  <q-page class="q-pa-lg">
    <div class="row items-center q-mb-lg">
      <q-btn
        flat
        icon="arrow_back"
        label="Back"
        @click="router.push('/app/academic-years')"
        class="q-mr-md"
      />
      <div>
        <div class="text-h5 text-weight-bold">Academic Year Details</div>
        <div class="text-body2 text-grey-7">View and manage academic year information</div>
      </div>
      <q-space />
      <q-btn
        v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
        color="primary"
        label="Edit Academic Year"
        icon="edit"
        unelevated
        @click="router.push(`/app/academic-years/${academicYearId}/edit`)"
        class="q-mr-sm"
      />
      <q-btn
        v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin && !academicYear?.is_active"
        color="positive"
        label="Activate"
        icon="play_arrow"
        unelevated
        @click="activateAcademicYear"
        :loading="actionLoading"
      />
    </div>

    <div v-if="loading" class="row justify-center q-pa-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <div v-else-if="academicYear" class="row q-col-gutter-md">
      <!-- Left Column - Academic Year Information -->
      <div class="col-12 col-md-8">
        <!-- Basic Information Card -->
        <q-card class="widget-card q-mb-md">
          <q-card-section>
            <div class="row items-center q-mb-md">
              <q-avatar size="80px" class="bg-primary q-mr-md">
                <q-icon name="calendar_today" size="48px" color="white" />
              </q-avatar>
              <div>
                <div class="text-h5 text-weight-bold q-mb-xs">{{ academicYear.name }}</div>
                <q-badge
                  :color="academicYear.is_active ? 'positive' : 'grey'"
                  :label="academicYear.is_active ? 'Active' : 'Inactive'"
                />
              </div>
            </div>

            <q-separator class="q-my-md" />

            <div class="row q-col-gutter-md">
              <div class="col-12 col-sm-6">
                <div class="text-caption text-grey-7 q-mb-xs">Start Date</div>
                <div class="text-body1">{{ formatDate(academicYear.start_date) }}</div>
              </div>
              <div class="col-12 col-sm-6">
                <div class="text-caption text-grey-7 q-mb-xs">End Date</div>
                <div class="text-body1">{{ formatDate(academicYear.end_date) }}</div>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Terms Card -->
        <q-card class="widget-card">
          <q-card-section>
            <div class="text-h6 q-mb-md">Terms ({{ terms.length }})</div>
            <q-list v-if="terms.length > 0" separator>
              <q-item
                v-for="term in terms"
                :key="term.id"
                clickable
                @click="router.push(`/app/terms/${term.id}`)"
              >
                <q-item-section avatar>
                  <q-icon name="event" color="primary" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ term.name }}</q-item-label>
                  <q-item-label caption>
                    Term {{ term.term_number }} | {{ formatDate(term.start_date) }} - {{ formatDate(term.end_date) }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-badge :color="getStatusColor(term.status)">
                    {{ formatStatus(term.status) }}
                  </q-badge>
                </q-item-section>
                <q-item-section side>
                  <q-icon name="chevron_right" color="grey-6" />
                </q-item-section>
              </q-item>
            </q-list>
            <div v-else class="text-body2 text-grey-7">No terms for this academic year</div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Right Column - Quick Actions -->
      <div class="col-12 col-md-4">
        <q-card class="widget-card">
          <q-card-section>
            <div class="text-h6 q-mb-md">Quick Actions</div>
            <div class="q-gutter-sm">
              <q-btn
                flat
                color="primary"
                icon="event"
                label="Add Term"
                @click="router.push(`/app/terms/create?academic_year_id=${academicYearId}`)"
                class="full-width"
              />
              <q-btn
                flat
                color="primary"
                icon="list"
                label="View All Terms"
                @click="router.push(`/app/terms?academic_year_id=${academicYearId}`)"
                class="full-width"
              />
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
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

const academicYearId = route.params.id;
const loading = ref(false);
const actionLoading = ref(false);
const academicYear = ref(null);
const terms = ref([]);

const getStatusColor = (status) => {
  const colors = {
    draft: 'grey',
    active: 'positive',
    closing: 'warning',
    closed: 'negative',
    archived: 'dark',
  };
  return colors[status] || 'grey';
};

const formatStatus = (status) => {
  return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown';
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  const d = new Date(date);
  return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
};

const fetchAcademicYear = async () => {
  loading.value = true;
  try {
    const response = await api.get(`/academic-years/${academicYearId}`);
    if (response.data.success && response.data.data) {
      academicYear.value = response.data.data;
      terms.value = academicYear.value.terms || [];
    }
  } catch (error) {
    console.error('Failed to fetch academic year:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to load academic year details. Please try again.',
      position: 'top',
    });
    router.push('/app/academic-years');
  } finally {
    loading.value = false;
  }
};

const activateAcademicYear = async () => {
  actionLoading.value = true;
  try {
    const response = await api.post(`/academic-years/${academicYearId}/activate`);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Academic year activated successfully',
        position: 'top',
      });
      fetchAcademicYear();
    }
  } catch (error) {
    console.error('Failed to activate academic year:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to activate academic year. Please try again.',
      position: 'top',
    });
  } finally {
    actionLoading.value = false;
  }
};

onMounted(() => {
  fetchAcademicYear();
});
</script>

<style lang="scss" scoped>
.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}
</style>

