<template>
  <q-page class="parent-page">
    <!-- Mobile Header -->
    <div class="parent-header q-pa-md">
      <div class="row items-center">
        <q-btn
          flat
          round
          icon="arrow_back"
          @click="$router.push('/app/parent/payments')"
          class="q-mr-sm"
          size="md"
        />
        <div class="col">
          <div class="text-h6 text-weight-bold">Verify Payment</div>
          <div class="text-caption text-grey-7">Activate your subscription</div>
        </div>
      </div>
    </div>

    <!-- Content Area -->
    <div class="parent-content q-pa-md">
      <q-card class="form-card">
        <q-card-section class="q-pa-md">
          <div class="text-h6 q-mb-md">Payment Verification</div>
          
          <q-banner rounded class="bg-info text-white q-mb-md">
            <template v-slot:avatar>
              <q-icon name="info" color="white" />
            </template>
            <div class="text-body2">
              Enter your verification token and payment reference to activate your subscription immediately.
              The token was provided when you initiated the payment.
            </div>
          </q-banner>

          <q-form @submit="verifyPayment" class="q-gutter-md">
            <q-input
              v-model="form.verification_token"
              label="Verification Token *"
              outlined
              hint="Format: TOKEN-XXXX-XXXX"
              :rules="[(val) => !!val || 'Verification token is required']"
              size="lg"
              class="q-mb-md"
            >
              <template v-slot:prepend>
                <q-icon name="vpn_key" />
              </template>
            </q-input>

            <q-input
              v-model="form.payment_reference"
              label="Payment Reference *"
              outlined
              hint="The reference number from your payment (e.g., MTN transaction ID, bank reference)"
              :rules="[(val) => !!val || 'Payment reference is required']"
              size="lg"
              class="q-mb-md"
            >
              <template v-slot:prepend>
                <q-icon name="receipt" />
              </template>
            </q-input>

            <q-btn
              type="submit"
              color="primary"
              label="Verify Payment"
              icon="check_circle"
              unelevated
              :loading="verifying"
              :disable="!form.verification_token || !form.payment_reference"
              size="lg"
              class="full-width q-mt-md"
            />

            <q-btn
              flat
              label="Cancel"
              @click="$router.push('/app/parent/payments')"
              class="full-width q-mt-sm"
              size="md"
            />
          </q-form>
        </q-card-section>
      </q-card>

      <!-- Instructions Card -->
      <q-card class="info-card q-mt-md">
        <q-card-section>
          <div class="text-h6 q-mb-md">How to Verify Payment</div>
          <div class="q-gutter-y-sm">
            <div class="row items-start">
              <q-icon name="looks_one" color="primary" size="20px" class="q-mr-sm q-mt-xs" />
              <div class="col">
                <div class="text-body2 text-weight-medium">Get Your Token</div>
                <div class="text-caption text-grey-7">
                  When you initiate a payment, you'll receive a verification token (e.g., TOKEN-A1B2-C3D4)
                </div>
              </div>
            </div>
            <div class="row items-start">
              <q-icon name="looks_two" color="primary" size="20px" class="q-mr-sm q-mt-xs" />
              <div class="col">
                <div class="text-body2 text-weight-medium">Make Payment</div>
                <div class="text-caption text-grey-7">
                  Use the token in your payment reference when making the payment
                </div>
              </div>
            </div>
            <div class="row items-start">
              <q-icon name="looks_3" color="primary" size="20px" class="q-mr-sm q-mt-xs" />
              <div class="col">
                <div class="text-body2 text-weight-medium">Verify Payment</div>
                <div class="text-caption text-grey-7">
                  Enter your token and payment reference here to activate your subscription immediately
                </div>
              </div>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import api from 'src/services/api';

const route = useRoute();
const router = useRouter();
const $q = useQuasar();

const verifying = ref(false);

const form = ref({
  verification_token: '',
  payment_reference: '',
});

onMounted(() => {
  // If token is in query params, pre-fill it
  if (route.query.token) {
    form.value.verification_token = route.query.token;
  }
});

async function verifyPayment() {
  verifying.value = true;
  
  try {
    const response = await api.post('/parent/payments/verify', form.value);
    
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: response.data.data?.message || 'Payment verified successfully! Your subscription is now active.',
        position: 'top',
        timeout: 5000,
      });
      
      // Redirect to payments page after a short delay
      setTimeout(() => {
        router.push('/app/parent/payments');
      }, 2000);
    }
  } catch (error) {
    console.error('Failed to verify payment:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to verify payment. Please check your token and reference.',
      position: 'top',
      timeout: 5000,
    });
  } finally {
    verifying.value = false;
  }
}
</script>

<style lang="scss" scoped>
.parent-page {
  background: #f5f5f5;
  min-height: 100vh;
}

.parent-header {
  background: white;
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  position: sticky;
  top: 0;
  z-index: 100;
}

.parent-content {
  max-width: 800px;
  margin: 0 auto;
}

.form-card,
.info-card {
  border-radius: 16px;
  border: none;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  background: white;
}

// Mobile optimizations
@media (max-width: 600px) {
  .parent-header {
    padding: 12px 16px;
  }

  .parent-content {
    padding: 12px;
  }
}
</style>

