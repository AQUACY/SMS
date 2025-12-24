<template>
  <q-page class="q-pa-lg">
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">Grading Scales</div>
        <div class="text-body2 text-grey-7">Configure grading systems for your school</div>
      </div>
      <q-btn
        v-if="authStore.isSchoolAdmin || authStore.isSuperAdmin"
        color="primary"
        label="Add Grading Scale"
        icon="add"
        unelevated
        @click="showCreateDialog = true"
      />
    </div>

    <q-card class="widget-card">
      <q-card-section>
        <q-table
          :rows="gradingScales"
          :columns="columns"
          row-key="id"
          :loading="loading"
          flat
        >
          <template v-slot:body-cell-is_default="props">
            <q-td :props="props">
              <q-badge
                v-if="props.value"
                color="primary"
                label="Default"
              />
              <span v-else class="text-grey-7">-</span>
            </q-td>
          </template>

          <template v-slot:body-cell-is_active="props">
            <q-td :props="props">
              <q-badge
                :color="props.value ? 'positive' : 'grey'"
                :label="props.value ? 'Active' : 'Inactive'"
              />
            </q-td>
          </template>

          <template v-slot:body-cell-grade_levels="props">
            <q-td :props="props">
              <div class="text-body2">{{ props.row.grade_levels?.length || 0 }} grades</div>
              <div class="text-caption text-grey-7">
                {{ getGradeRange(props.row) }}
              </div>
            </q-td>
          </template>

          <template v-slot:body-cell-actions="props">
            <q-td :props="props">
              <q-btn
                flat
                dense
                icon="visibility"
                color="primary"
                @click="viewGradingScale(props.row)"
                class="q-mr-xs"
              />
              <q-btn
                flat
                dense
                icon="edit"
                color="primary"
                @click="editGradingScale(props.row)"
                class="q-mr-xs"
              />
              <q-btn
                v-if="!props.row.is_default"
                flat
                dense
                icon="star"
                color="warning"
                @click="setAsDefault(props.row)"
                class="q-mr-xs"
              />
              <q-btn
                v-if="!props.row.is_default"
                flat
                dense
                icon="delete"
                color="negative"
                @click="deleteGradingScale(props.row)"
              />
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <!-- Create/Edit Dialog -->
    <q-dialog v-model="showCreateDialog" persistent>
      <q-card style="min-width: 800px; max-width: 1000px;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">{{ editingScale ? 'Edit Grading Scale' : 'Create Grading Scale' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <q-form @submit="saveGradingScale" class="q-gutter-md">
            <q-input
              v-model="form.name"
              label="Grading Scale Name *"
              outlined
              hint="e.g., Standard Grading, GES Grading"
              :rules="[(val) => !!val || 'Name is required']"
            />

            <q-input
              v-model="form.description"
              label="Description"
              outlined
              type="textarea"
              rows="2"
            />

            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <q-checkbox
                  v-model="form.is_default"
                  label="Set as Default"
                />
              </div>
              <div class="col-12 col-md-6">
                <q-checkbox
                  v-model="form.is_active"
                  label="Active"
                />
              </div>
            </div>

            <q-separator class="q-my-md" />

            <div class="text-h6 q-mb-md">Grade Levels</div>
            <div class="text-body2 text-grey-7 q-mb-md">
              Define the grade levels for this grading scale. Grades should be ordered from highest to lowest.
            </div>

            <div class="q-gutter-md">
              <q-card
                v-for="(level, index) in form.grade_levels"
                :key="index"
                flat
                bordered
                class="q-pa-md"
              >
                <div class="row q-col-gutter-md items-center">
                  <div class="col-12 col-md-2">
                    <q-input
                      v-model="level.grade"
                      label="Grade *"
                      outlined
                      dense
                      hint="e.g., A, B, C"
                      :rules="[(val) => !!val || 'Required']"
                    />
                  </div>
                  <div class="col-12 col-md-3">
                    <q-input
                      v-model="level.label"
                      label="Label"
                      outlined
                      dense
                      hint="e.g., Excellent, Very Good"
                    />
                  </div>
                  <div class="col-12 col-md-2">
                    <q-input
                      v-model.number="level.min_percentage"
                      label="Min % *"
                      type="number"
                      outlined
                      dense
                      :rules="[
                        (val) => val !== null && val !== '' || 'Required',
                        (val) => val >= 0 && val <= 100 || 'Must be 0-100',
                      ]"
                    />
                  </div>
                  <div class="col-12 col-md-2">
                    <q-input
                      v-model.number="level.max_percentage"
                      label="Max %"
                      type="number"
                      outlined
                      dense
                      hint="Leave empty for highest"
                      :rules="[
                        (val) => !val || (val >= 0 && val <= 100) || 'Must be 0-100',
                        (val) => !val || val >= level.min_percentage || 'Must be >= Min %',
                      ]"
                    />
                  </div>
                  <div class="col-12 col-md-2">
                    <q-input
                      v-model="level.gpa_value"
                      label="GPA Value"
                      outlined
                      dense
                      hint="e.g., 4.0, 3.5"
                    />
                  </div>
                  <div class="col-12 col-md-1">
                    <q-btn
                      flat
                      dense
                      icon="delete"
                      color="negative"
                      @click="removeGradeLevel(index)"
                      :disable="form.grade_levels.length <= 1"
                    />
                  </div>
                </div>
                <div class="row q-mt-sm">
                  <div class="col-12">
                    <q-input
                      v-model="level.description"
                      label="Description"
                      outlined
                      dense
                    />
                  </div>
                </div>
              </q-card>
            </div>

            <q-btn
              color="primary"
              label="Add Grade Level"
              icon="add"
              outline
              @click="addGradeLevel"
              class="q-mt-md"
            />

            <div class="row justify-end q-mt-lg">
              <q-btn
                flat
                label="Cancel"
                @click="closeDialog"
                class="q-mr-sm"
              />
              <q-btn
                type="submit"
                color="primary"
                label="Save Grading Scale"
                :loading="submitting"
              />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- View Dialog -->
    <q-dialog v-model="showViewDialog">
      <q-card style="min-width: 600px;">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">{{ viewingScale?.name }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section v-if="viewingScale">
          <div class="text-body2 text-grey-7 q-mb-md" v-if="viewingScale.description">
            {{ viewingScale.description }}
          </div>

          <q-table
            :rows="viewingScale.grade_levels || []"
            :columns="viewColumns"
            row-key="id"
            flat
          >
            <template v-slot:body-cell-range="props">
              <q-td :props="props">
                {{ props.row.min_percentage }}%
                <span v-if="props.row.max_percentage"> - {{ props.row.max_percentage }}%</span>
                <span v-else> and above</span>
              </q-td>
            </template>
          </q-table>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'src/stores/auth';
import api from 'src/services/api';

const $q = useQuasar();
const authStore = useAuthStore();

const loading = ref(false);
const submitting = ref(false);
const gradingScales = ref([]);
const showCreateDialog = ref(false);
const showViewDialog = ref(false);
const editingScale = ref(null);
const viewingScale = ref(null);

const form = ref({
  name: '',
  description: '',
  is_default: false,
  is_active: true,
  grade_levels: [
    { grade: 'A', label: 'Excellent', min_percentage: 80, max_percentage: null, gpa_value: '4.0', description: '', order: 6 },
    { grade: 'B', label: 'Very Good', min_percentage: 70, max_percentage: 79.99, gpa_value: '3.0', description: '', order: 5 },
    { grade: 'C', label: 'Good', min_percentage: 60, max_percentage: 69.99, gpa_value: '2.0', description: '', order: 4 },
    { grade: 'D', label: 'Fair', min_percentage: 50, max_percentage: 59.99, gpa_value: '1.0', description: '', order: 3 },
    { grade: 'E', label: 'Poor', min_percentage: 40, max_percentage: 49.99, gpa_value: '0.5', description: '', order: 2 },
    { grade: 'F', label: 'Fail', min_percentage: 0, max_percentage: 39.99, gpa_value: '0.0', description: '', order: 1 },
  ],
});

const columns = [
  { name: 'name', label: 'Name', field: 'name', align: 'left', sortable: true },
  { name: 'is_default', label: 'Default', field: 'is_default', align: 'center' },
  { name: 'is_active', label: 'Status', field: 'is_active', align: 'center' },
  { name: 'grade_levels', label: 'Grade Levels', field: 'grade_levels', align: 'left' },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'right' },
];

const viewColumns = [
  { name: 'grade', label: 'Grade', field: 'grade', align: 'left' },
  { name: 'label', label: 'Label', field: 'label', align: 'left' },
  { name: 'range', label: 'Percentage Range', field: 'range', align: 'left' },
  { name: 'gpa_value', label: 'GPA', field: 'gpa_value', align: 'left' },
];

onMounted(() => {
  fetchGradingScales();
});

async function fetchGradingScales() {
  loading.value = true;
  try {
    const response = await api.get('/grading-scales');
    if (response.data.success) {
      gradingScales.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch grading scales:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch grading scales',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
}

function addGradeLevel() {
  form.value.grade_levels.push({
    grade: '',
    label: '',
    min_percentage: null,
    max_percentage: null,
    gpa_value: '',
    description: '',
    order: form.value.grade_levels.length + 1,
  });
}

function removeGradeLevel(index) {
  form.value.grade_levels.splice(index, 1);
  // Recalculate order
  form.value.grade_levels.forEach((level, idx) => {
    level.order = idx + 1;
  });
}

function viewGradingScale(scale) {
  viewingScale.value = scale;
  showViewDialog.value = true;
}

function editGradingScale(scale) {
  editingScale.value = scale;
  form.value = {
    name: scale.name,
    description: scale.description || '',
    is_default: scale.is_default || false,
    is_active: scale.is_active !== false,
    grade_levels: (scale.grade_levels || []).map(level => ({
      grade: level.grade,
      label: level.label || '',
      min_percentage: level.min_percentage,
      max_percentage: level.max_percentage,
      gpa_value: level.gpa_value || '',
      description: level.description || '',
      order: level.order || 0,
    })),
  };
  showCreateDialog.value = true;
}

function closeDialog() {
  showCreateDialog.value = false;
  editingScale.value = null;
  form.value = {
    name: '',
    description: '',
    is_default: false,
    is_active: true,
    grade_levels: [
      { grade: 'A', label: 'Excellent', min_percentage: 80, max_percentage: null, gpa_value: '4.0', description: '', order: 6 },
      { grade: 'B', label: 'Very Good', min_percentage: 70, max_percentage: 79.99, gpa_value: '3.0', description: '', order: 5 },
      { grade: 'C', label: 'Good', min_percentage: 60, max_percentage: 69.99, gpa_value: '2.0', description: '', order: 4 },
      { grade: 'D', label: 'Fair', min_percentage: 50, max_percentage: 59.99, gpa_value: '1.0', description: '', order: 3 },
      { grade: 'E', label: 'Poor', min_percentage: 40, max_percentage: 49.99, gpa_value: '0.5', description: '', order: 2 },
      { grade: 'F', label: 'Fail', min_percentage: 0, max_percentage: 39.99, gpa_value: '0.0', description: '', order: 1 },
    ],
  };
}

async function saveGradingScale() {
  // Validate grade levels
  if (form.value.grade_levels.length === 0) {
    $q.notify({
      type: 'negative',
      message: 'Please add at least one grade level',
      position: 'top',
    });
    return;
  }

  // Validate that grades are unique
  const grades = form.value.grade_levels.map(l => l.grade);
  if (new Set(grades).size !== grades.length) {
    $q.notify({
      type: 'negative',
      message: 'Grade levels must have unique grades',
      position: 'top',
    });
    return;
  }

  submitting.value = true;
  try {
    const payload = {
      ...form.value,
      grade_levels: form.value.grade_levels.map((level, index) => ({
        ...level,
        order: level.order || (index + 1),
      })),
    };

    if (editingScale.value) {
      const response = await api.put(`/grading-scales/${editingScale.value.id}`, payload);
      if (response.data.success) {
        $q.notify({
          type: 'positive',
          message: 'Grading scale updated successfully',
          position: 'top',
        });
      }
    } else {
      const response = await api.post('/grading-scales', payload);
      if (response.data.success) {
        $q.notify({
          type: 'positive',
          message: 'Grading scale created successfully',
          position: 'top',
        });
      }
    }

    closeDialog();
    fetchGradingScales();
  } catch (error) {
    console.error('Failed to save grading scale:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to save grading scale',
      position: 'top',
    });
  } finally {
    submitting.value = false;
  }
}

async function setAsDefault(scale) {
  $q.dialog({
    title: 'Set as Default',
    message: `Set "${scale.name}" as the default grading scale?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      const response = await api.post(`/grading-scales/${scale.id}/set-default`);
      if (response.data.success) {
        $q.notify({
          type: 'positive',
          message: 'Grading scale set as default successfully',
          position: 'top',
        });
        fetchGradingScales();
      }
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to set default grading scale',
        position: 'top',
      });
    }
  });
}

async function deleteGradingScale(scale) {
  $q.dialog({
    title: 'Confirm Delete',
    message: `Are you sure you want to delete the grading scale "${scale.name}"?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await api.delete(`/grading-scales/${scale.id}`);
      $q.notify({
        type: 'positive',
        message: 'Grading scale deleted successfully',
        position: 'top',
      });
      fetchGradingScales();
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to delete grading scale',
        position: 'top',
      });
    }
  });
}

function getGradeRange(scale) {
  if (!scale.grade_levels || scale.grade_levels.length === 0) return 'No grades';
  const sorted = [...scale.grade_levels].sort((a, b) => (b.order || 0) - (a.order || 0));
  const highest = sorted[0];
  const lowest = sorted[sorted.length - 1];
  return `${highest.grade} (${highest.min_percentage}%+) to ${lowest.grade} (${lowest.min_percentage}%)`;
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

