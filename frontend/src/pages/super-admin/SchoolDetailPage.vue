<template>
  <q-page class="q-pa-lg">
    <div class="row items-center q-mb-lg">
      <q-btn
        flat
        icon="arrow_back"
        label="Back"
        @click="router.back()"
        class="q-mr-md"
      />
      <div>
        <div class="text-h5 text-weight-bold">{{ school?.name || 'School Details' }}</div>
        <div class="text-body2 text-grey-7">View and manage school information</div>
      </div>
    </div>

    <div v-if="loading" class="text-center q-pa-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <div v-else-if="school" class="row q-gutter-md">
      <!-- School Information -->
      <div class="col-12 col-md-8">
        <q-card class="widget-card">
          <q-card-section>
            <div class="text-h6 q-mb-md">School Information</div>
            <q-list>
              <q-item>
                <q-item-section>
                  <q-item-label caption>School Name</q-item-label>
                  <q-item-label>{{ school.name }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item v-if="school.code">
                <q-item-section>
                  <q-item-label caption>Code</q-item-label>
                  <q-item-label>{{ school.code }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item v-if="school.domain">
                <q-item-section>
                  <q-item-label caption>Domain</q-item-label>
                  <q-item-label>{{ school.domain }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item v-if="school.email">
                <q-item-section>
                  <q-item-label caption>Email</q-item-label>
                  <q-item-label>{{ school.email }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item v-if="school.phone">
                <q-item-section>
                  <q-item-label caption>Phone</q-item-label>
                  <q-item-label>{{ school.phone }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item v-if="school.address">
                <q-item-section>
                  <q-item-label caption>Address</q-item-label>
                  <q-item-label>{{ school.address }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Status</q-item-label>
                  <q-item-label>
                    <q-badge
                      :color="school.is_active ? 'positive' : 'negative'"
                      :label="school.is_active ? 'Active' : 'Inactive'"
                    />
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>
      </div>

      <!-- Statistics -->
      <div class="col-12 col-md-4">
        <q-card class="widget-card">
          <q-card-section>
            <div class="text-h6 q-mb-md">Statistics</div>
            <q-list>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Total Users</q-item-label>
                  <q-item-label class="text-h6">{{ statistics.total_users || 0 }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Total Students</q-item-label>
                  <q-item-label class="text-h6">{{ statistics.total_students || 0 }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Total Teachers</q-item-label>
                  <q-item-label class="text-h6">{{ statistics.total_teachers || 0 }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Total Classes</q-item-label>
                  <q-item-label class="text-h6">{{ statistics.total_classes || 0 }}</q-item-label>
                </q-item-section>
              </q-item>
              <q-item>
                <q-item-section>
                  <q-item-label caption>Academic Years</q-item-label>
                  <q-item-label class="text-h6">
                    {{ statistics.active_academic_years || 0 }} / {{ statistics.total_academic_years || 0 }}
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>

        <q-card class="widget-card q-mt-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">Actions</div>
            <q-btn
              color="secondary"
              label="Sign In As Admin"
              icon="login"
              unelevated
              class="full-width q-mb-sm"
              @click="signInAsSchool"
            />
            <q-btn
              color="primary"
              label="Edit School"
              icon="edit"
              unelevated
              class="full-width q-mb-sm"
              @click="editSchool"
            />
            <q-btn
              :color="school.is_active ? 'negative' : 'positive'"
              :label="school.is_active ? 'Deactivate' : 'Activate'"
              :icon="school.is_active ? 'block' : 'check_circle'"
              unelevated
              class="full-width"
              @click="toggleStatus"
            />
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

const loading = ref(false);
const school = ref(null);
const statistics = ref({});

onMounted(() => {
  fetchSchool();
  fetchStatistics();
});

async function fetchSchool() {
  loading.value = true;
  try {
    const response = await api.get(`/schools/${route.params.id}`);

    if (response.data.success) {
      school.value = response.data.data;
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch school details',
      position: 'top',
    });
    router.back();
  } finally {
    loading.value = false;
  }
}

async function fetchStatistics() {
  try {
    const response = await api.get(`/schools/${route.params.id}/statistics`);

    if (response.data.success) {
      statistics.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to fetch statistics:', error);
  }
}

function editSchool() {
  router.push(`/app/super-admin/schools/${route.params.id}/edit`);
}

async function signInAsSchool() {
  $q.dialog({
    title: 'Sign In As School Admin',
    message: `You are about to sign in as the admin of "${school.value.name}". You will be able to manage this school's data.`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      const response = await api.post(`/auth/sign-in-as-school/${school.value.id}`);

      if (response.data.success) {
        const { user, token, roles, impersonating, original_user_id } = response.data.data;

        authStore.setAuth(user, token, roles, impersonating, original_user_id);

        $q.notify({
          type: 'positive',
          message: `Signed in as admin of ${school.value.name}`,
          position: 'top',
        });

        router.push('/app/dashboard');
      }
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to sign in as school admin',
        position: 'top',
      });
    }
  });
}

async function toggleStatus() {
  try {
    const response = await api.post(`/schools/${school.value.id}/toggle-status`);

    if (response.data.success) {
      school.value.is_active = !school.value.is_active;
      $q.notify({
        type: 'positive',
        message: `School ${school.value.is_active ? 'activated' : 'deactivated'} successfully`,
        position: 'top',
      });
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to toggle school status',
      position: 'top',
    });
  }
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

