<template>
  <q-page class="school-setup-page">
    <MobilePageHeader
      title="School Setup"
      subtitle="Update your school information"
    />

    <div class="page-content">
      <div v-if="loading" class="loading-state">
        <MobileCard v-for="i in 2" :key="i" variant="default" padding="lg" class="q-mb-md">
          <q-skeleton type="rect" height="60px" class="q-mb-md" />
          <q-skeleton type="text" width="80%" />
        </MobileCard>
      </div>

      <div v-else-if="school" class="form-content">
        <MobileCard variant="default" padding="lg" class="q-mb-md">
          <div class="card-title q-mb-lg">School Information</div>
          
          <q-form @submit="saveSchool" class="school-form">
            <!-- School Name -->
            <q-input
              v-model="schoolForm.name"
              label="School Name *"
              outlined
              dense
              :rules="[val => !!val || 'School name is required']"
              class="q-mb-md"
            >
              <template v-slot:prepend>
                <q-icon name="school" />
              </template>
            </q-input>

            <!-- School Code -->
            <q-input
              v-model="schoolForm.code"
              label="School Code (Auto-generated)"
              outlined
              readonly
              dense
              hint="Unique identifier for your school"
              class="q-mb-md"
            >
              <template v-slot:prepend>
                <q-icon name="tag" />
              </template>
            </q-input>

            <!-- Domain -->
            <q-input
              v-model="schoolForm.domain"
              label="Domain"
              outlined
              dense
              hint="School website domain (optional)"
              class="q-mb-md"
            >
              <template v-slot:prepend>
                <q-icon name="language" />
              </template>
            </q-input>

            <!-- Email -->
            <q-input
              v-model="schoolForm.email"
              label="Email"
              type="email"
              outlined
              dense
              hint="School contact email"
              class="q-mb-md"
            >
              <template v-slot:prepend>
                <q-icon name="email" />
              </template>
            </q-input>

            <!-- Phone -->
            <q-input
              v-model="schoolForm.phone"
              label="Phone"
              outlined
              dense
              hint="School contact phone number"
              class="q-mb-md"
            >
              <template v-slot:prepend>
                <q-icon name="phone" />
              </template>
            </q-input>

            <!-- Address -->
            <q-input
              v-model="schoolForm.address"
              label="Address"
              type="textarea"
              outlined
              dense
              rows="3"
              hint="School physical address"
              class="q-mb-md"
            >
              <template v-slot:prepend>
                <q-icon name="location_on" />
              </template>
            </q-input>

            <!-- Action Buttons -->
            <div class="form-actions">
              <q-btn
                type="submit"
                color="primary"
                label="Save Changes"
                :loading="saving"
                class="full-width q-mb-sm"
                unelevated
              >
                <template v-slot:loading>
                  <q-spinner-hourglass class="on-left" />
                  Saving...
                </template>
              </q-btn>

              <q-btn
                type="button"
                outline
                color="grey-7"
                label="Cancel"
                @click="resetForm"
                class="full-width"
                :disable="saving"
              />
            </div>
          </q-form>
        </MobileCard>

        <!-- Current School Info (Read-only) -->
        <MobileCard variant="default" padding="lg">
          <div class="card-title q-mb-md">Current Information</div>
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
          </q-list>
        </MobileCard>
      </div>

      <div v-else class="error-state">
        <MobileCard variant="default" padding="lg">
          <div class="empty-state">
            <q-icon name="error_outline" size="64px" color="negative" />
            <div class="empty-text">Unable to load school information</div>
            <q-btn
              color="primary"
              label="Retry"
              @click="fetchSchool"
              class="q-mt-md"
            />
          </div>
        </MobileCard>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'src/stores/auth';
import api from 'src/services/api';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';

const $q = useQuasar();
const authStore = useAuthStore();

const loading = ref(true);
const saving = ref(false);
const school = ref(null);

const schoolForm = ref({
  name: '',
  code: '',
  domain: '',
  email: '',
  phone: '',
  address: '',
});

async function fetchSchool() {
  if (!authStore.user?.school?.id) {
    loading.value = false;
    return;
  }

  loading.value = true;
  try {
    const response = await api.get(`/schools/${authStore.user.school.id}`);
    if (response.data.success) {
      school.value = response.data.data;
      // Populate form with current school data
      schoolForm.value = {
        name: school.value.name || '',
        code: school.value.code || '',
        domain: school.value.domain || '',
        email: school.value.email || '',
        phone: school.value.phone || '',
        address: school.value.address || '',
      };
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to load school information',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
}

async function saveSchool() {
  if (!authStore.user?.school?.id) {
    $q.notify({
      type: 'negative',
      message: 'School information not available',
      position: 'top',
    });
    return;
  }

  saving.value = true;
  try {
    const response = await api.put(`/schools/${authStore.user.school.id}`, schoolForm.value);
    
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'School information updated successfully',
        position: 'top',
      });

      // Update school data
      school.value = response.data.data;
      
      // Refresh user data to update school info in auth store
      await authStore.fetchUser();
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to update school information',
      position: 'top',
    });
  } finally {
    saving.value = false;
  }
}

function resetForm() {
  if (school.value) {
    schoolForm.value = {
      name: school.value.name || '',
      code: school.value.code || '',
      domain: school.value.domain || '',
      email: school.value.email || '',
      phone: school.value.phone || '',
      address: school.value.address || '',
    };
  }
}

onMounted(() => {
  fetchSchool();
});
</script>

<style lang="scss" scoped>
.school-setup-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.page-content {
  max-width: 900px;
  margin: 0 auto;
}

.loading-state {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.form-content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.card-title {
  font-size: var(--font-size-xl);
  font-weight: 700;
  color: var(--text-primary);
}

.school-form {
  .q-field {
    margin-bottom: var(--spacing-md);
  }
}

.form-actions {
  margin-top: var(--spacing-lg);
  padding-top: var(--spacing-lg);
  border-top: 1px solid var(--border-light);
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
  font-size: var(--font-size-base);
  color: var(--text-secondary);
  margin-top: var(--spacing-md);
}

.error-state {
  margin-top: var(--spacing-lg);
}
</style>

