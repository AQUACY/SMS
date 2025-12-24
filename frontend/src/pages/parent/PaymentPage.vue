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
          <div class="text-h6 text-weight-bold">Make Payment</div>
          <div class="text-caption text-grey-7">Subscribe to a term</div>
        </div>
      </div>
    </div>

    <!-- Content Area -->
    <div class="parent-content q-pa-md">
      <div v-if="loading" class="text-center q-pa-xl">
        <q-spinner color="primary" size="3em" />
      </div>

      <q-card v-else class="form-card">
        <q-card-section class="q-pa-md">
          <q-form @submit="submitPayment" class="q-gutter-md">
            <div class="text-h6 q-mb-md">Payment Information</div>

            <div class="q-gutter-md">
              <q-select
                v-model="form.student_id"
                :options="children"
                option-label="full_name"
                option-value="id"
                emit-value
                map-options
                label="Student *"
                outlined
                :rules="[(val) => !!val || 'Student is required']"
                :loading="loadingChildren"
                :disable="!!route.params.studentId"
                size="lg"
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

              <q-select
                v-model="form.term_id"
                :options="terms"
                option-label="name"
                option-value="id"
                emit-value
                map-options
                label="Term *"
                outlined
                :rules="[(val) => !!val || 'Term is required']"
                :loading="loadingTerms"
                :disable="!!route.params.termId"
                size="lg"
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

              <q-input
                v-model.number="form.amount"
                label="Amount (GHS) *"
                outlined
                type="number"
                step="0.01"
                min="0"
                :rules="[
                  (val) => !!val || 'Amount is required',
                  (val) => val > 0 || 'Amount must be greater than 0',
                ]"
                hint="Enter the subscription amount"
                size="lg"
              />

              <div>
                <div class="text-subtitle2 q-mb-sm">Payment Method *</div>
                <q-option-group
                  v-model="form.payment_method"
                  :options="paymentMethodOptions"
                  color="primary"
                  type="radio"
                  :rules="[(val) => !!val || 'Payment method is required']"
                />
              </div>

              <!-- Mobile Money Fields -->
              <template v-if="form.payment_method === 'momo'">
                <q-select
                  v-model="form.momo_provider"
                  :options="momoProviderOptions"
                  label="Mobile Money Provider *"
                  outlined
                  :rules="[(val) => !!val || 'Provider is required']"
                  size="lg"
                />
                <q-input
                  v-model="form.momo_number"
                  label="Mobile Money Number *"
                  outlined
                  mask="###########"
                  :rules="[
                    (val) => !!val || 'Mobile Money number is required',
                    (val) => val.length >= 10 || 'Invalid mobile number',
                  ]"
                  hint="e.g., 0244123456"
                  size="lg"
                />
              </template>

              <q-banner v-if="form.payment_method === 'momo'" class="bg-info text-white">
                <template v-slot:avatar>
                  <q-icon name="info" />
                </template>
                After submitting, you will receive a payment prompt on your mobile device.
                Please approve the payment to complete your subscription.
              </q-banner>

              <!-- Payment Summary (Mobile) -->
              <q-card v-if="selectedStudent || selectedTerm" class="summary-card q-mt-md">
                <q-card-section>
                  <div class="text-h6 q-mb-md">Payment Summary</div>
                  <div class="q-gutter-y-sm">
                    <div v-if="selectedStudent">
                      <div class="text-caption text-grey-7">Student</div>
                      <div class="text-body2">{{ selectedStudent.full_name }}</div>
                      <div class="text-caption text-grey-6">{{ selectedStudent.student_number }}</div>
                    </div>
                    <q-separator v-if="selectedStudent && selectedTerm" />
                    <div v-if="selectedTerm">
                      <div class="text-caption text-grey-7">Term</div>
                      <div class="text-body2">{{ selectedTerm.name }}</div>
                      <div class="text-caption text-grey-6">{{ selectedTerm.academic_year?.name || '' }}</div>
                    </div>
                    <q-separator v-if="selectedTerm && form.amount" />
                    <div v-if="form.amount" class="q-pt-sm">
                      <div class="text-caption text-grey-7">Amount</div>
                      <div class="text-h6 text-weight-bold text-primary">
                        GHS {{ parseFloat(form.amount || 0).toFixed(2) }}
                      </div>
                    </div>
                  </div>
                </q-card-section>
              </q-card>

              <q-btn
                type="submit"
                color="primary"
                label="Submit Payment"
                icon="payment"
                unelevated
                :loading="submitting"
                :disable="!form.student_id || !form.term_id || !form.amount || !form.payment_method || paymentSubmitted"
                size="lg"
                class="full-width q-mt-md"
              >
                <q-tooltip v-if="paymentSubmitted">
                  Payment already submitted. Please wait for confirmation.
                </q-tooltip>
              </q-btn>
              
              <q-banner
                v-if="paymentSubmitted && currentPaymentId"
                dense
                rounded
                class="bg-info text-white q-mt-md"
              >
                <template v-slot:avatar>
                  <q-icon name="info" color="white" />
                </template>
                <div class="text-body2">
                  Payment submitted. Checking status... Please approve the payment on your mobile device.
                </div>
                <template v-slot:action>
                  <q-btn
                    flat
                    label="Check Status"
                    color="white"
                    size="sm"
                    @click="startPaymentStatusPolling(currentPaymentId)"
                  />
                </template>
              </q-banner>
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
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import api from 'src/services/api';

const route = useRoute();
const router = useRouter();
const $q = useQuasar();

const loading = ref(false);
const loadingChildren = ref(false);
const loadingTerms = ref(false);
const submitting = ref(false);
const paymentSubmitted = ref(false);
const currentPaymentId = ref(null);
const paymentStatusInterval = ref(null);
const children = ref([]);
const terms = ref([]);

const form = ref({
  student_id: route.params.studentId ? parseInt(route.params.studentId) : null,
  term_id: route.params.termId ? parseInt(route.params.termId) : null,
  amount: null,
  payment_method: 'momo',
  momo_provider: null,
  momo_number: null,
});

const paymentMethodOptions = [
  { label: 'Mobile Money', value: 'momo' },
  { label: 'Bank Transfer', value: 'bank' },
  { label: 'Cash', value: 'cash' },
  { label: 'Other', value: 'other' },
];

const momoProviderOptions = [
  { label: 'MTN Mobile Money', value: 'mtn' },
  { label: 'Vodafone Cash', value: 'vodafone' },
  { label: 'AirtelTigo Money', value: 'airteltigo' },
];

const selectedStudent = computed(() => {
  return children.value.find(c => c.id === form.value.student_id);
});

const selectedTerm = computed(() => {
  return terms.value.find(t => t.id === form.value.term_id);
});

onMounted(() => {
  fetchChildren();
  fetchTerms();
});

async function fetchChildren() {
  loadingChildren.value = true;
  try {
    const response = await api.get('/parent/children');
    if (response.data.success) {
      children.value = response.data.data || [];
      // If studentId is in route params, ensure it's in the list
      if (route.params.studentId && !children.value.find(c => c.id === parseInt(route.params.studentId))) {
        $q.notify({
          type: 'warning',
          message: 'Selected student not found in your children list',
          position: 'top',
        });
      }
    }
  } catch (error) {
    console.error('Failed to fetch children:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch children',
      position: 'top',
    });
  } finally {
    loadingChildren.value = false;
  }
}

async function fetchTerms() {
  loadingTerms.value = true;
  try {
    const response = await api.get('/terms', { params: { per_page: 100 } });
    if (response.data.success) {
      terms.value = response.data.data || [];
      // If termId is in route params, ensure it's in the list
      if (route.params.termId && !terms.value.find(t => t.id === parseInt(route.params.termId))) {
        $q.notify({
          type: 'warning',
          message: 'Selected term not found',
          position: 'top',
        });
      }
    }
  } catch (error) {
    console.error('Failed to fetch terms:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch terms',
      position: 'top',
    });
  } finally {
    loadingTerms.value = false;
  }
}

async function submitPayment() {
  // Prevent multiple submissions
  if (submitting.value || paymentSubmitted.value) {
    return;
  }

  submitting.value = true;
  paymentSubmitted.value = true;

  try {
    const paymentData = {
      student_id: form.value.student_id,
      term_id: form.value.term_id,
      amount: parseFloat(form.value.amount),
      payment_method: form.value.payment_method,
    };

    if (form.value.payment_method === 'momo') {
      paymentData.momo_provider = form.value.momo_provider;
      paymentData.momo_number = form.value.momo_number;
    }

    const response = await api.post('/parent/payments', paymentData);
    
    if (response.data.success) {
      const payment = response.data.data;
      currentPaymentId.value = payment.id;

      $q.notify({
        type: 'positive',
        message: 'Payment initiated successfully. Please check your mobile device for payment prompt.',
        position: 'top',
        timeout: 5000,
        actions: [
          {
            label: 'View Status',
            color: 'white',
            handler: () => {
              startPaymentStatusPolling(payment.id);
            },
          },
        ],
      });

      // Start polling payment status
      startPaymentStatusPolling(payment.id);

      // Disable form to prevent changes
      disableForm();
    }
  } catch (error) {
    console.error('Failed to submit payment:', error);
    
    // Handle duplicate payment error
    if (error.response?.status === 422 && error.response?.data?.data?.payment_id) {
      const existingPayment = error.response.data.data;
      
      $q.dialog({
        title: 'Payment Already in Progress',
        message: existingPayment.message || 'A payment is already in progress for this student and term.',
        ok: {
          label: 'View Payment Status',
          color: 'primary',
        },
        cancel: {
          label: 'Cancel',
          flat: true,
        },
      }).onOk(() => {
        startPaymentStatusPolling(existingPayment.payment_id);
        router.push(`/app/parent/payments`);
      });
    } else {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to submit payment. Please try again.',
        position: 'top',
        timeout: 5000,
      });
    }

    // Allow retry on error
    paymentSubmitted.value = false;
  } finally {
    submitting.value = false;
  }
}

function disableForm() {
  // Disable all form fields after submission
  form.value = {
    ...form.value,
    // Keep values but mark as submitted
  };
}

function startPaymentStatusPolling(paymentId) {
  // Clear any existing interval
  if (paymentStatusInterval.value) {
    clearInterval(paymentStatusInterval.value);
  }

  // Poll payment status every 5 seconds
  paymentStatusInterval.value = setInterval(async () => {
    try {
      const response = await api.get(`/parent/payments/${paymentId}/status`);
      
      if (response.data.success) {
        const payment = response.data.data;
        
        if (payment.status === 'completed') {
          // Payment completed successfully
          clearInterval(paymentStatusInterval.value);
          paymentStatusInterval.value = null;
          
          $q.notify({
            type: 'positive',
            message: 'Payment completed successfully! Your subscription is now active.',
            position: 'top',
            timeout: 5000,
          });
          
          // Redirect to payments page after a short delay
          setTimeout(() => {
            router.push('/app/parent/payments');
          }, 2000);
        } else if (payment.status === 'failed') {
          // Payment failed
          clearInterval(paymentStatusInterval.value);
          paymentStatusInterval.value = null;
          
          $q.dialog({
            title: 'Payment Failed',
            message: 'Your payment could not be processed. Would you like to retry?',
            ok: {
              label: 'Retry Payment',
              color: 'primary',
            },
            cancel: {
              label: 'Cancel',
              flat: true,
            },
          }).onOk(() => {
            retryPayment(paymentId);
          });
        }
        // Continue polling if status is 'pending' or 'processing'
      }
    } catch (error) {
      console.error('Failed to check payment status:', error);
      // Continue polling on error (might be temporary network issue)
    }
  }, 5000); // Poll every 5 seconds

  // Stop polling after 5 minutes (300 seconds)
  setTimeout(() => {
    if (paymentStatusInterval.value) {
      clearInterval(paymentStatusInterval.value);
      paymentStatusInterval.value = null;
      
      $q.notify({
        type: 'warning',
        message: 'Payment status check timed out. Please check your payment status manually.',
        position: 'top',
        timeout: 5000,
      });
    }
  }, 300000); // 5 minutes
}

async function retryPayment(paymentId) {
  submitting.value = true;
  
  try {
    const response = await api.post(`/parent/payments/${paymentId}/retry`);
    
    if (response.data.success) {
      const newPayment = response.data.data;
      currentPaymentId.value = newPayment.id;
      
      $q.notify({
        type: 'positive',
        message: 'Payment retry initiated. Please check your mobile device.',
        position: 'top',
        timeout: 5000,
      });
      
      // Start polling the new payment
      startPaymentStatusPolling(newPayment.id);
    }
  } catch (error) {
    console.error('Failed to retry payment:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to retry payment. Please try again.',
      position: 'top',
    });
  } finally {
    submitting.value = false;
  }
}

// Cleanup on unmount
onBeforeUnmount(() => {
  if (paymentStatusInterval.value) {
    clearInterval(paymentStatusInterval.value);
  }
});
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

.form-card {
  border-radius: 16px;
  border: none;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  background: white;
}

.summary-card {
  border-radius: 12px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  background: #f9f9f9;
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
