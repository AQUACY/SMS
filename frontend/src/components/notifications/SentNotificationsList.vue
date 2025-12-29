<template>
  <div>
    <q-list v-if="sentNotifications.length > 0" class="sent-notifications-list">
      <q-item
        v-for="notification in sentNotifications"
        :key="notification.id"
        class="sent-notification-item"
      >
        <q-item-section avatar>
          <q-icon
            :name="getNotificationIcon(notification.type)"
            :color="getNotificationColor(notification.type)"
            size="24px"
          />
        </q-item-section>
        <q-item-section>
          <q-item-label class="text-weight-medium">{{ notification.title }}</q-item-label>
          <q-item-label caption lines="2">{{ notification.message }}</q-item-label>
          <q-item-label caption class="text-grey-6 q-mt-xs">
            <q-badge
              :color="notification.is_announcement ? 'red' : 'grey-6'"
              :label="notification.is_announcement ? 'Announcement' : 'Notification'"
              class="q-mr-xs"
            />
            <q-badge
              :color="getPriorityColor(notification.priority)"
              :label="notification.priority"
              class="q-mr-xs"
            />
            Sent to {{ notification.recipient_count }} recipient(s)
            <span v-if="notification.email_sent" class="q-ml-sm">
              • {{ notification.email_sent_count }} email(s) sent
            </span>
            • {{ formatTime(notification.created_at) }}
          </q-item-label>
        </q-item-section>
        <q-item-section side class="action-buttons">
          <div class="column items-end q-gutter-xs">
            <q-btn
              v-if="notification.can_edit"
              flat
              dense
              round
              icon="edit"
              size="md"
              color="primary"
              @click="editNotification(notification)"
              class="action-btn"
            />
            <q-btn
              v-if="notification.can_delete"
              flat
              dense
              round
              icon="delete"
              size="md"
              color="negative"
              @click="deleteNotification(notification)"
              class="action-btn"
            />
            <q-tooltip v-if="!notification.can_edit && !notification.can_delete">
              Cannot edit/delete - email already sent
            </q-tooltip>
          </div>
        </q-item-section>
      </q-item>
    </q-list>

    <div v-else-if="!loading" class="text-center q-pa-xl text-grey-6">
      <q-icon name="send" size="64px" class="q-mb-md" />
      <div class="text-h6">No sent notifications</div>
      <div class="text-body2 q-mt-sm">Notifications you send will appear here</div>
    </div>

    <div v-if="loading" class="text-center q-pa-xl">
      <q-spinner color="primary" size="3em" />
    </div>

    <!-- Edit Dialog -->
    <q-dialog v-model="showEditDialog">
      <q-card class="edit-dialog-card">
        <q-card-section>
          <div class="text-h6">Edit Notification</div>
        </q-card-section>

        <q-card-section>
          <q-form @submit="saveEdit" class="q-gutter-md">
            <q-input
              v-model="editForm.title"
              label="Title *"
              outlined
              :rules="[(val) => !!val || 'Title is required']"
            />

            <q-input
              v-model="editForm.message"
              label="Message *"
              type="textarea"
              rows="5"
              outlined
              :rules="[(val) => !!val || 'Message is required']"
            />

            <q-select
              v-model="editForm.type"
              :options="typeOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              label="Notification Type"
              outlined
            />

            <q-select
              v-model="editForm.priority"
              :options="priorityOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              label="Priority"
              outlined
            />

            <q-checkbox
              v-model="editForm.is_announcement"
              label="Mark as Announcement"
            />

            <q-card-actions align="right">
              <q-btn flat label="Cancel" color="grey-7" v-close-popup />
              <q-btn type="submit" label="Save" color="primary" :loading="saving" />
            </q-card-actions>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useQuasar } from 'quasar';
import api from 'src/services/api';

const $q = useQuasar();

const loading = ref(false);
const saving = ref(false);
const sentNotifications = ref([]);
const showEditDialog = ref(false);
const editForm = ref({
  id: null,
  title: '',
  message: '',
  type: 'general',
  priority: 'normal',
  is_announcement: false,
});

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

function getNotificationIcon(type) {
  const icons = {
    payment: 'payment',
    subscription: 'subscriptions',
    result: 'assignment',
    attendance: 'event',
    announcement: 'campaign',
    general: 'notifications',
  };
  return icons[type] || 'notifications';
}

function getNotificationColor(type) {
  const colors = {
    payment: 'green',
    subscription: 'blue',
    result: 'purple',
    attendance: 'orange',
    announcement: 'red',
    general: 'grey',
  };
  return colors[type] || 'grey';
}

function getPriorityColor(priority) {
  const colors = {
    urgent: 'red',
    high: 'orange',
    normal: 'blue',
    low: 'grey',
  };
  return colors[priority] || 'grey';
}

function formatTime(dateString) {
  if (!dateString) return '';
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  } catch {
    return '';
  }
}

async function fetchSentNotifications() {
  loading.value = true;
  try {
    const response = await api.get('/notifications/sent');
    if (response.data.success) {
      sentNotifications.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch sent notifications:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to fetch sent notifications',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
}

function editNotification(notification) {
  editForm.value = {
    id: notification.id,
    title: notification.title,
    message: notification.message,
    type: notification.type,
    priority: notification.priority,
    is_announcement: notification.is_announcement,
  };
  showEditDialog.value = true;
}

async function saveEdit() {
  saving.value = true;
  try {
    const response = await api.put(`/notifications/sent/${editForm.value.id}`, {
      title: editForm.value.title,
      message: editForm.value.message,
      type: editForm.value.type,
      priority: editForm.value.priority,
      is_announcement: editForm.value.is_announcement,
    });
    
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: `Notification updated successfully (${response.data.data.updated_count} notification(s) updated)`,
        position: 'top',
      });
      showEditDialog.value = false;
      await fetchSentNotifications();
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to update notification',
      position: 'top',
    });
  } finally {
    saving.value = false;
  }
}

async function deleteNotification(notification) {
  $q.dialog({
    title: 'Confirm Delete',
    message: `Are you sure you want to delete this notification? This will delete ${notification.recipient_count} notification(s) that haven't been emailed.`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      const response = await api.delete(`/notifications/sent/${notification.id}`);
      if (response.data.success) {
        $q.notify({
          type: 'positive',
          message: `Notification deleted successfully (${response.data.data.deleted_count} notification(s) deleted)`,
          position: 'top',
        });
        await fetchSentNotifications();
      }
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to delete notification',
        position: 'top',
      });
    }
  });
}

onMounted(() => {
  fetchSentNotifications();
});
</script>

<style lang="scss" scoped>
.sent-notifications-list {
  :deep(.q-item) {
    padding: 12px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    min-height: auto;
    
    @media (min-width: 600px) {
      padding: 16px;
    }
    
    &:hover {
      background: rgba(0, 0, 0, 0.02);
    }
    
    .q-item__section--avatar {
      min-width: 40px;
      padding-right: 12px;
      
      @media (min-width: 600px) {
        min-width: 48px;
        padding-right: 16px;
      }
    }
    
    .q-item__section--side {
      padding-left: 8px;
      
      @media (min-width: 600px) {
        padding-left: 16px;
      }
    }
    
    .q-item__label {
      font-size: 14px;
      
      @media (min-width: 600px) {
        font-size: 16px;
      }
    }
    
    .q-item__label--caption {
      font-size: 12px;
      
      @media (min-width: 600px) {
        font-size: 14px;
      }
    }
  }
}

.sent-notification-item {
  transition: all 0.2s ease;
}

.action-buttons {
  .action-btn {
    min-width: 40px;
    min-height: 40px;
    width: 40px;
    height: 40px;
    
    @media (min-width: 600px) {
      min-width: 36px;
      min-height: 36px;
      width: 36px;
      height: 36px;
    }
  }
}

.edit-dialog-card {
  width: 100%;
  max-width: 500px;
  
  @media (max-width: 599px) {
    margin: 16px;
    max-width: calc(100% - 32px);
  }
}

:deep(.q-badge) {
  font-size: 11px;
  padding: 4px 8px;
  
  @media (min-width: 600px) {
    font-size: 12px;
    padding: 4px 10px;
  }
}
</style>

