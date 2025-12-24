<template>
  <q-page class="login-page flex flex-center">
    <div class="login-container">
      <q-card class="login-card">
        <q-card-section class="q-pa-xl">
          <!-- Logo Section -->
          <div class="text-center q-mb-xl">
            <div class="row items-center justify-center q-mb-md">
              <q-icon name="school" size="48px" color="primary" class="q-mr-sm" />
              <div class="text-h4 text-weight-bold text-primary">SMS</div>
            </div>
            <div class="text-h5 text-weight-bold q-mt-sm">Welcome Back</div>
            <div class="text-body1 text-grey-7 q-mt-xs">
              Sign in to continue to your account
            </div>
          </div>

          <!-- Login Form -->
          <q-form @submit="onSubmit" class="q-gutter-md">
            <q-input
              v-model="form.email"
              label="Email"
              type="email"
              outlined
              dense
              :rules="[val => !!val || 'Email is required', val => /.+@.+\..+/.test(val) || 'Email must be valid']"
              class="login-input"
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
              :rules="[val => !!val || 'Password is required']"
              class="login-input"
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

            <div class="row items-center justify-between">
              <q-checkbox v-model="rememberMe" label="Remember me" dense />
              <q-btn flat dense label="Forgot password?" color="primary" size="sm" />
            </div>

            <q-btn
              type="submit"
              label="Sign In"
              color="primary"
              class="full-width login-button"
              :loading="loading"
              unelevated
              size="lg"
            />

            <div class="text-center q-mt-md">
              <span class="text-grey-7">Don't have an account? </span>
              <q-btn
                flat
                dense
                label="Sign Up"
                to="/register"
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
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from 'src/stores/auth';
import { useQuasar } from 'quasar';
import api from 'src/services/api';

const $q = useQuasar();
const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const form = ref({
  email: '',
  password: '',
});

const loading = ref(false);
const showPassword = ref(false);
const rememberMe = ref(false);

async function onSubmit() {
  loading.value = true;

  try {
    const result = await authStore.login(form.value);

    if (result.success) {
      $q.notify({
        type: 'positive',
        message: 'Login successful!',
        position: 'top',
        icon: 'check_circle',
      });

      // Check if user is a parent and has no children, redirect to link child page
      if (authStore.isParent) {
        try {
          const childrenResponse = await api.get('/parent/children');
          if (childrenResponse.data.success && childrenResponse.data.data.length === 0) {
            router.push('/app/parent/link-child');
            return;
          }
        } catch (error) {
          // If error checking children, continue to normal redirect
        }
      }

      // Redirect to dashboard or intended page
      const redirect = route.query.redirect || '/app/dashboard';
      router.push(redirect);
    } else {
      $q.notify({
        type: 'negative',
        message: result.message || 'Login failed',
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
.login-page {
  min-height: 100vh;
  position: relative;
}

.login-container {
  width: 100%;
  max-width: 420px;
  position: relative;
  z-index: 1;
  animation: scaleIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.login-card {
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

.login-input {
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

.login-button {
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
