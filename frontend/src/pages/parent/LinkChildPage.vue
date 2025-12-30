<template>
  <q-page class="link-child-page">
    <MobilePageHeader
      title="Link Your Child"
      subtitle="Add a child to your account"
      :show-back="true"
      @back="router.push('/app/parent/children')"
    />

    <div class="form-content">
      <MobileCard variant="default" padding="lg">
        <div class="link-child-content">
          <q-icon name="child_care" size="56px" color="primary" class="q-mb-md" />
          <div class="link-title">Link Your Child</div>
          <div class="link-description">
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
        </div>
      </MobileCard>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
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
.link-child-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.form-content {
  max-width: 600px;
  margin: 0 auto;
}

.link-child-content {
  text-align: center;
  padding: var(--spacing-lg);
}

.link-title {
  font-size: var(--font-size-lg);
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: var(--spacing-sm);
}

.link-description {
  font-size: var(--font-size-base);
  color: var(--text-secondary);
  margin-bottom: var(--spacing-lg);
}
</style>

