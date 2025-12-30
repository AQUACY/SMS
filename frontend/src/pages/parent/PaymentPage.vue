<template>
  <q-page class="payment-page">
    <MobilePageHeader
      :title="paymentType === 'fee' ? 'Pay School Fees' : 'Subscribe'"
      :subtitle="paymentType === 'fee' ? 'Pay term fees to the school' : 'Subscribe to view academic records'"
      :show-back="true"
      @back="$router.push('/app/parent/payments')"
    />

    <div v-if="loading" class="detail-loading">
      <MobileCard variant="default" padding="md">
        <q-skeleton type="rect" height="200px" class="q-mb-md" />
        <q-skeleton type="text" width="60%" />
        <q-skeleton type="text" width="40%" />
      </MobileCard>
    </div>

    <div v-else class="form-content">
      <MobileCard variant="default" padding="md">
          <q-banner 
            v-if="paymentType === 'fee'"
            rounded 
            class="bg-info text-white q-mb-md"
          >
            <template v-slot:avatar>
              <q-icon name="school" color="white" />
            </template>
            <div class="text-body2">
              <strong>School Fees:</strong> Please proceed to the school accounts office to make payment. The accounts manager will verify and process your payment manually.
            </div>
          </q-banner>

          <q-banner 
            v-else
            rounded 
            class="bg-primary text-white q-mb-md"
          >
            <template v-slot:avatar>
              <q-icon name="card_membership" color="white" />
            </template>
            <div class="text-body2">
              <strong>Subscription:</strong> This payment enables you to view your child's academic records, attendance, results, and report cards on the platform.
            </div>
          </q-banner>

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
                :hint="paymentType === 'fee' 
                  ? (selectedTerm ? `Fee for ${selectedTerm.name}` : 'Enter the fee amount')
                  : 'Enter the subscription amount'"
                size="lg"
                :loading="loadingTerms"
              >
                <template v-slot:append>
                  <q-btn
                    v-if="paymentType === 'fee' && selectedTerm"
                    flat
                    dense
                    round
                    icon="refresh"
                    @click="fetchPaymentAmount(form.term_id)"
                    size="sm"
                  >
                    <q-tooltip>Refresh fee amount</q-tooltip>
                  </q-btn>
                  <q-btn
                    v-else-if="paymentType === 'subscription' && form.student_id"
                    flat
                    dense
                    round
                    icon="refresh"
                    @click="fetchPaymentAmount(null)"
                    size="sm"
                  >
                    <q-tooltip>Refresh subscription price</q-tooltip>
                  </q-btn>
                </template>
              </q-input>

              <!-- Payment Method - Only show for subscriptions -->
              <template v-if="paymentType === 'subscription'">
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
                    option-label="label"
                    option-value="value"
                    emit-value
                    map-options
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
                  <q-banner class="bg-info text-white">
                    <template v-slot:avatar>
                      <q-icon name="info" />
                    </template>
                    After submitting, you will receive a payment prompt on your mobile device.
                    Please approve the payment to complete your subscription.
                  </q-banner>
                </template>
              </template>
              
              <!-- Fee Payment Info -->
              <q-banner v-if="paymentType === 'fee'" class="bg-warning text-white">
                <template v-slot:avatar>
                  <q-icon name="info" />
                </template>
                <div class="text-body2">
                  <strong>Manual Payment Required:</strong> After creating this payment record, please visit the school accounts office to complete your payment. The accounts manager will verify and process it.
                </div>
              </q-banner>

              <!-- Payment Details for Subscription (if available) -->
              <q-card 
                v-if="paymentType === 'subscription' && subscriptionPrice && (subscriptionPrice.payment_number || subscriptionPrice.payment_network || subscriptionPrice.payment_name)"
                class="bg-primary text-white q-mt-md"
              >
                <q-card-section>
                  <div class="text-h6 q-mb-md">
                    <q-icon name="account_balance_wallet" class="q-mr-sm" />
                    Send Payment To
                  </div>
                  <div class="q-gutter-y-md">
                    <div v-if="subscriptionPrice.payment_network">
                      <div class="text-caption text-grey-3">Network</div>
                      <div class="text-body1 text-weight-medium">
                        {{ subscriptionPrice.payment_network === 'mtn' ? 'MTN Mobile Money' : subscriptionPrice.payment_network === 'vodafone' ? 'Vodafone Cash' : 'AirtelTigo Money' }}
                      </div>
                    </div>
                    <div v-if="subscriptionPrice.payment_number">
                      <div class="text-caption text-grey-3">Payment Number</div>
                      <div class="text-body1 text-weight-bold">
                        <q-icon name="phone" class="q-mr-sm" />
                        {{ subscriptionPrice.payment_number }}
                      </div>
                    </div>
                    <div v-if="subscriptionPrice.payment_name">
                      <div class="text-caption text-grey-3">Name to Expect</div>
                      <div class="text-body1 text-weight-medium">
                        <q-icon name="account_circle" class="q-mr-sm" />
                        {{ subscriptionPrice.payment_name }}
                      </div>
                    </div>
                    <q-separator dark class="q-my-sm" />
                    <div class="text-caption text-grey-3">
                      <q-icon name="info" class="q-mr-xs" />
                      Please ensure the name matches when sending payment
                    </div>
                  </div>
                </q-card-section>
              </q-card>

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
                :label="paymentType === 'fee' ? 'Pay School Fees' : 'Subscribe'"
                icon="payment"
                unelevated
                :loading="submitting"
                :disable="!form.student_id || !form.term_id || !form.amount || (paymentType === 'subscription' && !form.payment_method) || paymentSubmitted"
                size="lg"
                class="full-width q-mt-md"
              >
                <q-tooltip v-if="paymentSubmitted">
                  Payment already submitted. Please wait for confirmation.
                </q-tooltip>
              </q-btn>
              
              <q-banner
                v-if="paymentSubmitted && currentPaymentId && paymentType === 'subscription'"
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
            </div>
          </q-form>
      </MobileCard>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
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
const subscriptionPrice = ref(null);

// Determine payment type from query param or default to subscription
const paymentType = computed(() => route.query.type === 'fee' ? 'fee' : 'subscription');

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
  
  // If termId is in route, fetch amount for that term
  if (route.params.termId && paymentType.value === 'fee') {
    fetchPaymentAmount(parseInt(route.params.termId));
  } else if (paymentType.value === 'subscription') {
    // Fetch subscription price on mount if student is selected
    if (form.value.student_id) {
      fetchPaymentAmount(null);
    }
  }
});

// Watch for term and student selection to auto-fetch amount
watch([() => form.value.term_id, () => form.value.student_id, paymentType], ([newTermId, newStudentId, newType]) => {
  if (newTermId && newStudentId) {
    if (newType === 'fee') {
      // Auto-fetch fee when both student and term are selected
      subscriptionPrice.value = null; // Clear subscription price for fee payments
      fetchPaymentAmount(newTermId);
    } else if (newType === 'subscription' && newStudentId) {
      // Auto-fetch subscription price when student is selected
      fetchPaymentAmount(null);
    }
  } else {
    form.value.amount = null;
    subscriptionPrice.value = null;
  }
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

async function fetchPaymentAmount(termId) {
  if (!form.value.student_id) {
    return;
  }
  
  try {
    if (paymentType.value === 'fee') {
      // Fetch school fee
      const response = await api.get(`/fees/term/${termId}`, {
        params: {
          student_id: form.value.student_id,
        },
      });
      if (response.data.success && response.data.data?.fee) {
        const fee = response.data.data.fee;
        if (fee.amount) {
          form.value.amount = parseFloat(fee.amount);
        }
      }
    } else {
      // Fetch subscription price
      const response = await api.get(`/subscription-prices/student/${form.value.student_id}`);
      if (response.data.success && response.data.data?.price) {
        const price = response.data.data.price;
        subscriptionPrice.value = price;
        if (price.amount) {
          form.value.amount = parseFloat(price.amount);
        }
      } else {
        subscriptionPrice.value = null;
      }
    }
  } catch (error) {
    // Amount might not be configured yet, that's okay
    console.log('No amount configured or error fetching:', error);
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
      payment_type: paymentType.value === 'fee' ? 'fee_payment' : 'subscription_payment',
      amount: parseFloat(form.value.amount),
      payment_method: form.value.payment_method,
    };

    if (form.value.payment_method === 'momo') {
      paymentData.momo_provider = form.value.momo_provider;
      paymentData.momo_number = form.value.momo_number;
    }

    const response = await api.post('/parent/payments', paymentData);
    
    if (response.data.success) {
      const payment = response.data.data?.payment || response.data.data;
      const verificationToken = response.data.data?.verification_token;
      const message = response.data.data?.message || response.data.message;
      currentPaymentId.value = payment.id;

      // Handle different payment types
      if (paymentType.value === 'fee') {
        // Fee payment - manual verification required
        $q.notify({
          type: 'info',
          message: message || 'Fee payment record created. Please proceed to the school accounts office to complete payment.',
          position: 'top',
          timeout: 8000,
          icon: 'school',
        });
        
        // Redirect to payments page after a short delay
        setTimeout(() => {
          router.push('/app/parent/payments');
        }, 3000);
      } else if (form.value.payment_method === 'momo') {
        // Subscription MoMo payment - show instructions if available
        const instructions = response.data.data?.instructions;
        
        if (instructions) {
          $q.dialog({
            title: 'Payment Instructions',
            message: instructions,
            html: true,
            ok: {
              label: 'Got it',
              color: 'primary',
            },
          });
        }
        
        $q.notify({
          type: 'positive',
          message: message || 'Payment request created. Please complete payment on your mobile device.',
          position: 'top',
          timeout: 8000,
          icon: 'phone_android',
        });
        
        // Start polling payment status for subscriptions (auto-verifies on each check)
        startPaymentStatusPolling(payment.id);
      } else if (verificationToken) {
        // Non-MoMo payment - show token for manual verification
        $q.dialog({
          title: 'Payment Token Generated',
          message: `Your verification token is: ${verificationToken}`,
          html: true,
          prompt: {
            model: verificationToken,
            type: 'text',
            label: 'Verification Token',
            readonly: true,
          },
          ok: {
            label: 'Copy Token',
            color: 'primary',
          },
          cancel: {
            label: 'Close',
            flat: true,
          },
        }).onOk(() => {
          // Copy token to clipboard
          navigator.clipboard.writeText(verificationToken);
          $q.notify({
            type: 'positive',
            message: 'Token copied to clipboard!',
            position: 'top',
          });
        });

        const verifyMessage = paymentType.value === 'fee'
          ? 'Use the verification token in your payment reference. After payment, verify it to complete the fee payment.'
          : 'Use the verification token in your payment reference. After payment, verify it to activate your subscription immediately.';
        
        $q.notify({
          type: 'info',
          message: verifyMessage,
          position: 'top',
          timeout: 8000,
          actions: [
            {
              label: 'Verify Payment',
              color: 'white',
              handler: () => {
                router.push('/app/parent/verify-payment');
              },
            },
          ],
        });
        
        // Start polling payment status for non-MoMo subscription payments
        startPaymentStatusPolling(payment.id);
      }

      // Disable form to prevent changes
      disableForm();
    }
  } catch (error) {
    console.error('Failed to submit payment:', error);
    
    // Handle duplicate payment error
    if (error.response?.status === 422 && error.response?.data?.data?.payment_id) {
      const existingPayment = error.response.data.data;
      const isCompleted = existingPayment.verified_at || error.response.data.message?.includes('already been completed');
      
      if (isCompleted) {
        // Payment already completed
        $q.dialog({
          title: 'Fee Payment Already Completed',
          message: `
            <div class="q-pa-md">
              <p class="text-body1 q-mb-md">
                ${error.response.data.message || 'Fee payment for this student and term has already been completed and verified.'}
              </p>
              <div class="q-mt-md">
                <div class="text-body2 text-grey-7"><strong>Payment Reference:</strong> ${existingPayment.payment_reference || 'N/A'}</div>
                ${existingPayment.verified_at ? `<div class="text-body2 text-grey-7 q-mt-xs"><strong>Verified:</strong> ${new Date(existingPayment.verified_at).toLocaleString()}</div>` : ''}
              </div>
            </div>
          `,
          html: true,
          ok: {
            label: 'View Payment',
            color: 'primary',
          },
          cancel: {
            label: 'Close',
            flat: true,
          },
        }).onOk(() => {
          router.push(`/app/parent/payments`);
        });
      } else {
        // Payment in progress
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
      }
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
          
          const successMessage = payment.payment_type === 'subscription_payment'
            ? 'Payment completed successfully! Your subscription is now active.'
            : 'Fee payment completed successfully!';
          
          $q.notify({
            type: 'positive',
            message: successMessage,
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
.payment-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.detail-loading {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.form-content {
  max-width: 800px;
  margin: 0 auto;
}

.summary-card {
  border-radius: var(--radius-md);
  border: 1px solid var(--border-light);
  background: var(--bg-secondary);
}
</style>
