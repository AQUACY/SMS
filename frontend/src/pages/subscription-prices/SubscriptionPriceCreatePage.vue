<template>
  <q-page class="form-page">
    <MobilePageHeader
      :title="isEdit ? 'Edit Subscription Price' : 'Create Subscription Price'"
      :subtitle="isEdit ? 'Update subscription price' : 'Set subscription pricing'"
      :show-back="true"
      @back="$router.push('/app/subscription-prices')"
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
        <q-form @submit="submitForm" class="form">
          <div class="row q-gutter-md">
            <div class="col-12 col-md-6">
              <q-select
                v-model="form.price_type"
                :options="priceTypeOptions"
                option-label="label"
                option-value="value"
                emit-value
                map-options
                label="Price Type *"
                outlined
                :rules="[(val) => !!val || 'Price type is required']"
                :disable="isEdit"
              />
            </div>
          </div>

          <!-- School Selection (for school-specific prices) -->
          <div v-if="form.price_type === 'school'" class="row q-gutter-md">
            <div class="col-12">
              <q-select
                v-model="form.school_id"
                :options="schools"
                option-label="name"
                option-value="id"
                emit-value
                map-options
                label="School *"
                outlined
                :rules="[(val) => !!val || 'School is required']"
                :loading="loadingSchools"
                :disable="isEdit"
              >
                <template v-slot:option="scope">
                  <q-item v-bind="scope.itemProps">
                    <q-item-section>
                      <q-item-label>{{ scope.opt.name }}</q-item-label>
                      <q-item-label caption>{{ scope.opt.code || '' }}</q-item-label>
                    </q-item-section>
                  </q-item>
                </template>
              </q-select>
            </div>
          </div>

          <div class="form-grid">
            <div class="col-12">
              <q-input
                v-model="form.name"
                label="Price Name *"
                outlined
                :rules="[(val) => !!val || 'Price name is required']"
                hint="e.g., Standard Subscription, Premium Subscription"
              />
            </div>
            <div class="col-12">
              <q-input
                v-model="form.description"
                label="Description"
                outlined
                type="textarea"
                rows="3"
              />
            </div>
          </div>

          <div class="form-grid">
            <div class="col-12 col-md-6">
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
              />
            </div>

            <div class="col-12 col-md-6">
              <q-input
                v-model="form.currency"
                label="Currency"
                outlined
                hint="Default: GHS"
              />
            </div>
          </div>

          <q-separator class="q-my-md" />
          <div class="form-section">
            <div class="section-title">Payment Information</div>
            <div class="section-description">
              Provide payment details so parents know where to send subscription payments
            </div>

          <div class="form-grid">
            <q-select
              v-model="form.payment_network"
              :options="networkOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              label="Payment Network"
              outlined
              hint="Select the mobile money network"
            >
              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section avatar>
                    <q-icon :name="scope.opt.icon" :color="scope.opt.color" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>{{ scope.opt.label }}</q-item-label>
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
            <q-input
              v-model="form.payment_number"
              label="Payment Number"
              outlined
              hint="Mobile money number (e.g., 0244123456)"
              mask="### ### ####"
              :rules="[
                (val) => !form.payment_network || !!val || 'Payment number is required when network is selected',
              ]"
            >
              <template v-slot:prepend>
                <q-icon name="phone" />
              </template>
            </q-input>
            <div class="col-12">
              <q-input
                v-model="form.payment_name"
                label="Payment Name"
                outlined
                hint="Name to expect when sending payment (e.g., School Name, Company Name)"
                :rules="[
                  (val) => !form.payment_network || !!val || 'Payment name is required when network is selected',
                ]"
              >
                <template v-slot:prepend>
                  <q-icon name="account_circle" />
                </template>
              </q-input>
            </div>
            <div class="col-12">
              <q-toggle
                v-model="form.is_active"
                label="Active"
              />
            </div>
          </div>
          </div>

          <div class="form-actions">
            <q-btn
              flat
              label="Cancel"
              @click="$router.push('/app/subscription-prices')"
              class="q-mr-sm"
            />
            <q-btn
              type="submit"
              color="primary"
              label="Save Price"
              icon="save"
              :loading="submitting"
            />
          </div>
        </q-form>
      </MobileCard>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import api from 'src/services/api';

const route = useRoute();
const router = useRouter();
const $q = useQuasar();

const isEdit = computed(() => !!route.params.id);
const loading = ref(false);
const loadingSchools = ref(false);
const submitting = ref(false);
const schools = ref([]);

const form = ref({
  price_type: 'global',
  school_id: null,
  name: '',
  description: '',
  amount: null,
  currency: 'GHS',
  payment_number: '',
  payment_network: null,
  payment_name: '',
  is_active: true,
});

const priceTypeOptions = [
  { label: 'Global (All Schools)', value: 'global' },
  { label: 'School Specific', value: 'school' },
];

const networkOptions = [
  { label: 'MTN Mobile Money', value: 'mtn', icon: 'phone_android', color: 'yellow-8' },
  { label: 'Vodafone Cash', value: 'vodafone', icon: 'phone_android', color: 'red' },
  { label: 'AirtelTigo Money', value: 'airteltigo', icon: 'phone_android', color: 'red-6' },
];

onMounted(() => {
  if (form.value.price_type === 'school') {
    fetchSchools();
  }
  if (isEdit.value) {
    fetchPrice();
  }
});

watch(() => form.value.price_type, (newType) => {
  if (newType === 'school') {
    fetchSchools();
  } else {
    form.value.school_id = null;
  }
});

async function fetchSchools() {
  loadingSchools.value = true;
  try {
    const response = await api.get('/schools', { params: { per_page: 100 } });
    if (response.data.success) {
      schools.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch schools:', error);
  } finally {
    loadingSchools.value = false;
  }
}

async function fetchPrice() {
  loading.value = true;
  try {
    const response = await api.get(`/subscription-prices/${route.params.id}`);
    if (response.data.success) {
      const price = response.data.data;
      form.value = {
        price_type: price.school_id ? 'school' : 'global',
        school_id: price.school_id,
        name: price.name,
        description: price.description || '',
        amount: parseFloat(price.amount),
        currency: price.currency || 'GHS',
        payment_number: price.payment_number || '',
        payment_network: price.payment_network || null,
        payment_name: price.payment_name || '',
        is_active: price.is_active,
      };
      
      if (form.value.price_type === 'school') {
        fetchSchools();
      }
    }
  } catch (error) {
    console.error('Failed to fetch subscription price:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch subscription price',
      position: 'top',
    });
    router.push('/app/subscription-prices');
  } finally {
    loading.value = false;
  }
}

async function submitForm() {
  submitting.value = true;
  try {
    const priceData = {
      price_type: form.value.price_type,
      name: form.value.name,
      description: form.value.description,
      amount: parseFloat(form.value.amount),
      currency: form.value.currency || 'GHS',
      payment_number: form.value.payment_number || null,
      payment_network: form.value.payment_network || null,
      payment_name: form.value.payment_name || null,
      is_active: form.value.is_active,
    };

    if (form.value.price_type === 'school') {
      priceData.school_id = form.value.school_id;
    }

    let response;
    if (isEdit.value) {
      response = await api.put(`/subscription-prices/${route.params.id}`, priceData);
    } else {
      response = await api.post('/subscription-prices', priceData);
    }

    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: isEdit.value ? 'Subscription price updated successfully' : 'Subscription price created successfully',
        position: 'top',
      });
      router.push('/app/subscription-prices');
    }
  } catch (error) {
    console.error('Failed to save subscription price:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to save subscription price',
      position: 'top',
    });
  } finally {
    submitting.value = false;
  }
}
</script>

<style lang="scss" scoped>
.form-page {
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
  max-width: 900px;
  margin: 0 auto;
}

.form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.form-section {
  margin-bottom: var(--spacing-lg);
}

.section-title {
  font-size: var(--font-size-base);
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: var(--spacing-sm);
}

.section-description {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  margin-bottom: var(--spacing-md);
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-md);
  
  @media (min-width: 600px) {
    grid-template-columns: repeat(2, 1fr);
  }
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-sm);
  margin-top: var(--spacing-lg);
  padding-top: var(--spacing-md);
  border-top: 1px solid var(--border-light);
}
</style>

