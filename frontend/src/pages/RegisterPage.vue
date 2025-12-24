<template>
  <q-page class="register-page flex flex-center">
    <div class="register-container">
      <q-card class="register-card">
        <q-card-section class="q-pa-xl">
          <!-- Logo Section -->
          <div class="text-center q-mb-xl">
            <div class="row items-center justify-center q-mb-md">
              <q-icon name="school" size="48px" color="primary" class="q-mr-sm" />
              <div class="text-h4 text-weight-bold text-primary">SMS</div>
            </div>
            <div class="text-h5 text-weight-bold q-mt-sm">Create Account</div>
            <div class="text-body1 text-grey-7 q-mt-xs">
              Sign up to get started
            </div>
          </div>

          <!-- Registration Form -->
          <q-form @submit="onSubmit" class="q-gutter-md">
            <div class="row q-col-gutter-sm">
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="form.first_name"
                  label="First Name"
                  outlined
                  dense
                  :rules="[val => !!val || 'First name is required']"
                  class="register-input"
                >
                  <template v-slot:prepend>
                    <q-icon name="person" />
                  </template>
                </q-input>
              </div>
              <div class="col-12 col-sm-6">
                <q-input
                  v-model="form.last_name"
                  label="Last Name"
                  outlined
                  dense
                  :rules="[val => !!val || 'Last name is required']"
                  class="register-input"
                >
                  <template v-slot:prepend>
                    <q-icon name="person" />
                  </template>
                </q-input>
              </div>
            </div>

            <q-input
              v-model="form.email"
              label="Email"
              type="email"
              outlined
              dense
              :rules="[val => !!val || 'Email is required', val => /.+@.+\..+/.test(val) || 'Email must be valid']"
              class="register-input"
            >
              <template v-slot:prepend>
                <q-icon name="email" />
              </template>
            </q-input>

            <q-input
              v-model="form.password"
              label="Password"
              :type="showPassword ? 'text' : 'password'"
              outlined
              dense
              :rules="[val => !!val || 'Password is required', val => val.length >= 8 || 'Password must be at least 8 characters']"
              class="register-input"
            >
              <template v-slot:prepend>
                <q-icon name="lock" />
              </template>
              <template v-slot:append>
                <q-icon
                  :name="showPassword ? 'visibility' : 'visibility_off'"
                  class="cursor-pointer"
                  @click="showPassword = !showPassword"
                />
              </template>
            </q-input>

            <q-input
              v-model="form.password_confirmation"
              label="Confirm Password"
              :type="showPasswordConfirm ? 'text' : 'password'"
              outlined
              dense
              :rules="[val => !!val || 'Please confirm password', val => val === form.password || 'Passwords do not match']"
              class="register-input"
            >
              <template v-slot:prepend>
                <q-icon name="lock" />
              </template>
              <template v-slot:append>
                <q-icon
                  :name="showPasswordConfirm ? 'visibility' : 'visibility_off'"
                  class="cursor-pointer"
                  @click="showPasswordConfirm = !showPasswordConfirm"
                />
              </template>
            </q-input>

            <q-checkbox v-model="agreeToTerms" dense>
              <span class="text-body2">
                I agree to the 
                <q-btn flat dense label="Terms & Conditions" color="primary" size="sm" />
              </span>
            </q-checkbox>

            <q-btn
              type="submit"
              label="Sign Up"
              color="primary"
              class="full-width register-button"
              :loading="loading"
              unelevated
              size="lg"
            />

            <div class="text-center q-mt-md">
              <span class="text-grey-7">Already have an account? </span>
              <q-btn
                flat
                dense
                label="Sign In"
                to="/login"
                color="primary"
                no-caps
                class="q-ml-xs"
              />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from 'src/stores/auth';
import { useQuasar } from 'quasar';
import api from 'src/services/api';

const $q = useQuasar();
const router = useRouter();
const authStore = useAuthStore();

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const loading = ref(false);
const showPassword = ref(false);
const showPasswordConfirm = ref(false);
const agreeToTerms = ref(false);

async function onSubmit() {
  if (!agreeToTerms.value) {
    $q.notify({
      type: 'warning',
      message: 'Please agree to the Terms & Conditions',
      position: 'top',
      icon: 'warning',
    });
    return;
  }

  loading.value = true;

  try {
    const result = await authStore.register(form.value);

    if (result.success) {
      $q.notify({
        type: 'positive',
        message: 'Registration successful!',
        position: 'top',
        icon: 'check_circle',
      });

      // Check if user is a parent and redirect to link child page
      const user = authStore.user;
      if (user && authStore.isParent) {
        // Check if parent has children
        try {
          const childrenResponse = await api.get('/parent/children');
          if (childrenResponse.data.success && childrenResponse.data.data.length === 0) {
            router.push('/app/parent/link-child');
            return;
          }
        } catch (error) {
          // If error, still redirect to link child page for new parents
          router.push('/app/parent/link-child');
          return;
        }
      }

      router.push('/app/dashboard');
    } else {
      $q.notify({
        type: 'negative',
        message: result.message || 'Registration failed',
        position: 'top',
        icon: 'error',
      });
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'An error occurred. Please try again.',
      position: 'top',
      icon: 'error',
    });
  } finally {
    loading.value = false;
  }
}
</script>

<style lang="scss" scoped>
.register-page {
  min-height: 100vh;
  position: relative;
}

.register-container {
  width: 100%;
  max-width: 500px;
  position: relative;
  z-index: 1;
  animation: scaleIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.register-card {
  border-radius: 24px;
  backdrop-filter: blur(20px);
  background: rgba(255, 255, 255, 0.95);
  border: 1px solid rgba(255, 255, 255, 0.3);
  box-shadow: 
    0 8px 32px rgba(0, 0, 0, 0.1),
    0 2px 8px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
  
  &:hover {
    box-shadow: 
      0 12px 40px rgba(0, 0, 0, 0.15),
      0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
  }
}

.register-input {
  :deep(.q-field__control) {
    border-radius: 12px;
    transition: all 0.3s ease;
    
    &:hover {
      background: rgba(0, 0, 0, 0.02);
    }
  }
  
  :deep(.q-field--focused .q-field__control) {
    box-shadow: 0 0 0 2px rgba(156, 39, 176, 0.2);
  }
}

.register-button {
  border-radius: 12px;
  text-transform: none;
  font-weight: 600;
  letter-spacing: 0.5px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  
  &:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(156, 39, 176, 0.4);
  }
  
  &:active:not(:disabled) {
    transform: translateY(0);
  }
}

@keyframes scaleIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
</style>
