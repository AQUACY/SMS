<template>
  <q-page class="q-pa-lg">
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">Schools Management</div>
        <div class="text-body2 text-grey-7">Manage all schools in the system</div>
      </div>
      <q-btn
        color="primary"
        label="Add School"
        icon="add"
        unelevated
        @click="showCreateDialog = true"
      />
    </div>

    <!-- Search and Filters -->
    <q-card class="widget-card q-mb-md">
      <q-card-section>
        <div class="row q-gutter-md">
          <div class="col-12 col-md-4">
            <q-input
              v-model="searchQuery"
              placeholder="Search schools..."
              outlined
              dense
              clearable
              @update:model-value="fetchSchools"
            >
              <template v-slot:prepend>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>
          <div class="col-12 col-md-3">
            <q-select
              v-model="statusFilter"
              :options="statusOptions"
              label="Status"
              outlined
              dense
              clearable
              @update:model-value="fetchSchools"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Schools Table -->
    <q-card class="widget-card">
      <q-card-section>
        <q-table
          :rows="schools"
          :columns="columns"
          row-key="id"
          :loading="loading"
          :pagination="pagination"
          @request="onRequest"
          flat
        >
          <template v-slot:body-cell-is_active="props">
            <q-td :props="props">
              <q-badge
                :color="props.value ? 'positive' : 'negative'"
                :label="props.value ? 'Active' : 'Inactive'"
              />
            </q-td>
          </template>

          <template v-slot:body-cell-actions="props">
            <q-td :props="props">
              <q-btn
                flat
                dense
                icon="visibility"
                color="primary"
                @click="viewSchool(props.row.id)"
                class="q-mr-xs"
              />
              <q-btn
                flat
                dense
                icon="login"
                color="secondary"
                @click="signInAsSchool(props.row)"
                class="q-mr-xs"
                label="Sign In"
              />
              <q-btn
                flat
                dense
                icon="edit"
                color="primary"
                @click="editSchool(props.row)"
                class="q-mr-xs"
              />
              <q-btn
                flat
                dense
                icon="delete"
                color="negative"
                @click="confirmDelete(props.row)"
              />
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <!-- Create/Edit School Dialog -->
    <q-dialog v-model="showCreateDialog" persistent>
      <q-card style="min-width: 500px">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">{{ editingSchool ? 'Edit School' : 'Create New School' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup @click="closeDialog" />
        </q-card-section>

        <q-card-section>
          <q-form @submit="saveSchool" class="q-gutter-md">
            <q-input
              v-model="schoolForm.name"
              label="School Name *"
              outlined
              :rules="[val => !!val || 'School name is required']"
            />

            <q-input
              v-model="schoolForm.code"
              label="School Code"
              outlined
              hint="Unique code for the school"
            />

            <q-input
              v-model="schoolForm.domain"
              label="Domain"
              outlined
              hint="School domain (e.g., schoolname.sms.com)"
            />

            <q-input
              v-model="schoolForm.email"
              label="Email"
              outlined
              type="email"
            />

            <q-input
              v-model="schoolForm.phone"
              label="Phone"
              outlined
            />

            <q-input
              v-model="schoolForm.address"
              label="Address"
              outlined
              type="textarea"
              rows="2"
            />

            <q-toggle
              v-model="schoolForm.is_active"
              label="Active"
            />

            <q-separator class="q-my-md" />

            <div class="text-subtitle2 q-mb-sm">Create School Admin (Optional)</div>
            <q-input
              v-model="schoolForm.admin_email"
              label="Admin Email"
              outlined
              type="email"
              hint="If provided, a school admin account will be created"
            />

            <q-input
              v-if="schoolForm.admin_email"
              v-model="schoolForm.admin_password"
              label="Admin Password *"
              outlined
              type="password"
              :rules="[val => !schoolForm.admin_email || (val && val.length >= 8) || 'Password must be at least 8 characters']"
            />

            <div v-if="schoolForm.admin_email" class="row q-gutter-sm">
              <q-input
                v-model="schoolForm.admin_first_name"
                label="First Name"
                outlined
                class="col"
              />
              <q-input
                v-model="schoolForm.admin_last_name"
                label="Last Name"
                outlined
                class="col"
              />
            </div>

            <q-card-actions align="right" class="q-pt-md">
              <q-btn flat label="Cancel" @click="closeDialog" />
              <q-btn color="primary" label="Save" type="submit" :loading="saving" />
            </q-card-actions>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'src/stores/auth';
import api from 'src/services/api';

const router = useRouter();
const $q = useQuasar();
const authStore = useAuthStore();

const loading = ref(false);
const saving = ref(false);
const schools = ref([]);
const searchQuery = ref('');
const statusFilter = ref(null);
const showCreateDialog = ref(false);
const editingSchool = ref(null);

const statusOptions = [
  { label: 'Active', value: true },
  { label: 'Inactive', value: false },
];

const columns = [
  { name: 'name', label: 'School Name', field: 'name', align: 'left', sortable: true },
  { name: 'code', label: 'Code', field: 'code', align: 'left' },
  { name: 'domain', label: 'Domain', field: 'domain', align: 'left' },
  { name: 'email', label: 'Email', field: 'email', align: 'left' },
  { name: 'users_count', label: 'Users', field: 'users_count', align: 'center' },
  { name: 'students_count', label: 'Students', field: 'students_count', align: 'center' },
  { name: 'is_active', label: 'Status', field: 'is_active', align: 'center' },
  { name: 'actions', label: 'Actions', field: 'actions', align: 'right' },
];

const pagination = ref({
  page: 1,
  rowsPerPage: 15,
  rowsNumber: 0,
});

const schoolForm = ref({
  name: '',
  code: '',
  domain: '',
  email: '',
  phone: '',
  address: '',
  is_active: true,
  admin_email: '',
  admin_password: '',
  admin_first_name: '',
  admin_last_name: '',
});

onMounted(() => {
  fetchSchools();
});

function onRequest(props) {
  pagination.value = props.pagination;
  fetchSchools();
}

async function fetchSchools() {
  loading.value = true;
  try {
    const params = {
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
    };

    if (searchQuery.value) {
      params.search = searchQuery.value;
    }

    if (statusFilter.value !== null) {
      params.is_active = statusFilter.value.value;
    }

    const response = await api.get('/schools', { params });

    if (response.data.success) {
      schools.value = response.data.data;
      pagination.value.rowsNumber = response.data.meta.total;
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch schools',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
}

function viewSchool(id) {
  router.push(`/app/super-admin/schools/${id}`);
}

function editSchool(school) {
  editingSchool.value = school;
  schoolForm.value = {
    name: school.name,
    code: school.code || '',
    domain: school.domain || '',
    email: school.email || '',
    phone: school.phone || '',
    address: school.address || '',
    is_active: school.is_active,
    admin_email: '',
    admin_password: '',
    admin_first_name: '',
    admin_last_name: '',
  };
  showCreateDialog.value = true;
}

async function saveSchool() {
  saving.value = true;
  try {
    const url = editingSchool.value
      ? `/schools/${editingSchool.value.id}`
      : '/schools';

    const method = editingSchool.value ? 'put' : 'post';

    const response = await api[method](url, schoolForm.value);

    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: editingSchool.value
          ? 'School updated successfully'
          : 'School created successfully',
        position: 'top',
      });

      closeDialog();
      fetchSchools();
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to save school',
      position: 'top',
    });
  } finally {
    saving.value = false;
  }
}

function closeDialog() {
  showCreateDialog.value = false;
  editingSchool.value = null;
  schoolForm.value = {
    name: '',
    code: '',
    domain: '',
    email: '',
    phone: '',
    address: '',
    is_active: true,
    admin_email: '',
    admin_password: '',
    admin_first_name: '',
    admin_last_name: '',
  };
}

function confirmDelete(school) {
  $q.dialog({
    title: 'Confirm Delete',
    message: `Are you sure you want to delete "${school.name}"? This action cannot be undone.`,
    cancel: true,
    persistent: true,
  }).onOk(() => {
    deleteSchool(school);
  });
}

async function deleteSchool(school) {
  try {
    const response = await api.delete(`/schools/${school.id}`);

    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'School deleted successfully',
        position: 'top',
      });
      fetchSchools();
    }
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to delete school',
      position: 'top',
    });
  }
}

async function signInAsSchool(school) {
  $q.dialog({
    title: 'Sign In As School Admin',
    message: `You are about to sign in as the admin of "${school.name}". You will be able to manage this school's data.`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      const response = await api.post(`/auth/sign-in-as-school/${school.id}`);

      if (response.data.success) {
        const { user, token, roles, impersonating, original_user_id } = response.data.data;

        // Update auth store with impersonation info
        authStore.setAuth(user, token, roles, impersonating, original_user_id);

        $q.notify({
          type: 'positive',
          message: `Signed in as admin of ${school.name}`,
          position: 'top',
        });

        // Redirect to dashboard
        router.push('/app/dashboard');
      }
    } catch (error) {
      $q.notify({
        type: 'negative',
        message: error.response?.data?.message || 'Failed to sign in as school admin',
        position: 'top',
      });
    }
  });
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

