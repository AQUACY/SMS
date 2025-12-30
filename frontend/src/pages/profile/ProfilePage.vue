<template>
  <q-page class="profile-page">
    <MobilePageHeader
      title="My Profile"
      subtitle="View and manage your profile information"
    >
      <template v-slot:actions>
        <q-btn
          v-if="!editing"
          color="primary"
          :label="$q.screen.gt.xs ? 'Edit Profile' : ''"
          icon="edit"
          unelevated
          @click="startEditing"
          class="mobile-btn"
        />
        <template v-else>
          <q-btn
            flat
            :label="$q.screen.gt.xs ? 'Cancel' : ''"
            icon="close"
            @click="cancelEditing"
            class="mobile-btn q-mr-xs"
          />
          <q-btn
            color="primary"
            :label="$q.screen.gt.xs ? 'Save' : ''"
            icon="save"
            unelevated
            @click="updateProfile"
            :loading="saving"
            class="mobile-btn"
          />
        </template>
      </template>
    </MobilePageHeader>

    <div v-if="loading" class="loading-container">
      <q-spinner color="primary" size="3em" />
    </div>

    <div v-else-if="profile" class="profile-content">
      <!-- Profile Overview Card -->
      <MobileCard variant="default" padding="lg" class="profile-overview-card">
        <div class="profile-avatar-section">
          <q-avatar size="100px" class="profile-avatar" :color="profile.avatar ? 'transparent' : 'primary'">
            <img v-if="profile.avatar" :src="profile.avatar" alt="Avatar" />
            <q-icon v-else name="person" size="50px" color="white" />
          </q-avatar>
          <div class="profile-name">
            {{ profile.first_name }} {{ profile.last_name }}
          </div>
          <div class="profile-email">{{ profile.email }}</div>
          <div class="profile-roles">
            <q-chip
              v-for="role in profile.roles"
              :key="role.id"
              :label="role.name"
              color="primary"
              text-color="white"
              size="sm"
              class="q-mr-xs q-mb-xs"
            />
          </div>
          <div v-if="profile.school" class="profile-school">
            <q-icon name="school" size="16px" class="q-mr-xs" />
            {{ profile.school.name }}
          </div>
        </div>
      </MobileCard>

      <!-- Role-Specific Information -->
      <MobileCard v-if="profile.teacher" variant="default" padding="md" class="role-info-card">
        <div class="card-title">Teacher Information</div>
        <div class="info-grid">
          <div class="info-item">
            <div class="info-label">Employee Number</div>
            <div class="info-value">{{ profile.teacher.employee_number || 'N/A' }}</div>
          </div>
          <div v-if="profile.teacher.department" class="info-item">
            <div class="info-label">Department</div>
            <div class="info-value">{{ profile.teacher.department }}</div>
          </div>
        </div>
      </MobileCard>

      <MobileCard v-if="profile.parent" variant="default" padding="md" class="role-info-card">
        <div class="card-title">Parent Information</div>
        <div class="info-grid">
          <div v-if="profile.parent.occupation" class="info-item">
            <div class="info-label">Occupation</div>
            <div class="info-value">{{ profile.parent.occupation }}</div>
          </div>
          <div v-if="profile.parent.address" class="info-item">
            <div class="info-label">Address</div>
            <div class="info-value">{{ profile.parent.address }}</div>
          </div>
        </div>
      </MobileCard>

      <!-- Profile Details Card -->
      <MobileCard variant="default" padding="lg" class="profile-details-card">
        <q-tabs v-model="activeTab" class="profile-tabs" active-color="primary" indicator-color="primary">
          <q-tab name="personal" label="Personal" icon="person" />
          <q-tab name="password" label="Password" icon="lock" />
        </q-tabs>

        <q-separator class="q-mt-md" />

        <q-tab-panels v-model="activeTab" animated class="q-mt-md">
              <!-- Personal Information Tab -->
              <q-tab-panel name="personal">
                <q-form @submit="updateProfile" class="q-gutter-md">
                  <div class="row q-col-gutter-md">
                    <div class="col-12 col-md-6">
                      <q-input
                        v-model="form.first_name"
                        label="First Name *"
                        outlined
                        :rules="[(val) => !!val || 'First name is required']"
                        :readonly="!editing"
                        :disable="saving"
                      />
                    </div>
                    <div class="col-12 col-md-6">
                      <q-input
                        v-model="form.last_name"
                        label="Last Name *"
                        outlined
                        :rules="[(val) => !!val || 'Last name is required']"
                        :readonly="!editing"
                        :disable="saving"
                      />
                    </div>
                    <div class="col-12">
                      <q-input
                        v-model="form.email"
                        label="Email"
                        outlined
                        type="email"
                        readonly
                        hint="Email cannot be changed"
                      />
                    </div>
                    <div class="col-12">
                      <q-input
                        v-model="form.phone"
                        label="Phone"
                        outlined
                        :readonly="!editing"
                        :disable="saving"
                        hint="Optional phone number"
                      />
                    </div>
                    <div class="col-12">
                      <div class="text-caption text-grey-7 q-mb-sm">Avatar URL</div>
                      <q-input
                        v-model="form.avatar"
                        label="Avatar URL"
                        outlined
                        :readonly="!editing"
                        :disable="saving"
                        hint="Enter a URL to your profile picture"
                      >
                        <template v-slot:append>
                          <q-btn
                            v-if="form.avatar && editing"
                            flat
                            dense
                            round
                            icon="visibility"
                            @click="previewAvatar = true"
                          >
                            <q-tooltip>Preview Avatar</q-tooltip>
                          </q-btn>
                        </template>
                      </q-input>
                    </div>
                  </div>
                </q-form>
              </q-tab-panel>

              <!-- Change Password Tab -->
              <q-tab-panel name="password">
                <q-form @submit="changePassword" class="q-gutter-md">
                  <div class="row q-col-gutter-md">
                    <div class="col-12">
                      <q-input
                        v-model="passwordForm.current_password"
                        label="Current Password *"
                        outlined
                        type="password"
                        :rules="[(val) => !!val || 'Current password is required']"
                        :disable="changingPassword"
                      />
                    </div>
                    <div class="col-12">
                      <q-input
                        v-model="passwordForm.password"
                        label="New Password *"
                        outlined
                        type="password"
                        :rules="[
                          (val) => !!val || 'New password is required',
                          (val) => val.length >= 8 || 'Password must be at least 8 characters',
                        ]"
                        :disable="changingPassword"
                      />
                    </div>
                    <div class="col-12">
                      <q-input
                        v-model="passwordForm.password_confirmation"
                        label="Confirm New Password *"
                        outlined
                        type="password"
                        :rules="[
                          (val) => !!val || 'Please confirm your password',
                          (val) => val === passwordForm.password || 'Passwords do not match',
                        ]"
                        :disable="changingPassword"
                      />
                    </div>
                    <div class="col-12">
                      <q-btn
                        type="submit"
                        color="primary"
                        label="Change Password"
                        icon="lock"
                        unelevated
                        :loading="changingPassword"
                      />
                    </div>
                  </div>
                </q-form>
              </q-tab-panel>
            </q-tab-panels>
      </MobileCard>
    </div>

    <!-- Avatar Preview Dialog -->
    <q-dialog v-model="previewAvatar">
      <q-card style="min-width: 300px">
        <q-card-section>
          <div class="text-h6">Avatar Preview</div>
        </q-card-section>
        <q-card-section class="text-center">
          <q-avatar size="200px" :color="form.avatar ? 'transparent' : 'primary'">
            <img v-if="form.avatar" :src="form.avatar" alt="Avatar Preview" />
            <q-icon v-else name="person" size="100px" color="white" />
          </q-avatar>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Close" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'src/stores/auth';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import api from 'src/services/api';

const $q = useQuasar();
const authStore = useAuthStore();

const loading = ref(false);
const saving = ref(false);
const changingPassword = ref(false);
const editing = ref(false);
const activeTab = ref('personal');
const previewAvatar = ref(false);
const profile = ref(null);

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  avatar: '',
});

const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
});

onMounted(() => {
  fetchProfile();
});

async function fetchProfile() {
  loading.value = true;
  try {
    const response = await api.get('/profile');
    if (response.data.success) {
      profile.value = response.data.data;
      // Initialize form with current profile data
      form.value = {
        first_name: profile.value.first_name || '',
        last_name: profile.value.last_name || '',
        email: profile.value.email || '',
        phone: profile.value.phone || '',
        avatar: profile.value.avatar || '',
      };
    }
  } catch (error) {
    console.error('Failed to fetch profile:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch profile',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
}

function startEditing() {
  editing.value = true;
  activeTab.value = 'personal';
}

function cancelEditing() {
  editing.value = false;
  // Reset form to original values
  if (profile.value) {
    form.value = {
      first_name: profile.value.first_name || '',
      last_name: profile.value.last_name || '',
      email: profile.value.email || '',
      phone: profile.value.phone || '',
      avatar: profile.value.avatar || '',
    };
  }
}

async function updateProfile() {
  saving.value = true;
  try {
    const response = await api.put('/profile', form.value);
    if (response.data.success) {
      profile.value = response.data.data;
      // Update auth store with new user data
      await authStore.fetchUser();
      editing.value = false;
      $q.notify({
        type: 'positive',
        message: 'Profile updated successfully',
        position: 'top',
      });
    }
  } catch (error) {
    console.error('Failed to update profile:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to update profile',
      position: 'top',
    });
  } finally {
    saving.value = false;
  }
}

async function changePassword() {
  changingPassword.value = true;
  try {
    const response = await api.put('/profile', {
      current_password: passwordForm.value.current_password,
      password: passwordForm.value.password,
      password_confirmation: passwordForm.value.password_confirmation,
    });
    if (response.data.success) {
      // Reset password form
      passwordForm.value = {
        current_password: '',
        password: '',
        password_confirmation: '',
      };
      $q.notify({
        type: 'positive',
        message: 'Password changed successfully',
        position: 'top',
      });
    }
  } catch (error) {
    console.error('Failed to change password:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to change password',
      position: 'top',
    });
  } finally {
    changingPassword.value = false;
  }
}
</script>

<style lang="scss" scoped>
.profile-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: var(--spacing-2xl);
}

.profile-content {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
  max-width: 1200px;
  margin: 0 auto;
  
  @media (min-width: 768px) {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: var(--spacing-lg);
  }
}

.profile-overview-card {
  @media (min-width: 768px) {
    grid-column: 1;
  }
}

.profile-avatar-section {
  text-align: center;
}

.profile-avatar {
  margin-bottom: var(--spacing-md);
}

.profile-name {
  font-size: var(--font-size-xl);
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: var(--spacing-xs);
}

.profile-email {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  margin-bottom: var(--spacing-md);
}

.profile-roles {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: var(--spacing-xs);
  margin-bottom: var(--spacing-md);
}

.profile-school {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
}

.role-info-card {
  @media (min-width: 768px) {
    grid-column: 1;
  }
}

.card-title {
  font-size: var(--font-size-lg);
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: var(--spacing-md);
}

.info-grid {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.info-item {
  .info-label {
    font-size: var(--font-size-xs);
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: var(--spacing-xs);
  }
  
  .info-value {
    font-size: var(--font-size-base);
    color: var(--text-primary);
    font-weight: 500;
  }
}

.profile-details-card {
  @media (min-width: 768px) {
    grid-column: 2;
  }
}

.profile-tabs {
  :deep(.q-tab) {
    min-width: auto;
    padding: var(--spacing-sm) var(--spacing-md);
    
    @media (max-width: 599px) {
      font-size: var(--font-size-sm);
      padding: var(--spacing-xs) var(--spacing-sm);
    }
  }
}

.mobile-btn {
  @media (max-width: 599px) {
    min-width: 0;
    padding: var(--spacing-sm);
  }
}
</style>
