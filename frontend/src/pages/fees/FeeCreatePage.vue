<template>
  <q-page class="q-pa-lg">
    <div class="row items-center q-mb-lg">
      <q-btn
        flat
        round
        icon="arrow_back"
        @click="$router.push('/app/fees')"
        class="q-mr-md"
      />
      <div>
        <div class="text-h5 text-weight-bold">{{ isEdit ? 'Edit Fee' : 'Create Fee' }}</div>
        <div class="text-body2 text-grey-7">{{ isEdit ? 'Update fee details' : 'Set fee for term' }}</div>
      </div>
    </div>

    <q-card class="widget-card q-pr-lg">
      <q-card-section>
        <q-form @submit="submitForm" class="q-gutter-md">
          <div class="row q-gutter-md">
            <div class="col-12 col-md-6">
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
                :disable="isEdit"
              >
                <template v-slot:option="scope">
                  <q-item v-bind="scope.itemProps">
                    <q-item-section>
                      <q-item-label>{{ scope.opt.name }}</q-item-label>
                      <q-item-label caption>{{ scope.opt.academic_year?.name || '' }}</q-item-label>
                    </q-item-section>
                  </q-item>
                </template>
              </q-select>
            </div>

            <div class="col-12 col-md-6">
              <q-select
                v-model="form.fee_type"
                :options="feeTypeOptions"
                option-label="label"
                option-value="value"
                emit-value
                map-options
                label="Fee Type *"
                outlined
                :rules="[(val) => !!val || 'Fee type is required']"
                :disable="isEdit"
              />
            </div>
          </div>

          <!-- Class Selection (for class-specific fees) -->
          <div v-if="form.fee_type === 'class'" class="row q-gutter-md">
            <div class="col-12">
              <q-select
                v-model="form.class_id"
                :options="classes"
                option-label="name"
                option-value="id"
                emit-value
                map-options
                label="Class *"
                outlined
                :rules="[(val) => !!val || 'Class is required']"
                :loading="loadingClasses"
                :disable="isEdit"
              >
                <template v-slot:option="scope">
                  <q-item v-bind="scope.itemProps">
                    <q-item-section>
                      <q-item-label>{{ scope.opt.name }}</q-item-label>
                      <q-item-label caption>{{ scope.opt.level || '' }}</q-item-label>
                    </q-item-section>
                  </q-item>
                </template>
              </q-select>
            </div>
          </div>

          <!-- Level Selection (for level-specific fees) -->
          <div v-if="form.fee_type === 'level'" class="row q-gutter-md">
            <div class="col-12">
              <q-select
                v-model="form.level_category"
                :options="levelOptions"
                option-label="label"
                option-value="value"
                emit-value
                map-options
                label="Level *"
                outlined
                :rules="[(val) => !!val || 'Level is required']"
                :disable="isEdit"
              />
            </div>
          </div>

          <div class="row q-gutter-md">
            <div class="col-12">
              <q-input
                v-model="form.name"
                label="Fee Name *"
                outlined
                :rules="[(val) => !!val || 'Fee name is required']"
                hint="e.g., Term 1 Subscription Fee"
              />
            </div>
          </div>

          <div class="row q-gutter-md">
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

          <div class="row q-gutter-md">
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

          <div class="row q-gutter-md">
            <div class="col-12 col-md-6">
              <q-input
                v-model="form.due_date"
                label="Due Date"
                outlined
                type="date"
              />
            </div>

            <div class="col-12 col-md-6">
              <q-toggle
                v-model="form.is_active"
                label="Active"
              />
            </div>
          </div>

          <div class="row q-mt-lg">
            <div class="col-12">
              <q-btn
                type="submit"
                color="primary"
                label="Save Fee"
                icon="save"
                unelevated
                :loading="submitting"
              />
              <q-btn
                flat
                label="Cancel"
                @click="$router.push('/app/fees')"
                class="q-ml-sm"
              />
            </div>
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import api from 'src/services/api';

const route = useRoute();
const router = useRouter();
const $q = useQuasar();

const isEdit = computed(() => !!route.params.id);
const loading = ref(false);
const loadingClasses = ref(false);
const submitting = ref(false);
const terms = ref([]);
const classes = ref([]);

const form = ref({
  term_id: null,
  fee_type: 'school',
  class_id: null,
  level_category: null,
  name: '',
  description: '',
  amount: null,
  currency: 'GHS',
  is_active: true,
  due_date: null,
});

const feeTypeOptions = [
  { label: 'School Wide', value: 'school' },
  { label: 'Level Specific', value: 'level' },
  { label: 'Class Specific', value: 'class' },
];

const levelOptions = [
  { label: 'Nursery', value: 'nursery' },
  { label: 'Creche', value: 'creche' },
  { label: 'Primary', value: 'primary' },
  { label: 'JHS', value: 'jhs' },
  { label: 'SHS', value: 'shs' },
];

onMounted(() => {
  fetchTerms();
  // Don't fetch classes on mount - wait for fee_type selection
  if (isEdit.value) {
    fetchFee();
  }
});

watch(() => form.value.fee_type, (newType, oldType) => {
  console.log('Fee type changed:', { newType, oldType });
  
  // Fetch classes when switching to class type
  if (newType === 'class') {
    if (!classes.value || classes.value.length === 0) {
      fetchClasses();
    }
  } else {
    form.value.class_id = null;
  }
  
  // Clear level category when not level type
  if (newType !== 'level') {
    form.value.level_category = null;
  }
}, { immediate: false });

async function fetchTerms() {
  try {
    const response = await api.get('/terms', { params: { per_page: 100 } });
    if (response.data.success) {
      terms.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch terms:', error);
  }
}

async function fetchClasses() {
  loadingClasses.value = true;
  try {
    const response = await api.get('/classes', { params: { per_page: 100 } });
    if (response.data.success) {
      classes.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch classes:', error);
  } finally {
    loadingClasses.value = false;
  }
}

async function fetchFee() {
  loading.value = true;
  try {
    const response = await api.get(`/fees/${route.params.id}`);
    if (response.data.success) {
      const fee = response.data.data;
      form.value = {
        term_id: fee.term_id,
        fee_type: fee.class_id ? 'class' : (fee.level_category ? 'level' : 'school'),
        class_id: fee.class_id,
        level_category: fee.level_category,
        name: fee.name,
        description: fee.description || '',
        amount: parseFloat(fee.amount),
        currency: fee.currency || 'GHS',
        is_active: fee.is_active,
        due_date: fee.due_date || null,
      };
      
      if (form.value.fee_type === 'class') {
        fetchClasses();
      }
    }
  } catch (error) {
    console.error('Failed to fetch fee:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch fee',
      position: 'top',
    });
    router.push('/app/fees');
  } finally {
    loading.value = false;
  }
}

async function submitForm() {
  submitting.value = true;
  try {
    const feeData = {
      term_id: form.value.term_id,
      fee_type: form.value.fee_type,
      name: form.value.name,
      description: form.value.description,
      amount: parseFloat(form.value.amount),
      currency: form.value.currency || 'GHS',
      is_active: form.value.is_active,
      due_date: form.value.due_date || null,
    };

    if (form.value.fee_type === 'class') {
      feeData.class_id = form.value.class_id;
    } else if (form.value.fee_type === 'level') {
      feeData.level_category = form.value.level_category;
    }

    let response;
    if (isEdit.value) {
      response = await api.put(`/fees/${route.params.id}`, feeData);
    } else {
      response = await api.post('/fees', feeData);
    }

    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: isEdit.value ? 'Fee updated successfully' : 'Fee created successfully',
        position: 'top',
      });
      router.push('/app/fees');
    }
  } catch (error) {
    console.error('Failed to save fee:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to save fee',
      position: 'top',
    });
  } finally {
    submitting.value = false;
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

