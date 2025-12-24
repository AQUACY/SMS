<template>
  <q-page class="q-pa-lg">
    <div class="row items-center q-mb-lg">
      <q-btn
        flat
        icon="arrow_back"
        label="Back"
        @click="router.push('/app/students')"
        class="q-mr-md"
      />
      <div>
        <div class="text-h5 text-weight-bold">Student Details</div>
        <div class="text-body2 text-grey-7">View and manage student information</div>
      </div>
      <q-space />
      <q-btn
        v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
        color="primary"
        label="Edit Student"
        icon="edit"
        unelevated
        @click="router.push(`/app/students/${studentId}/edit`)"
      />
    </div>

    <!-- Skeleton Loading -->
    <div v-if="loading" class="row q-col-gutter-md">
      <div class="col-12 col-md-8">
        <q-card class="widget-card q-mb-md">
          <q-card-section>
            <q-skeleton type="rect" height="100px" class="q-mb-md" />
            <q-skeleton type="text" width="60%" />
            <q-skeleton type="text" width="40%" />
            <q-skeleton type="text" width="50%" class="q-mt-sm" />
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-4">
        <q-card class="widget-card">
          <q-card-section>
            <q-skeleton type="text" width="40%" />
            <q-skeleton type="rect" height="60px" class="q-mt-md" />
            <q-skeleton type="rect" height="60px" class="q-mt-sm" />
          </q-card-section>
        </q-card>
      </div>
    </div>

    <div v-else-if="student" class="row q-col-gutter-md">
      <!-- Left Column - Student Information -->
      <div class="col-12 col-md-8">
        <!-- Basic Information Card -->
        <q-card class="widget-card q-mb-md">
          <q-card-section>
            <div class="row items-center q-mb-md">
              <q-avatar size="80px" class="bg-primary q-mr-md">
                <q-icon name="person" size="48px" color="white" />
              </q-avatar>
              <div>
                <div class="text-h5 text-weight-bold q-mb-xs">{{ getStudentFullName(student) }}</div>
                <div class="text-body2 text-grey-7 q-mb-xs">Student ID: {{ student.student_number }}</div>
                <q-badge
                  :color="student.is_active ? 'positive' : 'negative'"
                  :label="student.is_active ? 'Active' : 'Inactive'"
                />
              </div>
            </div>

            <q-separator class="q-my-md" />

            <!-- Subscription Banner for Parents -->
            <q-banner
              v-if="authStore.isParent && !student.has_active_subscription"
              class="bg-warning text-white q-mb-md"
              rounded
            >
              <template v-slot:avatar>
                <q-icon name="lock" />
              </template>
              <div class="text-weight-bold">Subscription Required</div>
              <div class="text-body2">
                Subscribe to a term to view full student information, results, and report cards.
              </div>
              <template v-slot:action>
                <q-btn
                  flat
                  label="Subscribe Now"
                  color="white"
                  @click="goToSubscribe"
                />
              </template>
            </q-banner>

            <div class="row q-col-gutter-md">
              <!-- Always visible: Name and Class are shown above -->
              
              <!-- Blurred content for parents without subscription -->
              <template v-if="authStore.isParent && !student.has_active_subscription">
                <div class="col-12 col-sm-6">
                  <div class="text-caption text-grey-7 q-mb-xs">Date of Birth</div>
                  <div class="text-body1 blur-text">{{ formatDateOfBirth(student.date_of_birth) }}</div>
                </div>
                <div class="col-12 col-sm-6">
                  <div class="text-caption text-grey-7 q-mb-xs">Gender</div>
                  <div class="text-body1 blur-text">{{ student.gender ? student.gender.charAt(0).toUpperCase() + student.gender.slice(1) : 'Not provided' }}</div>
                </div>
                <div class="col-12 col-sm-6">
                  <div class="text-caption text-grey-7 q-mb-xs">Email</div>
                  <div class="text-body1 blur-text">{{ student.email || 'Not provided' }}</div>
                </div>
                <div class="col-12 col-sm-6">
                  <div class="text-caption text-grey-7 q-mb-xs">Phone</div>
                  <div class="text-body1 blur-text">{{ student.phone || 'Not provided' }}</div>
                </div>
                <div class="col-12">
                  <div class="text-caption text-grey-7 q-mb-xs">Address</div>
                  <div class="text-body1 blur-text">{{ student.address || 'Not provided' }}</div>
                </div>
              </template>

              <!-- Clear content for admins/teachers or parents with subscription -->
              <template v-else>
                <div class="col-12 col-sm-6">
                  <div class="text-caption text-grey-7 q-mb-xs">Date of Birth</div>
                  <div class="text-body1">{{ formatDateOfBirth(student.date_of_birth) }}</div>
                </div>
                <div class="col-12 col-sm-6">
                  <div class="text-caption text-grey-7 q-mb-xs">Gender</div>
                  <div class="text-body1">{{ student.gender ? student.gender.charAt(0).toUpperCase() + student.gender.slice(1) : 'Not provided' }}</div>
                </div>
                <div class="col-12 col-sm-6">
                  <div class="text-caption text-grey-7 q-mb-xs">Email</div>
                  <div class="text-body1">{{ student.email || 'Not provided' }}</div>
                </div>
                <div class="col-12 col-sm-6">
                  <div class="text-caption text-grey-7 q-mb-xs">Phone</div>
                  <div class="text-body1">{{ student.phone || 'Not provided' }}</div>
                </div>
                <div class="col-12">
                  <div class="text-caption text-grey-7 q-mb-xs">Address</div>
                  <div class="text-body1">{{ student.address || 'Not provided' }}</div>
                </div>
              </template>
            </div>
          </q-card-section>
        </q-card>

        <!-- Class Information Card -->
        <q-card class="widget-card q-mb-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">Current Class</div>
            <div v-if="student.active_enrollment?.class" class="row items-center">
              <q-icon name="class" size="24px" color="primary" class="q-mr-sm" />
              <div>
                <div class="text-body1 text-weight-medium">{{ student.active_enrollment.class.name }}</div>
                <div class="text-caption text-grey-7">
                  Academic Year: {{ student.active_enrollment.academic_year?.name || 'N/A' }}
                </div>
              </div>
            </div>
            <div v-else class="text-body2 text-grey-7">Not enrolled in any class</div>
          </q-card-section>
        </q-card>

        <!-- Academic History Card -->
        <q-card class="widget-card">
          <q-card-section>
            <div class="text-h6 q-mb-md">Academic History</div>
            <q-list v-if="enrollments.length > 0" separator>
              <q-item v-for="enrollment in enrollments" :key="enrollment.id">
                <q-item-section avatar>
                  <q-icon name="school" color="primary" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ enrollment.class?.name || 'Unknown Class' }}</q-item-label>
                  <q-item-label caption>
                    {{ enrollment.academic_year?.name || 'Unknown Year' }} - 
                    {{ enrollment.status === 'active' ? 'Active' : 'Inactive' }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-badge :color="enrollment.status === 'active' ? 'positive' : 'grey'">
                    {{ enrollment.status }}
                  </q-badge>
                </q-item-section>
              </q-item>
            </q-list>
            <div v-else class="text-body2 text-grey-7">No enrollment history</div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Right Column - Guardians & Actions -->
      <div class="col-12 col-md-4">
        <!-- Guardians Card -->
        <q-card class="widget-card q-mb-md">
          <q-card-section>
            <div class="row items-center justify-between q-mb-md">
              <div class="text-h6">Guardians</div>
              <q-btn
                v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
                flat
                dense
                round
                icon="add"
                color="primary"
                @click="showLinkGuardianDialog = true"
              />
            </div>

            <div v-if="guardians.length > 0">
              <q-list separator>
                <q-item v-for="guardian in guardians" :key="guardian.id">
                  <q-item-section avatar>
                    <q-avatar size="40px" class="bg-primary">
                      <q-icon name="person" color="white" />
                    </q-avatar>
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>
                      {{ getGuardianName(guardian) }}
                    </q-item-label>
                    <q-item-label caption>
                      {{ formatRelationship(guardian.pivot?.relationship) }}
                      <span v-if="guardian.pivot?.is_primary" class="q-ml-xs">
                        <q-badge color="primary" label="Primary" />
                      </span>
                    </q-item-label>
                    <q-item-label caption v-if="guardian.user?.email">
                      <q-icon name="email" size="14px" class="q-mr-xs" />
                      {{ guardian.user.email }}
                    </q-item-label>
                    <q-item-label caption v-if="guardian.phone">
                      <q-icon name="phone" size="14px" class="q-mr-xs" />
                      {{ guardian.phone }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin">
                    <q-btn
                      flat
                      dense
                      round
                      icon="close"
                      color="negative"
                      size="sm"
                      @click="unlinkGuardian(guardian.id)"
                    />
                  </q-item-section>
                </q-item>
              </q-list>
            </div>
            <div v-else class="text-body2 text-grey-7 text-center q-py-md">
              No guardians linked
              <q-btn
                v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
                flat
                dense
                label="Link Guardian"
                color="primary"
                @click="showLinkGuardianDialog = true"
                class="q-mt-sm full-width"
              />
            </div>
          </q-card-section>
        </q-card>

        <!-- Quick Actions Card -->
        <q-card class="widget-card">
          <q-card-section>
            <div class="text-h6 q-mb-md">Quick Actions</div>
            <q-list>
              <q-item clickable @click="router.push(`/app/students/${studentId}/attendance`)">
                <q-item-section avatar>
                  <q-icon name="checklist" color="primary" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>View Attendance</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-icon name="chevron_right" />
                </q-item-section>
              </q-item>
              <q-item clickable @click="router.push(`/app/students/${studentId}/results`)">
                <q-item-section avatar>
                  <q-icon name="assessment" color="primary" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>View Results</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-icon name="chevron_right" />
                </q-item-section>
              </q-item>
              <q-item clickable @click="router.push(`/app/report-cards?student=${studentId}`)">
                <q-item-section avatar>
                  <q-icon name="description" color="primary" />
                </q-item-section>
                <q-item-section>
                  <q-item-label>View Report Cards</q-item-label>
                </q-item-section>
                <q-item-section side>
                  <q-icon name="chevron_right" />
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Link Guardian Dialog -->
    <q-dialog v-model="showLinkGuardianDialog" persistent>
      <q-card style="min-width: 500px">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Link Guardian</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <div class="text-body2 text-grey-7 q-mb-md">
            Search for an existing guardian or create a new one
          </div>

          <q-select
            v-model="selectedGuardian"
            :options="guardianOptions"
            label="Select Guardian"
            outlined
            use-input
            input-debounce="300"
            @filter="filterGuardians"
            @new-value="createGuardian"
            option-label="label"
            option-value="value"
            emit-value
            map-options
            :loading="loadingGuardians"
            hint="Type to search or create new guardian"
          >
            <template v-slot:no-option>
              <q-item>
                <q-item-section class="text-grey">
                  No guardians found. Type to create a new one.
                </q-item-section>
              </q-item>
            </template>
          </q-select>

          <div v-if="selectedGuardian" class="q-mt-md">
            <q-select
              v-model="guardianRelationship"
              :options="relationshipOptions"
              label="Relationship"
              outlined
              emit-value
              map-options
              hint="Select the relationship type"
            />
            <q-checkbox
              v-model="isPrimaryGuardian"
              label="Set as Primary Guardian"
              class="q-mt-sm"
            />
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" color="grey" v-close-popup />
          <q-btn
            color="primary"
            label="Link Guardian"
            @click="linkGuardian"
            :loading="linkingGuardian"
            :disable="!selectedGuardian"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'src/stores/auth';
import api from 'src/services/api';

const route = useRoute();
const router = useRouter();
const $q = useQuasar();
const authStore = useAuthStore();

const studentId = computed(() => route.params.id);
const loading = ref(false);
const student = ref(null);
const enrollments = ref([]);
const guardians = ref([]);
const showLinkGuardianDialog = ref(false);
const selectedGuardian = ref(null);
const guardianRelationship = ref('guardian');
const isPrimaryGuardian = ref(false);
const linkingGuardian = ref(false);
const loadingGuardians = ref(false);
const guardianOptions = ref([]);
const allGuardians = ref([]);

const relationshipOptions = [
  { label: 'Father', value: 'father' },
  { label: 'Mother', value: 'mother' },
  { label: 'Guardian', value: 'guardian' },
  { label: 'Other', value: 'other' },
];

onMounted(() => {
  fetchStudentDetails();
  fetchGuardians();
});

async function fetchStudentDetails() {
  loading.value = true;
  try {
    const response = await api.get(`/students/${studentId.value}`);
    if (response.data.success) {
      student.value = response.data.data;
      guardians.value = student.value.parents || [];
      
      // Fetch enrollment history
      const enrollmentsResponse = await api.get(`/students/${studentId.value}/enrollments`);
      if (enrollmentsResponse.data.success) {
        enrollments.value = enrollmentsResponse.data.data || [];
      }
    }
  } catch (error) {
    console.error('Failed to fetch student details:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch student details',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
}

async function fetchGuardians() {
  loadingGuardians.value = true;
  try {
    const response = await api.get('/guardians');
    if (response.data.success) {
      allGuardians.value = response.data.data || [];
      updateGuardianOptions();
    }
  } catch (error) {
    console.error('Failed to fetch guardians:', error);
  } finally {
    loadingGuardians.value = false;
  }
}

function filterGuardians(val, update) {
  if (val === '') {
    update(() => {
      updateGuardianOptions();
    });
    return;
  }

  update(() => {
    const needle = val.toLowerCase();
    guardianOptions.value = allGuardians.value
      .filter(guardian => {
        const name = guardian.user?.full_name || '';
        const email = guardian.user?.email || '';
        return name.toLowerCase().indexOf(needle) > -1 || email.toLowerCase().indexOf(needle) > -1;
      })
      .map(guardian => ({
        label: `${guardian.user?.full_name || 'Unknown'} (${guardian.user?.email || 'No email'})`,
        value: guardian.id,
      }));
  });
}

function updateGuardianOptions() {
  guardianOptions.value = allGuardians.value.map(guardian => ({
    label: `${guardian.user?.full_name || 'Unknown'} (${guardian.user?.email || 'No email'})`,
    value: guardian.id,
  }));
}

async function createGuardian(val, done) {
  // Create new guardian from email
  if (!val || !val.includes('@')) {
    $q.notify({
      type: 'warning',
      message: 'Please enter a valid email address',
      position: 'top',
    });
    done(false);
    return;
  }

  try {
    const response = await api.post('/guardians', {
      email: val,
      first_name: 'Guardian',
      last_name: 'User',
    });

    if (response.data.success) {
      const newGuardian = response.data.data;
      allGuardians.value.push(newGuardian);
      updateGuardianOptions();
      selectedGuardian.value = newGuardian.id;
      done(newGuardian.id, 'add-unique');
      $q.notify({
        type: 'positive',
        message: 'Guardian created successfully',
        position: 'top',
      });
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to create guardian',
      position: 'top',
    });
    done(false);
  }
}

async function linkGuardian() {
  if (!selectedGuardian.value) {
    $q.notify({
      type: 'warning',
      message: 'Please select a guardian',
      position: 'top',
    });
    return;
  }

  linkingGuardian.value = true;
  try {
    const response = await api.post(`/students/${studentId.value}/guardians`, {
      guardian_id: selectedGuardian.value,
      relationship: guardianRelationship.value,
      is_primary: isPrimaryGuardian.value,
    });

    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Guardian linked successfully',
        position: 'top',
      });
      showLinkGuardianDialog.value = false;
      selectedGuardian.value = null;
      guardianRelationship.value = 'guardian';
      isPrimaryGuardian.value = false;
      fetchStudentDetails(); // Refresh student data
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to link guardian',
      position: 'top',
    });
  } finally {
    linkingGuardian.value = false;
  }
}

async function unlinkGuardian(guardianId) {
  $q.dialog({
    title: 'Confirm Unlink',
    message: 'Are you sure you want to unlink this guardian from the student?',
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      const response = await api.delete(`/students/${studentId.value}/guardians/${guardianId}`);

      if (response.data.success) {
        $q.notify({
          type: 'positive',
          message: 'Guardian unlinked successfully',
          position: 'top',
        });
        fetchStudentDetails(); // Refresh student data
      }
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to unlink guardian',
        position: 'top',
      });
    }
  });
}

function formatDateOfBirth(date) {
  if (!date) return 'Not provided';
  
  try {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    if (isNaN(dateObj.getTime())) return 'Not provided';
    
    const day = dateObj.getDate();
    const monthNames = [
      'January', 'February', 'March', 'April', 'May', 'June',
      'July', 'August', 'September', 'October', 'November', 'December'
    ];
    const month = monthNames[dateObj.getMonth()];
    const year = dateObj.getFullYear();
    
    // Get ordinal suffix (st, nd, rd, th)
    const getOrdinalSuffix = (n) => {
      const s = ['th', 'st', 'nd', 'rd'];
      const v = n % 100;
      return s[(v - 20) % 10] || s[v] || s[0];
    };
    
    return `${day}${getOrdinalSuffix(day)} ${month}, ${year}`;
  } catch (error) {
    return 'Not provided';
  }
}

function getGuardianName(guardian) {
  if (!guardian) return 'Unknown';
  
  if (guardian.user) {
    const firstName = guardian.user.first_name || '';
    const lastName = guardian.user.last_name || '';
    const fullName = `${firstName} ${lastName}`.trim();
    return fullName || guardian.user.email || 'Unknown';
  }
  
  return 'Unknown';
}

function formatRelationship(relationship) {
  if (!relationship) return 'Guardian';
  
  const relationshipMap = {
    'father': 'Father',
    'mother': 'Mother',
    'guardian': 'Guardian',
    'other': 'Other',
    'parent': 'Parent'
  };
  
  return relationshipMap[relationship.toLowerCase()] || relationship.charAt(0).toUpperCase() + relationship.slice(1);
}

function getStudentFullName(student) {
  if (!student) return 'Unknown';
  
  // Use full_name if available (from backend accessor), otherwise construct it
  if (student.full_name) {
    return student.full_name;
  }
  
  // Construct from separate fields
  const firstName = student.first_name || '';
  const middleName = student.middle_name || '';
  const lastName = student.last_name || '';
  
  // Combine names, filtering out empty strings
  const nameParts = [firstName, middleName, lastName].filter(part => part && part.trim() !== '');
  return nameParts.join(' ') || 'Unknown';
}

function goToSubscribe() {
  router.push(`/app/parent/payments`);
  $q.notify({
    type: 'info',
    message: 'Please select a term to subscribe',
    position: 'top',
  });
}
</script>

<style lang="scss" scoped>
.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}

.blur-text {
  filter: blur(4px);
  user-select: none;
  pointer-events: none;
  color: transparent;
  text-shadow: 0 0 8px rgba(0, 0, 0, 0.5);
}
</style>
