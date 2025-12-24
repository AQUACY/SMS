<template>
  <q-page class="q-pa-lg">
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">My Profile</div>
        <div class="text-body2 text-grey-7">View and manage your profile information</div>
      </div>
      <q-btn
        v-if="!editing"
        color="primary"
        label="Edit Profile"
        icon="edit"
        unelevated
        @click="startEditing"
      />
      <div v-else>
        <q-btn
          flat
          label="Cancel"
          @click="cancelEditing"
          class="q-mr-sm"
        />
        <q-btn
          color="primary"
          label="Save Changes"
          icon="save"
          unelevated
          @click="updateProfile"
          :loading="saving"
        />
      </div>
    </div>

    <div v-if="loading" class="row justify-center q-pa-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <div v-else-if="profile" class="row q-col-gutter-md">
      <!-- Profile Overview Card -->
      <div class="col-12 col-md-4">
        <q-card class="widget-card">
          <q-card-section class="text-center">
            <q-avatar size="120px" class="q-mb-md" :color="profile.avatar ? 'transparent' : 'primary'">
              <img v-if="profile.avatar" :src="profile.avatar" alt="Avatar" />
              <q-icon v-else name="person" size="60px" color="white" />
            </q-avatar>
            <div class="text-h6 text-weight-bold q-mt-md">
              {{ profile.first_name }} {{ profile.last_name }}
            </div>
            <div class="text-body2 text-grey-7 q-mt-xs">{{ profile.email }}</div>
            <div class="q-mt-md">
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
            <div v-if="profile.school" class="text-body2 text-grey-7 q-mt-md">
              <q-icon name="school" size="16px" class="q-mr-xs" />
              {{ profile.school.name }}
            </div>
          </q-card-section>
        </q-card>

        <!-- Role-Specific Information -->
        <q-card v-if="profile.teacher" class="widget-card q-mt-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">Teacher Information</div>
            <div class="q-gutter-sm">
              <div>
                <div class="text-caption text-grey-7">Employee Number</div>
                <div class="text-body2">{{ profile.teacher.employee_number || 'N/A' }}</div>
              </div>
              <div v-if="profile.teacher.department">
                <div class="text-caption text-grey-7">Department</div>
                <div class="text-body2">{{ profile.teacher.department }}</div>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <q-card v-if="profile.parent" class="widget-card q-mt-md">
          <q-card-section>
            <div class="text-h6 q-mb-md">Parent Information</div>
            <div class="q-gutter-sm">
              <div v-if="profile.parent.occupation">
                <div class="text-caption text-grey-7">Occupation</div>
                <div class="text-body2">{{ profile.parent.occupation }}</div>
              </div>
              <div v-if="profile.parent.address">
                <div class="text-caption text-grey-7">Address</div>
                <div class="text-body2">{{ profile.parent.address }}</div>
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- Profile Details Card -->
      <div class="col-12 col-md-8">
        <q-card class="widget-card">
          <q-card-section>
            <q-tabs v-model="activeTab" class="text-grey" active-color="primary" indicator-color="primary">
              <q-tab name="personal" label="Personal Information" icon="person" />
              <q-tab name="password" label="Change Password" icon="lock" />
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
          </q-card-section>
        </q-card>
      </div>
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
.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}
</style>
