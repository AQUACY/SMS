<template>
  <q-page class="payments-list-page">
    <MobilePageHeader
      title="Fee Payments"
      subtitle="Manage school fee payments"
    >
      <template v-slot:actions>
        <q-btn
          color="primary"
          :label="$q.screen.gt.xs ? 'Record Payment' : ''"
          icon="add"
          unelevated
          @click="showCreateDialog = true"
          class="mobile-btn"
        />
      </template>
    </MobilePageHeader>

    <!-- Filters -->
    <MobileCard variant="default" padding="md" class="filters-card">
      <div class="filters-grid">
        <q-select
          v-model="filters.status"
          :options="statusOptions"
          option-label="label"
          option-value="value"
          emit-value
          map-options
          label="Status"
          outlined
          clearable
          dense
          class="filter-item"
        />
        <q-select
          v-model="filters.student_id"
          :options="students"
          option-label="full_name"
          option-value="id"
          emit-value
          map-options
          label="Student"
          outlined
          clearable
          dense
          :loading="loadingStudents"
          use-input
          input-debounce="300"
          @filter="filterStudents"
          class="filter-item"
        >
          <template v-slot:no-option>
            <q-item>
              <q-item-section class="text-grey">
                No students found
              </q-item-section>
            </q-item>
          </template>
        </q-select>
        <q-select
          v-model="filters.term_id"
          :options="terms"
          option-label="name"
          option-value="id"
          emit-value
          map-options
          label="Term"
          outlined
          clearable
          dense
          :loading="loadingTerms"
          class="filter-item"
        />
        <q-input
          v-model="filters.search"
          label="Search"
          outlined
          dense
          clearable
          placeholder="Reference, parent name..."
          class="filter-item"
        >
          <template v-slot:prepend>
            <q-icon name="search" />
          </template>
        </q-input>
      </div>
    </MobileCard>

    <!-- Mobile Card View -->
    <div class="mobile-list-view">
      <div v-if="loading" class="loading-cards">
        <q-card v-for="i in 3" :key="i" class="mobile-list-card">
          <q-card-section>
            <q-skeleton type="rect" height="100px" />
          </q-card-section>
        </q-card>
      </div>
      
      <div v-else-if="payments.length > 0" class="cards-container">
        <MobileListCard
          v-for="payment in payments"
          :key="payment.id"
          :title="payment.student?.full_name || 'N/A'"
          :subtitle="`Ref: ${payment.reference || 'N/A'}`"
          :description="getPaymentDescription(payment)"
          icon="payment"
          :badge="payment.status"
          :badge-color="getStatusColor(payment.status)"
          icon-bg="rgba(76, 175, 80, 0.1)"
          @click="viewPayment(payment)"
        >
          <template v-slot:extra>
            <div class="payment-amount">
              <div class="amount-label">Amount</div>
              <div class="amount-value">
                {{ payment.currency || 'GHS' }} {{ parseFloat(payment.amount || 0).toFixed(2) }}
              </div>
            </div>
            <div class="card-actions">
              <q-btn
                flat
                dense
                icon="visibility"
                color="primary"
                label="View"
                @click.stop="viewPayment(payment)"
                size="sm"
              />
              <q-btn
                v-if="payment.status === 'pending'"
                flat
                dense
                icon="check_circle"
                color="positive"
                label="Verify"
                @click.stop="verifyPayment(payment)"
                size="sm"
              />
            </div>
          </template>
        </MobileListCard>
      </div>
      
      <div v-else class="empty-state">
        <q-icon name="payment" size="64px" color="grey-5" />
        <div class="empty-text">No payments found</div>
      </div>
    </div>

    <!-- Desktop Table View -->
    <div class="desktop-table-view">
      <q-card class="widget-card">
        <q-table
          :rows="payments"
          :columns="columns"
          :loading="loading"
          :pagination="pagination"
          @request="onRequest"
          row-key="id"
          flat
          class="payments-table"
        >
        <template v-slot:body-cell-status="props">
          <q-td :props="props">
            <q-badge
              :color="getStatusColor(props.value)"
              :label="props.value"
              class="text-capitalize"
            />
          </q-td>
        </template>

        <template v-slot:body-cell-student="props">
          <q-td :props="props">
            <div class="text-weight-medium">{{ props.row.student?.full_name }}</div>
            <div class="text-caption text-grey-6">{{ props.row.student?.student_number }}</div>
          </q-td>
        </template>

        <template v-slot:body-cell-parent="props">
          <q-td :props="props">
            <div>{{ props.row.parent?.user?.full_name || 'N/A' }}</div>
            <div class="text-caption text-grey-6">{{ props.row.parent?.user?.email || '' }}</div>
          </q-td>
        </template>

        <template v-slot:body-cell-amount="props">
          <q-td :props="props">
            <div class="text-weight-bold text-primary">
              {{ props.row.currency || 'GHS' }} {{ parseFloat(props.value || 0).toFixed(2) }}
            </div>
          </q-td>
        </template>

        <template v-slot:body-cell-actions="props">
          <q-td :props="props">
            <q-btn
              flat
              round
              dense
              icon="visibility"
              color="primary"
              @click="viewPayment(props.row)"
              class="q-mr-xs"
            >
              <q-tooltip>View Details</q-tooltip>
            </q-btn>
            <q-btn
              v-if="props.row.status === 'pending'"
              flat
              round
              dense
              icon="check_circle"
              color="positive"
              @click="verifyPayment(props.row)"
              class="q-mr-xs"
            >
              <q-tooltip>Verify Payment</q-tooltip>
            </q-btn>
            <q-btn
              v-if="props.row.status === 'pending'"
              flat
              round
              dense
              icon="edit"
              color="warning"
              @click="editPayment(props.row)"
              class="q-mr-xs"
            >
              <q-tooltip>Edit</q-tooltip>
            </q-btn>
          </q-td>
        </template>
      </q-table>
      </q-card>
    </div>

    <!-- Create Payment Dialog -->
    <q-dialog v-model="showCreateDialog" persistent>
      <q-card style="min-width: 500px; max-width: 600px">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Record Fee Payment</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <q-form @submit="createPayment" class="q-gutter-md">
            <q-select
              v-model="paymentForm.student_id"
              :options="students"
              option-label="full_name"
              option-value="id"
              emit-value
              map-options
              label="Student *"
              outlined
              :rules="[(val) => !!val || 'Student is required']"
              :loading="loadingStudents"
              use-input
              input-debounce="300"
              @filter="filterStudents"
              @update:model-value="onStudentSelected"
            >
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section>
                    <q-item-label>{{ scope.opt.full_name }}</q-item-label>
                    <q-item-label caption>{{ scope.opt.student_number }}</q-item-label>
                  </q-item-section>
                </q-item>
              </template>
            </q-select>

            <q-select
              v-model="paymentForm.parent_id"
              :options="availableParents"
              option-label="name"
              option-value="id"
              emit-value
              map-options
              label="Parent *"
              outlined
              :rules="[(val) => !!val || 'Parent is required']"
              :disable="!paymentForm.student_id"
              hint="Select the parent who made the payment"
            />

            <q-select
              v-model="paymentForm.term_id"
              :options="terms"
              option-label="name"
              option-value="id"
              emit-value
              map-options
              label="Term *"
              outlined
              :rules="[(val) => !!val || 'Term is required']"
              :loading="loadingTerms"
              @update:model-value="onTermSelected"
            />

            <q-input
              v-model.number="paymentForm.amount"
              label="Amount (GHS) *"
              outlined
              type="number"
              step="0.01"
              min="0"
              :rules="[
                (val) => !!val || 'Amount is required',
                (val) => val > 0 || 'Amount must be greater than 0',
              ]"
            />

            <q-select
              v-model="paymentForm.payment_method"
              :options="paymentMethodOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              label="Payment Method *"
              outlined
              :rules="[(val) => !!val || 'Payment method is required']"
            />

            <q-input
              v-model="paymentForm.payment_reference"
              label="Payment Reference"
              outlined
              hint="Transaction ID, receipt number, etc."
            />

            <div class="row q-mt-md">
              <q-space />
              <q-btn
                flat
                label="Cancel"
                color="grey"
                v-close-popup
                class="q-mr-sm"
              />
              <q-btn
                type="submit"
                label="Record Payment"
                color="primary"
                unelevated
                :loading="submitting"
              />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Verify Payment Dialog -->
    <q-dialog v-model="showVerifyDialog" persistent>
      <q-card style="min-width: 400px">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Verify Payment</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <div class="q-mb-md">
            <div class="text-body2 text-grey-7">Student:</div>
            <div class="text-body1 text-weight-medium">{{ selectedPayment?.student?.full_name }}</div>
          </div>
          <div class="q-mb-md">
            <div class="text-body2 text-grey-7">Amount:</div>
            <div class="text-body1 text-weight-medium text-primary">
              {{ selectedPayment?.currency || 'GHS' }} {{ parseFloat(selectedPayment?.amount || 0).toFixed(2) }}
            </div>
          </div>
          <div class="q-mb-md">
            <div class="text-body2 text-grey-7">Reference:</div>
            <div class="text-body1">{{ selectedPayment?.reference }}</div>
          </div>
          <div class="text-body2 text-grey-7 q-mt-md">
            Confirm that this payment has been received and verified?
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" color="grey" v-close-popup />
          <q-btn
            label="Verify Payment"
            color="positive"
            unelevated
            @click="confirmVerify"
            :loading="verifying"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useQuasar } from 'quasar';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import MobileListCard from 'src/components/mobile/MobileListCard.vue';
import api from 'src/services/api';

const $q = useQuasar();

const loading = ref(false);
const loadingStudents = ref(false);
const loadingTerms = ref(false);
const submitting = ref(false);
const verifying = ref(false);
const payments = ref([]);
const students = ref([]);
const allStudents = ref([]);
const terms = ref([]);
const showCreateDialog = ref(false);
const showVerifyDialog = ref(false);
const selectedPayment = ref(null);

const filters = ref({
  status: null,
  student_id: null,
  term_id: null,
  search: '',
});

const paymentForm = ref({
  student_id: null,
  parent_id: null,
  term_id: null,
  amount: null,
  payment_method: 'cash',
  payment_reference: '',
});

const pagination = ref({
  page: 1,
  rowsPerPage: 15,
  rowsNumber: 0,
});

const statusOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'Processing', value: 'processing' },
  { label: 'Completed', value: 'completed' },
  { label: 'Failed', value: 'failed' },
  { label: 'Cancelled', value: 'cancelled' },
];

const paymentMethodOptions = [
  { label: 'Cash', value: 'cash' },
  { label: 'Mobile Money', value: 'momo' },
  { label: 'Bank Transfer', value: 'bank' },
  { label: 'Other', value: 'other' },
];

const columns = [
  {
    name: 'reference',
    label: 'Reference',
    field: 'reference',
    align: 'left',
    sortable: true,
  },
  {
    name: 'student',
    label: 'Student',
    field: (row) => row.student?.full_name,
    align: 'left',
    sortable: true,
  },
  {
    name: 'parent',
    label: 'Parent',
    field: (row) => row.parent?.user?.full_name,
    align: 'left',
  },
  {
    name: 'term',
    label: 'Term',
    field: (row) => row.term?.name,
    align: 'left',
  },
  {
    name: 'amount',
    label: 'Amount',
    field: 'amount',
    align: 'right',
    sortable: true,
  },
  {
    name: 'payment_method',
    label: 'Method',
    field: 'payment_method',
    align: 'center',
  },
  {
    name: 'status',
    label: 'Status',
    field: 'status',
    align: 'center',
    sortable: true,
  },
  {
    name: 'created_at',
    label: 'Date',
    field: 'created_at',
    align: 'left',
    format: (val) => new Date(val).toLocaleDateString(),
    sortable: true,
  },
  {
    name: 'actions',
    label: 'Actions',
    align: 'center',
  },
];

const availableParents = computed(() => {
  if (!paymentForm.value.student_id) return [];
  
  const student = students.value.find(s => s.id === paymentForm.value.student_id);
  if (!student || !student.parents) return [];
  
  return student.parents.map(parent => ({
    id: parent.id,
    name: parent.user?.full_name || 'Unknown',
    email: parent.user?.email || '',
  }));
});

onMounted(() => {
  fetchPayments();
  fetchStudents();
  fetchTerms();
});

function filterStudents(val, update) {
  if (val === '') {
    update(() => {
      students.value = allStudents.value;
    });
    return;
  }
  
  update(() => {
    const needle = val.toLowerCase();
    students.value = allStudents.value.filter(
      s => s.full_name?.toLowerCase().includes(needle) ||
           s.student_number?.toLowerCase().includes(needle)
    );
  });
}

async function fetchStudents() {
  loadingStudents.value = true;
  try {
    const response = await api.get('/students', { params: { per_page: 1000 } });
    if (response.data.success) {
      allStudents.value = response.data.data.map(s => ({
        ...s,
        full_name: `${s.first_name} ${s.middle_name || ''} ${s.last_name}`.trim(),
      }));
      students.value = allStudents.value;
    }
  } catch (error) {
    console.error('Failed to fetch students:', error);
  } finally {
    loadingStudents.value = false;
  }
}

async function fetchTerms() {
  loadingTerms.value = true;
  try {
    const response = await api.get('/terms', { params: { per_page: 100 } });
    if (response.data.success) {
      terms.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch terms:', error);
  } finally {
    loadingTerms.value = false;
  }
}

async function fetchPayments() {
  loading.value = true;
  try {
    const params = {
      payment_type: 'fee_payment',
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
    };
    
    if (filters.value.status) params.status = filters.value.status;
    if (filters.value.student_id) params.student_id = filters.value.student_id;
    if (filters.value.term_id) params.term_id = filters.value.term_id;
    if (filters.value.search) params.search = filters.value.search;
    
    const response = await api.get('/payments', { params });
    if (response.data.success) {
      payments.value = response.data.data || [];
      pagination.value.rowsNumber = response.data.meta?.total || 0;
    }
  } catch (error) {
    console.error('Failed to fetch payments:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch payments',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
}

function onRequest(props) {
  pagination.value = props.pagination;
  fetchPayments();
}

function getStatusColor(status) {
  const colors = {
    pending: 'orange',
    processing: 'blue',
    completed: 'green',
    failed: 'red',
    cancelled: 'grey',
  };
  return colors[status] || 'grey';
}

function getPaymentDescription(payment) {
  const parts = [];
  if (payment.term?.name) parts.push(`Term: ${payment.term.name}`);
  if (payment.parent?.user?.full_name) parts.push(`Parent: ${payment.parent.user.full_name}`);
  if (payment.payment_method) {
    const method = payment.payment_method.charAt(0).toUpperCase() + payment.payment_method.slice(1);
    parts.push(`Method: ${method}`);
  }
  return parts.join(' • ') || 'No additional details';
}

async function onStudentSelected() {
  paymentForm.value.parent_id = null;
  // Fetch student with parents
  const student = allStudents.value.find(s => s.id === paymentForm.value.student_id);
  if (student) {
    try {
      // Always fetch fresh student data with parents
      const response = await api.get(`/students/${student.id}`);
      if (response.data.success) {
        const updatedStudent = response.data.data;
        const index = allStudents.value.findIndex(s => s.id === student.id);
        if (index !== -1) {
          allStudents.value[index] = {
            ...updatedStudent,
            full_name: `${updatedStudent.first_name} ${updatedStudent.middle_name || ''} ${updatedStudent.last_name}`.trim(),
          };
        }
        // Update the students list for the select
        const selectIndex = students.value.findIndex(s => s.id === student.id);
        if (selectIndex !== -1) {
          students.value[selectIndex] = allStudents.value[index];
        }
      }
    } catch (error) {
      console.error('Failed to fetch student details:', error);
    }
  }
}

async function onTermSelected() {
  if (paymentForm.value.student_id && paymentForm.value.term_id) {
    try {
      const response = await api.get(`/fees/term/${paymentForm.value.term_id}`, {
        params: { student_id: paymentForm.value.student_id },
      });
      if (response.data.success && response.data.data?.fee?.amount) {
        paymentForm.value.amount = parseFloat(response.data.data.fee.amount);
      }
    } catch (error) {
      // Fee might not be set, that's okay
    }
  }
}

async function createPayment() {
  submitting.value = true;
  try {
    const response = await api.post('/payments/initiate-fee', paymentForm.value);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Payment recorded successfully',
        position: 'top',
      });
      showCreateDialog.value = false;
      resetForm();
      fetchPayments();
    }
  } catch (error) {
    console.error('Failed to create payment:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to record payment',
      position: 'top',
    });
  } finally {
    submitting.value = false;
  }
}

function resetForm() {
  paymentForm.value = {
    student_id: null,
    parent_id: null,
    term_id: null,
    amount: null,
    payment_method: 'cash',
    payment_reference: '',
  };
}

function viewPayment(payment) {
  // Navigate to payment detail page
  window.location.href = `/app/payments/${payment.id}`;
}

function verifyPayment(payment) {
  selectedPayment.value = payment;
  showVerifyDialog.value = true;
}

async function confirmVerify() {
  verifying.value = true;
  try {
    const response = await api.post(`/payments/${selectedPayment.value.id}/verify`, {
      verified_by: 'admin',
    });
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Payment verified successfully',
        position: 'top',
      });
      showVerifyDialog.value = false;
      fetchPayments();
    }
  } catch (error) {
    console.error('Failed to verify payment:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to verify payment',
      position: 'top',
    });
  } finally {
    verifying.value = false;
  }
}

function editPayment(payment) {
  // TODO: Implement edit functionality
  $q.notify({
    type: 'info',
    message: 'Edit functionality coming soon',
    position: 'top',
  });
}

// Watch filters and refetch
watch([() => filters.value.status, () => filters.value.student_id, () => filters.value.term_id, () => filters.value.search], () => {
  pagination.value.page = 1;
  fetchPayments();
});
</script>

<style lang="scss" scoped>
.payments-list-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.filters-card {
  margin-bottom: var(--spacing-md);
}

.filters-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-md);
  
  @media (min-width: 600px) {
    grid-template-columns: repeat(2, 1fr);
  }
  
  @media (min-width: 960px) {
    grid-template-columns: repeat(4, 1fr);
  }
}

.filter-item {
  width: 100%;
}

.mobile-list-view {
  display: block;
  
  @media (min-width: 960px) {
    display: none;
  }
}

.desktop-table-view {
  display: none;
  
  @media (min-width: 960px) {
    display: block;
  }
}

.loading-cards {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.cards-container {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.payment-amount {
  margin-bottom: var(--spacing-sm);
  
  .amount-label {
    font-size: var(--font-size-xs);
    color: var(--text-secondary);
    margin-bottom: var(--spacing-xs);
  }
  
  .amount-value {
    font-size: var(--font-size-lg);
    font-weight: 700;
    color: var(--primary-color);
  }
}

.card-actions {
  display: flex;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
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

.mobile-btn {
  @media (max-width: 599px) {
    min-width: 0;
    padding: var(--spacing-sm);
  }
}

.widget-card {
  border-radius: var(--radius-xl);
  border: 1px solid var(--border-light);
  backdrop-filter: blur(10px);
  background: var(--bg-card);
  box-shadow: var(--shadow-sm);
  
  @media (min-width: 768px) {
    box-shadow: var(--shadow-md);
  }
}

.payments-table {
  :deep(.q-table__top) {
    padding: 0;
  }
}
</style>
