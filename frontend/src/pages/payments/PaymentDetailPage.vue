<template>
  <q-page class="payment-detail-page">
    <MobilePageHeader
      title="Payment Details"
      :subtitle="payment ? `Reference: ${payment.reference}` : 'View payment information'"
      :show-back="true"
      @back="$router.go(-1)"
    >
      <template #actions>
        <q-btn
          v-if="payment && payment.status === 'completed'"
          flat
          round
          dense
          icon="download"
          color="primary"
          @click="downloadReceipt"
          :loading="downloadingReceipt"
          class="q-mr-xs"
        >
          <q-tooltip>Download Receipt</q-tooltip>
        </q-btn>
        <q-btn
          v-if="payment && payment.status === 'completed'"
          flat
          round
          dense
          icon="email"
          color="primary"
          @click="emailReceipt"
          :loading="sendingEmail"
        >
          <q-tooltip>Email Receipt</q-tooltip>
        </q-btn>
      </template>
    </MobilePageHeader>

    <div v-if="loading" class="detail-loading">
      <div class="loading-center">
        <q-spinner color="primary" size="3em" />
      </div>
    </div>

    <div v-else-if="payment" class="detail-content">
      <!-- Payment Information -->
      <MobileCard variant="default" padding="md" class="q-mb-md">
        <div class="card-title">Payment Information</div>
        <div class="info-grid">
          <div class="info-item">
            <div class="info-label">Amount</div>
            <div class="info-value amount-value">
              {{ payment.currency || 'GHS' }} {{ formatCurrency(payment.amount) }}
            </div>
          </div>
          <div class="info-item">
            <div class="info-label">Status</div>
            <div class="info-value">
              <q-badge :color="getStatusColor(payment.status)" :label="payment.status.toUpperCase()" />
            </div>
          </div>
          <div class="info-item">
            <div class="info-label">Reference</div>
            <div class="info-value">{{ payment.reference || 'N/A' }}</div>
          </div>
          <div class="info-item">
            <div class="info-label">Payment Method</div>
            <div class="info-value">{{ formatPaymentMethod(payment) }}</div>
          </div>
          <div class="info-item" v-if="payment.payment_reference">
            <div class="info-label">Payment Reference</div>
            <div class="info-value">{{ payment.payment_reference }}</div>
          </div>
          <div class="info-item" v-if="payment.verified_at">
            <div class="info-label">Verified At</div>
            <div class="info-value">{{ formatDate(payment.verified_at) }}</div>
          </div>
          <div class="info-item" v-if="payment.verified_by">
            <div class="info-label">Verified By</div>
            <div class="info-value">{{ formatVerifiedBy(payment.verified_by) }}</div>
          </div>
        </div>
      </MobileCard>

      <!-- Student Information -->
      <MobileCard variant="default" padding="md" class="q-mb-md">
        <div class="card-title">Student Information</div>
        <div class="info-grid">
          <div class="info-item">
            <div class="info-label">Name</div>
            <div class="info-value">{{ payment.student?.full_name || (payment.student?.first_name + ' ' + payment.student?.last_name) || 'N/A' }}</div>
          </div>
          <div class="info-item" v-if="payment.student?.student_number">
            <div class="info-label">Student Number</div>
            <div class="info-value">{{ payment.student.student_number }}</div>
          </div>
          <div class="info-item" v-if="payment.student?.current_class">
            <div class="info-label">Class</div>
            <div class="info-value">{{ payment.student.current_class?.name || 'N/A' }}</div>
          </div>
        </div>
      </MobileCard>

      <!-- Term Information -->
      <MobileCard variant="default" padding="md" class="q-mb-md">
        <div class="card-title">Term Information</div>
        <div class="info-grid">
          <div class="info-item">
            <div class="info-label">Term</div>
            <div class="info-value">{{ payment.term?.name || 'N/A' }}</div>
          </div>
          <div class="info-item" v-if="payment.term?.academic_year">
            <div class="info-label">Academic Year</div>
            <div class="info-value">{{ payment.term.academic_year.name || 'N/A' }}</div>
          </div>
        </div>
      </MobileCard>

      <!-- Receipt Actions (Only for completed payments) -->
      <MobileCard v-if="payment.status === 'completed'" variant="default" padding="md">
        <div class="card-title">Receipt</div>
        <div class="action-buttons">
          <q-btn
            color="primary"
            icon="download"
            label="Download Receipt (PDF)"
            unelevated
            @click="downloadReceipt"
            :loading="downloadingReceipt"
            class="full-width q-mb-md"
            size="lg"
          />
          <q-btn
            color="secondary"
            icon="email"
            label="Email Receipt"
            unelevated
            @click="emailReceipt"
            :loading="sendingEmail"
            class="full-width"
            size="lg"
          />
        </div>
      </MobileCard>
    </div>

    <div v-else class="detail-content">
      <MobileCard variant="default" padding="lg">
        <div class="empty-state">
          <q-icon name="error" size="64px" color="grey-5" />
          <div class="empty-text">Payment Not Found</div>
          <div class="empty-subtext">
            The payment you're looking for doesn't exist or you don't have permission to view it.
          </div>
        </div>
      </MobileCard>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import api from 'src/services/api';

const route = useRoute();
const router = useRouter();
const $q = useQuasar();

const loading = ref(false);
const payment = ref(null);
const downloadingReceipt = ref(false);
const sendingEmail = ref(false);

onMounted(() => {
  fetchPayment();
});

async function fetchPayment() {
  loading.value = true;
  try {
    const response = await api.get(`/payments/${route.params.id}`);
    if (response.data.success) {
      payment.value = response.data.data;
    } else {
      payment.value = null;
    }
  } catch (error) {
    console.error('Failed to fetch payment:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch payment details',
      position: 'top',
    });
    payment.value = null;
  } finally {
    loading.value = false;
  }
}

async function downloadReceipt() {
  if (!payment.value || payment.value.status !== 'completed') {
    return;
  }

  downloadingReceipt.value = true;
  try {
    const response = await api.get(`/payments/${payment.value.id}/receipt`, {
      responseType: 'blob',
      params: { action: 'download' },
    });

    // Create blob URL and download
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `receipt_${payment.value.reference}.pdf`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);

    $q.notify({
      type: 'positive',
      message: 'Receipt downloaded successfully',
      position: 'top',
    });
  } catch (error) {
    console.error('Failed to download receipt:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to download receipt',
      position: 'top',
    });
  } finally {
    downloadingReceipt.value = false;
  }
}

async function emailReceipt() {
  if (!payment.value || payment.value.status !== 'completed') {
    return;
  }

  sendingEmail.value = true;
  try {
    const response = await api.post(`/payments/${payment.value.id}/receipt/email`);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Receipt email sent successfully',
        position: 'top',
      });
    }
  } catch (error) {
    console.error('Failed to send receipt email:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to send receipt email',
      position: 'top',
    });
  } finally {
    sendingEmail.value = false;
  }
}

function formatCurrency(amount) {
  if (!amount) return '0.00';
  return parseFloat(amount).toFixed(2);
}

function formatPaymentMethod(payment) {
  if (payment.payment_method === 'momo') {
    const provider = payment.momo_provider?.toUpperCase() || '';
    return `Mobile Money${provider ? ` (${provider})` : ''}`;
  }
  return payment.payment_method ? payment.payment_method.charAt(0).toUpperCase() + payment.payment_method.slice(1) : 'N/A';
}

function formatDate(date) {
  if (!date) return 'N/A';
  return new Date(date).toLocaleString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function formatVerifiedBy(verifiedBy) {
  if (!verifiedBy) return 'N/A';
  return verifiedBy.replace(/_/g, ' ').split(' ').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ');
}

function getStatusColor(status) {
  const colors = {
    pending: 'warning',
    processing: 'info',
    completed: 'positive',
    failed: 'negative',
    cancelled: 'grey',
  };
  return colors[status] || 'grey';
}
</script>

<style lang="scss" scoped>
.payment-detail-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.detail-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
}

.loading-center {
  display: flex;
  align-items: center;
  justify-content: center;
}

.detail-content {
  max-width: 1200px;
  margin: 0 auto;
}

.card-title {
  font-size: var(--font-size-lg);
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: var(--spacing-md);
}

.info-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-md);
  
  @media (min-width: 768px) {
    grid-template-columns: repeat(2, 1fr);
  }
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.info-label {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  font-weight: 500;
}

.info-value {
  font-size: var(--font-size-base);
  color: var(--text-primary);
  font-weight: 600;
}

.amount-value {
  font-size: var(--font-size-xl);
  color: var(--q-primary);
  font-weight: 700;
}

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
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
  font-size: var(--font-size-lg);
  font-weight: 600;
  color: var(--text-primary);
  margin-top: var(--spacing-md);
}

.empty-subtext {
  font-size: var(--font-size-base);
  color: var(--text-secondary);
  margin-top: var(--spacing-sm);
}
</style>

