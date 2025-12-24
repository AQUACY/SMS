<template>
  <q-form @submit="onSubmit" class="q-gutter-md">
    <div class="row q-col-gutter-md">
      <div class="col-12 col-md-6">
        <q-input
          v-model="form.student_number"
          label="Student Number *"
          outlined
          :rules="[(val) => !!val || 'Student number is required']"
        />
      </div>
      <div class="col-12 col-md-6">
        <q-input
          v-model="form.date_of_birth"
          label="Date of Birth"
          outlined
          type="date"
        />
      </div>
      <div class="col-12 col-md-4">
        <q-input
          v-model="form.first_name"
          label="First Name *"
          outlined
          :rules="[(val) => !!val || 'First name is required']"
        />
      </div>
      <div class="col-12 col-md-4">
        <q-input
          v-model="form.middle_name"
          label="Middle Name"
          outlined
        />
      </div>
      <div class="col-12 col-md-4">
        <q-input
          v-model="form.last_name"
          label="Last Name *"
          outlined
          :rules="[(val) => !!val || 'Last name is required']"
        />
      </div>
      <div class="col-12 col-md-6">
        <q-select
          v-model="form.gender"
          :options="genderOptions"
          label="Gender"
          outlined
          emit-value
          map-options
        />
      </div>
      <div class="col-12 col-md-6">
        <q-input
          v-model="form.phone"
          label="Phone"
          outlined
        />
      </div>
      <div class="col-12 col-md-6">
        <q-input
          v-model="form.email"
          label="Email"
          outlined
          type="email"
        />
      </div>
      <div class="col-12 col-md-6">
        <q-toggle
          v-model="form.is_active"
          label="Active"
        />
      </div>
      <div class="col-12">
        <q-input
          v-model="form.address"
          label="Address"
          outlined
          type="textarea"
          rows="2"
        />
      </div>
    </div>

    <q-separator class="q-my-md" />

    <div class="text-subtitle2 q-mb-sm">Guardian Information (Optional)</div>
    <div class="row q-col-gutter-md">
      <div class="col-12 col-md-6">
        <q-input
          v-model="guardianForm.email"
          label="Guardian Email"
          outlined
          type="email"
          hint="If guardian exists, enter their email"
        />
      </div>
      <div class="col-12 col-md-6">
        <q-input
          v-model="guardianForm.phone"
          label="Guardian Phone"
          outlined
        />
      </div>
      <div class="col-12 col-md-6">
        <q-input
          v-model="guardianForm.first_name"
          label="Guardian First Name"
          outlined
        />
      </div>
      <div class="col-12 col-md-6">
        <q-input
          v-model="guardianForm.last_name"
          label="Guardian Last Name"
          outlined
        />
      </div>
      <div class="col-12 col-md-6">
        <q-select
          v-model="guardianForm.relationship"
          :options="relationshipOptions"
          label="Relationship"
          outlined
          emit-value
          map-options
        />
      </div>
      <div class="col-12 col-md-6">
        <q-toggle
          v-model="guardianForm.is_primary"
          label="Primary Guardian"
        />
      </div>
    </div>

    <q-card-actions align="right" class="q-pt-md">
      <q-btn flat label="Cancel" @click="$emit('cancel')" />
      <q-btn color="primary" label="Save Student" type="submit" :loading="saving" />
    </q-card-actions>
  </q-form>
</template>

<script setup>
import { ref } from 'vue';
import { useQuasar } from 'quasar';
import api from 'src/services/api';

const props = defineProps({
  classId: {
    type: [String, Number],
    default: null,
  },
});

const emit = defineEmits(['saved', 'cancel']);

const $q = useQuasar();

const saving = ref(false);

const genderOptions = [
  { label: 'Male', value: 'male' },
  { label: 'Female', value: 'female' },
  { label: 'Other', value: 'other' },
];

const relationshipOptions = [
  { label: 'Parent', value: 'parent' },
  { label: 'Guardian', value: 'guardian' },
  { label: 'Relative', value: 'relative' },
  { label: 'Other', value: 'other' },
];

const form = ref({
  student_number: '',
  first_name: '',
  middle_name: '',
  last_name: '',
  date_of_birth: '',
  gender: null,
  email: '',
  phone: '',
  address: '',
  is_active: true,
});

const guardianForm = ref({
  email: '',
  first_name: '',
  last_name: '',
  phone: '',
  relationship: 'parent',
  is_primary: false,
  password: 'password123', // Default password for new guardian accounts
});

const onSubmit = async () => {
  saving.value = true;
  try {
    const payload = {
      ...form.value,
    };

    // Add guardian if email is provided
    if (guardianForm.value.email) {
      payload.guardian = guardianForm.value;
    }

    let response;
    if (props.classId) {
      // Add student to class
      response = await api.post(`/classes/${props.classId}/students`, payload);
    } else {
      // Regular student creation
      response = await api.post('/students', payload);
    }

    if (response.data.success) {
      emit('saved', response.data.data);
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to save student',
      position: 'top',
    });
  } finally {
    saving.value = false;
  }
};
</script>

