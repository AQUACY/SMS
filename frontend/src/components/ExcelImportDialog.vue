<template>
  <q-dialog v-model="showDialog" persistent>
    <q-card style="min-width: 500px">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6">{{ title }}</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <q-card-section>
        <div class="text-body2 text-grey-7 q-mb-md">
          {{ description }}
        </div>

        <div class="q-mb-md">
          <q-btn
            color="primary"
            icon="download"
            label="Download Template"
            @click="downloadTemplate"
            :loading="downloading"
            class="full-width"
          />
        </div>

        <q-separator class="q-my-md" />

        <div class="text-body2 text-weight-medium q-mb-sm">Upload Excel File:</div>
        <q-file
          v-model="file"
          label="Select Excel file (.xlsx, .xls)"
          accept=".xlsx,.xls"
          outlined
          :max-file-size="10485760"
          @rejected="onRejected"
        >
          <template v-slot:prepend>
            <q-icon name="attach_file" />
          </template>
        </q-file>

        <div v-if="file" class="q-mt-sm text-body2 text-grey-7">
          Selected: {{ file.name }} ({{ formatFileSize(file.size) }})
        </div>
      </q-card-section>

      <q-card-section v-if="importResult" class="q-pt-none">
        <q-banner
          :class="importResult.success ? 'bg-positive' : 'bg-negative'"
          class="text-white"
        >
          <template v-slot:avatar>
            <q-icon :name="importResult.success ? 'check_circle' : 'error'" />
          </template>
          {{ importResult.message }}
        </q-banner>

        <div v-if="importResult.data" class="q-mt-md">
          <div class="text-body2 q-mb-sm">
            <strong>Total Rows:</strong> {{ importResult.data.total_rows }}
          </div>
          <div class="text-body2 q-mb-sm text-positive">
            <strong>Success:</strong> {{ importResult.data.success_count }}
          </div>
          <div v-if="importResult.data.error_count > 0" class="text-body2 q-mb-sm text-negative">
            <strong>Errors:</strong> {{ importResult.data.error_count }}
          </div>

          <q-expansion-item
            v-if="importResult.data.errors && importResult.data.errors.length > 0"
            label="View Errors"
            icon="error"
            class="q-mt-sm"
          >
            <q-card>
              <q-card-section>
                <div
                  v-for="(error, index) in importResult.data.errors"
                  :key="index"
                  class="q-mb-sm q-pa-sm bg-grey-2"
                >
                  <div class="text-weight-medium">Row {{ error.row }}</div>
                  <div v-if="error.student_number" class="text-caption">
                    Student Number: {{ error.student_number }}
                  </div>
                  <div v-if="error.email" class="text-caption">Email: {{ error.email }}</div>
                  <div v-if="error.class_name" class="text-caption">Class Name: {{ error.class_name }}</div>
                  <div v-if="error.subject_name" class="text-caption">Subject Name: {{ error.subject_name }}</div>
                  <ul class="q-mt-xs q-ml-md">
                    <li v-for="(err, i) in error.errors" :key="i" class="text-negative">
                      {{ err }}
                    </li>
                  </ul>
                </div>
              </q-card-section>
            </q-card>
          </q-expansion-item>
        </div>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn flat label="Cancel" v-close-popup />
        <q-btn
          color="primary"
          label="Import"
          @click="handleImport"
          :loading="importing"
          :disable="!file"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useQuasar } from 'quasar';
import api from 'src/services/api';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  type: {
    type: String,
    required: true,
    validator: (value) => ['students', 'teachers', 'classes', 'subjects', 'class-students'].includes(value),
  },
  classId: {
    type: [String, Number],
    default: null,
  },
  title: {
    type: String,
    default: 'Import Data',
  },
  description: {
    type: String,
    default: 'Download the template, fill it with your data, and upload it here.',
  },
});

const emit = defineEmits(['update:modelValue', 'imported']);

const $q = useQuasar();

const showDialog = ref(props.modelValue);
const file = ref(null);
const downloading = ref(false);
const importing = ref(false);
const importResult = ref(null);

watch(() => props.modelValue, (val) => {
  showDialog.value = val;
  if (!val) {
    // Reset on close
    file.value = null;
    importResult.value = null;
  }
});

watch(showDialog, (val) => {
  emit('update:modelValue', val);
});

function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function onRejected(rejectedEntries) {
  $q.notify({
    type: 'negative',
    message: 'File rejected. Please select a valid Excel file (.xlsx or .xls)',
    position: 'top',
  });
}

async function downloadTemplate() {
  downloading.value = true;
  try {
    let apiUrl = `/excel/templates/${props.type}`;
    if (props.type === 'class-students') {
      apiUrl = '/excel/templates/class-students';
    }
    
    const response = await api.get(apiUrl, {
      responseType: 'blob',
    });

    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `${props.type}_import_template_${new Date().toISOString().split('T')[0]}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);

    $q.notify({
      type: 'positive',
      message: 'Template downloaded successfully',
      position: 'top',
    });
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to download template',
      position: 'top',
    });
  } finally {
    downloading.value = false;
  }
}

async function handleImport() {
  if (!file.value) {
    $q.notify({
      type: 'warning',
      message: 'Please select a file to import',
      position: 'top',
    });
    return;
  }

  importing.value = true;
  importResult.value = null;

  try {
    const formData = new FormData();
    formData.append('file', file.value);
    
    // Add class_id for class-students import
    if (props.type === 'class-students' && props.classId) {
      formData.append('class_id', props.classId);
    }

    let url = `/excel/import/${props.type}`;
    if (props.type === 'class-students') {
      url = '/excel/import/class-students';
    }

    const response = await api.post(url, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    if (response.data.success) {
      importResult.value = {
        success: true,
        message: response.data.message,
        data: response.data.data,
      };

      $q.notify({
        type: 'positive',
        message: response.data.message,
        position: 'top',
      });

      // Emit imported event after a short delay
      setTimeout(() => {
        emit('imported', response.data.data);
      }, 1000);
    } else {
      importResult.value = {
        success: false,
        message: response.data.message,
        data: response.data.data,
      };
    }
  } catch (error) {
    const errorData = error.response?.data;
    importResult.value = {
      success: false,
      message: errorData?.message || 'Import failed',
      data: errorData?.data || errorData,
    };

    $q.notify({
      type: 'negative',
      message: errorData?.message || 'Failed to import file',
      position: 'top',
    });
  } finally {
    importing.value = false;
  }
}
</script>

<style lang="scss" scoped>
</style>

