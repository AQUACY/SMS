<template>
  <q-page class="form-page">
    <MobilePageHeader
      title="Send Notification"
      subtitle="Send notifications to users"
      :show-back="true"
      @back="$router.push('/app/notifications')"
    />

    <div class="form-content">
      <MobileCard variant="default" padding="md">
        <q-form @submit="sendNotification" class="form">
          <q-select
            v-model="form.recipient_type"
            :options="recipientTypeOptions"
            option-label="label"
            option-value="value"
            emit-value
            map-options
            label="Recipient Type *"
            outlined
            :rules="[(val) => !!val || 'Recipient type is required']"
            @update:model-value="onRecipientTypeChange"
          />

          <q-select
            v-if="form.recipient_type === 'role'"
            v-model="form.role"
            :options="roleOptions"
            option-label="label"
            option-value="value"
            emit-value
            map-options
            label="Role *"
            outlined
            :rules="[(val) => !!val || 'Role is required']"
          />

          <q-select
            v-if="form.recipient_type === 'specific'"
            v-model="form.user_ids"
            :options="users"
            option-label="name"
            option-value="id"
            emit-value
            map-options
            multiple
            use-chips
            label="Select Users *"
            outlined
            :rules="[(val) => val && val.length > 0 || 'At least one user is required']"
            :loading="loadingUsers"
            @filter="filterUsers"
          >
            <template v-slot:option="scope">
              <q-item v-bind="scope.itemProps">
                <q-item-section avatar>
                  <q-avatar color="primary" text-color="white" size="32px">
                    {{ scope.opt.name.charAt(0) }}
                  </q-avatar>
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ scope.opt.name }}</q-item-label>
                  <q-item-label caption>{{ scope.opt.email }}</q-item-label>
                  <q-item-label caption>
                    <q-badge
                      v-for="role in scope.opt.roles"
                      :key="role"
                      :label="role"
                      color="grey-6"
                      class="q-mr-xs"
                      size="sm"
                    />
                  </q-item-label>
                </q-item-section>
              </q-item>
            </template>
          </q-select>

          <q-input
            v-model="form.title"
            label="Title *"
            outlined
            :rules="[(val) => !!val || 'Title is required']"
          />

          <q-input
            v-model="form.message"
            label="Message *"
            type="textarea"
            rows="5"
            outlined
            :rules="[(val) => !!val || 'Message is required']"
          />

          <q-select
            v-model="form.type"
            :options="typeOptions"
            option-label="label"
            option-value="value"
            emit-value
            map-options
            label="Notification Type"
            outlined
          />

          <q-select
            v-model="form.priority"
            :options="priorityOptions"
            option-label="label"
            option-value="value"
            emit-value
            map-options
            label="Priority"
            outlined
          />

          <q-checkbox
            v-model="form.is_announcement"
            label="Mark as Announcement (will be prominently displayed)"
          />

          <q-checkbox
            v-model="form.send_email"
            label="Send Email Notification"
          />

          <div class="form-actions">
            <q-btn
              flat
              label="Cancel"
              color="grey-7"
              @click="$router.push('/app/notifications')"
              class="q-mr-sm"
            />
            <q-btn
              type="submit"
              color="primary"
              label="Send Notification"
              :loading="sending"
            />
          </div>
        </q-form>
      </MobileCard>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'src/stores/auth';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import { notificationService } from 'src/services/notifications';

const router = useRouter();
const $q = useQuasar();
const authStore = useAuthStore();

const sending = ref(false);
const loadingUsers = ref(false);
const users = ref([]);
const allUsers = ref([]);

const form = ref({
  recipient_type: 'all',
  role: null,
  user_ids: [],
  title: '',
  message: '',
  type: 'general',
  priority: 'normal',
  is_announcement: false,
  send_email: true,
});

const recipientTypeOptions = [
  { label: 'All Users', value: 'all' },
  { label: 'By Role', value: 'role' },
  { label: 'Specific Users', value: 'specific' },
];

const roleOptions = [
  { label: 'Parents', value: 'parent' },
  { label: 'Teachers', value: 'teacher' },
  { label: 'School Admins', value: 'school_admin' },
  { label: 'Accounts Managers', value: 'accounts_manager' },
];

const typeOptions = [
  { label: 'General', value: 'general' },
  { label: 'Payment', value: 'payment' },
  { label: 'Subscription', value: 'subscription' },
  { label: 'Result', value: 'result' },
  { label: 'Attendance', value: 'attendance' },
  { label: 'Announcement', value: 'announcement' },
];

const priorityOptions = [
  { label: 'Low', value: 'low' },
  { label: 'Normal', value: 'normal' },
  { label: 'High', value: 'high' },
  { label: 'Urgent', value: 'urgent' },
];

function onRecipientTypeChange() {
  form.value.role = null;
  form.value.user_ids = [];
  if (form.value.recipient_type === 'specific') {
    fetchUsers();
  }
}

async function fetchUsers() {
  loadingUsers.value = true;
  try {
    const response = await notificationService.getRecipients();
    if (response.success) {
      allUsers.value = response.data || [];
      users.value = allUsers.value;
    }
  } catch (error) {
    console.error('Failed to fetch users:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to fetch users',
      position: 'top',
    });
  } finally {
    loadingUsers.value = false;
  }
}

function filterUsers(val, update) {
  if (val === '') {
    update(() => {
      users.value = allUsers.value;
    });
    return;
  }

  update(() => {
    const needle = val.toLowerCase();
    users.value = allUsers.value.filter(
      (user) =>
        user.name.toLowerCase().indexOf(needle) > -1 ||
        user.email.toLowerCase().indexOf(needle) > -1
    );
  });
}

async function sendNotification() {
  sending.value = true;
  try {
    // Prepare the payload - only include role if recipient_type is 'role'
    const payload = {
      ...form.value,
      // Only include role if recipient_type is 'role', otherwise remove it
      role: form.value.recipient_type === 'role' ? form.value.role : undefined,
      // Only include user_ids if recipient_type is 'specific', otherwise remove it
      user_ids: form.value.recipient_type === 'specific' ? form.value.user_ids : undefined,
    };
    
    // Remove undefined fields
    Object.keys(payload).forEach(key => {
      if (payload[key] === undefined) {
        delete payload[key];
      }
    });
    
    const response = await notificationService.sendNotification(payload);
    if (response.success) {
      $q.notify({
        type: 'positive',
        message: `Notification sent successfully to ${response.data.notifications_sent} user(s)`,
        position: 'top',
      });
      router.push('/app/notifications');
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to send notification',
      position: 'top',
    });
  } finally {
    sending.value = false;
  }
}

onMounted(() => {
  if (form.value.recipient_type === 'specific') {
    fetchUsers();
  }
});
</script>

<style lang="scss" scoped>
.form-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
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

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-sm);
  margin-top: var(--spacing-lg);
  padding-top: var(--spacing-md);
  border-top: 1px solid var(--border-light);
}
</style>

