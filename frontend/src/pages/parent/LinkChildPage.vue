<template>
  <q-page class="parent-page">
    <!-- Mobile Header -->
    <div class="parent-header q-pa-md">
      <div class="row items-center">
        <q-btn
          flat
          round
          icon="arrow_back"
          @click="router.push('/app/parent/children')"
          class="q-mr-sm"
          size="md"
        />
        <div class="col">
          <div class="text-h6 text-weight-bold">Link Your Child</div>
          <div class="text-caption text-grey-7">Add a child to your account</div>
        </div>
      </div>
    </div>

    <!-- Content Area -->
    <div class="parent-content q-pa-md">
      <q-card class="form-card">
        <q-card-section class="text-center q-pa-lg">
          <q-icon name="child_care" size="56px" color="primary" class="q-mb-md" />
          <div class="text-h6 text-weight-bold q-mb-sm">Link Your Child</div>
          <div class="text-body2 text-grey-7 q-mb-lg">
            Enter the Student Number provided by the school (e.g., BA01-STU001)
          </div>

          <q-form @submit="linkChild" class="q-gutter-md">
            <q-input
              v-model="studentIdentifier"
              label="Student Number *"
              outlined
              :rules="[(val) => !!val || 'Student Number is required']"
              :loading="linking"
              :disable="linking"
              hint="Format: SCHOOLCODE-STUNUMBER"
              class="q-mb-md"
              size="lg"
            >
              <template v-slot:prepend>
                <q-icon name="badge" />
              </template>
            </q-input>

            <q-select
              v-model="relationship"
              :options="relationshipOptions"
              label="Relationship (Optional)"
              outlined
              emit-value
              map-options
              :disable="linking"
              hint="Your relationship to the student"
              size="lg"
            />

            <q-btn
              type="submit"
              color="primary"
              label="Link Child"
              icon="link"
              unelevated
              :loading="linking"
              size="lg"
              class="full-width q-mt-md"
            />

            <div v-if="hasChildren" class="q-mt-lg">
              <q-separator class="q-mb-md" />
              <div class="text-body2 text-grey-7 q-mb-sm">Already linked a child?</div>
              <q-btn
                flat
                label="Go to My Children"
                color="primary"
                to="/app/parent/children"
                class="full-width"
                size="lg"
              />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import api from 'src/services/api';

const router = useRouter();
const $q = useQuasar();

const linking = ref(false);
const studentIdentifier = ref('');
const relationship = ref(null);
const hasChildren = ref(false);

const relationshipOptions = [
  { label: 'Father', value: 'father' },
  { label: 'Mother', value: 'mother' },
  { label: 'Guardian', value: 'guardian' },
  { label: 'Other', value: 'other' },
];

onMounted(() => {
  checkChildren();
});

async function checkChildren() {
  try {
    const response = await api.get('/parent/children');
    if (response.data.success) {
      hasChildren.value = (response.data.data || []).length > 0;
    }
  } catch (error) {
    // Ignore error, just check if we have children
  }
}

async function linkChild() {
  linking.value = true;
  try {
    const payload = {
      student_identifier: studentIdentifier.value,
    };
    
    // Only include relationship if it has a value
    if (relationship.value) {
      payload.relationship = relationship.value;
    }
    
    const response = await api.post('/parent/link-child', payload);

    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Child linked successfully!',
        position: 'top',
      });

      // Refresh children list
      await checkChildren();

      // Redirect to child detail page
      const childId = response.data.data?.id;
      if (childId) {
        router.push(`/app/parent/children/${childId}`);
      } else {
        router.push('/app/parent/children');
      }
    }
  } catch (error) {
    console.error('Failed to link child:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to link child. Please check the student ID or number.',
      position: 'top',
    });
  } finally {
    linking.value = false;
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
  max-width: 600px;
  margin: 0 auto;
  padding-top: 24px;
}

.form-card {
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
    padding: 16px;
  }
}
</style>

