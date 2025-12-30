<template>
  <q-page class="users-list-page">
    <MobilePageHeader
      title="User Management"
      subtitle="Manage accounts managers and teachers"
    >
      <template v-slot:actions>
        <q-btn
          color="primary"
          :label="$q.screen.gt.xs ? 'Add User' : ''"
          icon="add"
          unelevated
          @click="showCreateDialog = true"
          class="mobile-btn"
        />
      </template>
    </MobilePageHeader>

    <!-- Filters -->
    <MobileCard variant="default" padding="md" class="filters-card">
      <div class="filters-grid">
        <q-select
          v-model="filters.role"
          :options="roleOptions"
          option-label="label"
          option-value="value"
          emit-value
          map-options
          label="Role"
          outlined
          clearable
          dense
          class="filter-item"
        />
        <q-select
          v-model="filters.is_active"
          :options="statusOptions"
          option-label="label"
          option-value="value"
          emit-value
          map-options
          label="Status"
          outlined
          clearable
          dense
          class="filter-item"
        />
        <q-input
          v-model="filters.search"
          label="Search"
          outlined
          dense
          clearable
          placeholder="Name, email, phone..."
          class="filter-item"
        >
          <template v-slot:prepend>
            <q-icon name="search" />
          </template>
        </q-input>
      </div>
    </MobileCard>

    <!-- Mobile Card View -->
    <div class="mobile-list-view">
      <div v-if="loading" class="loading-cards">
        <q-card v-for="i in 3" :key="i" class="mobile-list-card">
          <q-card-section>
            <q-skeleton type="rect" height="100px" />
          </q-card-section>
        </q-card>
      </div>
      
      <div v-else-if="users.length > 0" class="cards-container">
        <MobileListCard
          v-for="user in users"
          :key="user.id"
          :title="`${user.first_name} ${user.last_name}`"
          :subtitle="user.email"
          :description="getUserDescription(user)"
          icon="person"
          :badge="user.is_active ? 'Active' : 'Inactive'"
          :badge-color="user.is_active ? 'positive' : 'negative'"
          icon-bg="rgba(156, 39, 176, 0.1)"
          @click="editUser(user)"
        >
          <template v-slot:extra>
            <div class="user-roles">
              <q-chip
                v-for="role in user.roles"
                :key="role.id"
                :color="getRoleColor(role.name)"
                :label="role.display_name || role.name"
                size="sm"
                class="q-mr-xs q-mb-xs"
              />
            </div>
            <div class="card-actions">
              <q-btn
                flat
                dense
                icon="edit"
                color="primary"
                label="Edit"
                @click.stop="editUser(user)"
                size="sm"
              />
              <q-btn
                v-if="user.id !== currentUserId"
                flat
                dense
                icon="delete"
                color="negative"
                label="Delete"
                @click.stop="confirmDelete(user)"
                size="sm"
              />
            </div>
          </template>
        </MobileListCard>
      </div>
      
      <div v-else class="empty-state">
        <q-icon name="person" size="64px" color="grey-5" />
        <div class="empty-text">No users found</div>
      </div>
    </div>

    <!-- Desktop Table View -->
    <div class="desktop-table-view">
      <q-card>
        <q-table
          :rows="users"
          :columns="columns"
          :loading="loading"
          :pagination="pagination"
          @request="onRequest"
          row-key="id"
          flat
        >
        <template v-slot:body-cell-roles="props">
          <q-td :props="props">
            <q-badge
              v-for="role in props.row.roles"
              :key="role.id"
              :color="getRoleColor(role.name)"
              :label="role.display_name || role.name"
              class="q-mr-xs"
            />
          </q-td>
        </template>

        <template v-slot:body-cell-status="props">
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
              round
              dense
              icon="edit"
              color="primary"
              @click="editUser(props.row)"
              class="q-mr-xs"
            >
              <q-tooltip>Edit</q-tooltip>
            </q-btn>
            <q-btn
              v-if="props.row.id !== currentUserId"
              flat
              round
              dense
              icon="delete"
              color="negative"
              @click="confirmDelete(props.row)"
            >
              <q-tooltip>Delete</q-tooltip>
            </q-btn>
          </q-td>
        </template>
      </q-table>
      </q-card>
    </div>

    <!-- Create/Edit User Dialog -->
    <q-dialog v-model="showCreateDialog" persistent>
      <q-card style="min-width: 500px; max-width: 600px">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">{{ isEdit ? 'Edit User' : 'Add User' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <q-form @submit="saveUser" class="q-gutter-md">
            <div class="row q-gutter-md">
              <div class="col-12 col-md-6">
                <q-input
                  v-model="userForm.first_name"
                  label="First Name *"
                  outlined
                  :rules="[(val) => !!val || 'First name is required']"
                />
              </div>
              <div class="col-12 col-md-6">
                <q-input
                  v-model="userForm.last_name"
                  label="Last Name *"
                  outlined
                  :rules="[(val) => !!val || 'Last name is required']"
                />
              </div>
            </div>

            <q-input
              v-model="userForm.email"
              label="Email *"
              type="email"
              outlined
              :rules="[
                (val) => !!val || 'Email is required',
                (val) => /.+@.+\..+/.test(val) || 'Email must be valid',
              ]"
              :disable="isEdit"
            />

            <q-input
              v-model="userForm.phone"
              label="Phone"
              outlined
            />

            <q-select
              v-model="userForm.role"
              :options="roleOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              label="Role *"
              outlined
              :rules="[(val) => !!val || 'Role is required']"
              hint="Select accounts manager or teacher"
            />

            <q-input
              v-model="userForm.password"
              :label="isEdit ? 'Password (leave blank to keep current)' : 'Password *'"
              type="password"
              outlined
              :rules="[
                (val) => {
                  if (isEdit) {
                    // For edit: password is optional, but if provided, must be at least 8 chars
                    return !val || val.length >= 8 || 'Password must be at least 8 characters';
                  } else {
                    // For new: password is required and must be at least 8 chars
                    if (!val) return 'Password is required';
                    if (val.length < 8) return 'Password must be at least 8 characters';
                    return true;
                  }
                },
              ]"
              :hint="isEdit ? 'Leave blank to keep current password' : 'Minimum 8 characters'"
            />

            <q-toggle
              v-model="userForm.is_active"
              label="Active"
            />

            <div class="row q-mt-md">
              <q-space />
              <q-btn
                flat
                label="Cancel"
                color="grey"
                v-close-popup
                class="q-mr-sm"
              />
              <q-btn
                type="submit"
                :label="isEdit ? 'Update User' : 'Create User'"
                color="primary"
                unelevated
                :loading="submitting"
              />
            </div>
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'src/stores/auth';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import MobileListCard from 'src/components/mobile/MobileListCard.vue';
import api from 'src/services/api';

const $q = useQuasar();
const authStore = useAuthStore();

const loading = ref(false);
const submitting = ref(false);
const users = ref([]);
const showCreateDialog = ref(false);
const isEdit = ref(false);
const editingUser = ref(null);

const filters = ref({
  role: null,
  is_active: null,
  search: '',
});

const userForm = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  role: 'accounts_manager',
  password: '',
  is_active: true,
});

const pagination = ref({
  page: 1,
  rowsPerPage: 15,
  rowsNumber: 0,
});

const roleOptions = [
  { label: 'Accounts Manager', value: 'accounts_manager' },
  { label: 'Teacher', value: 'teacher' },
];

const statusOptions = [
  { label: 'Active', value: true },
  { label: 'Inactive', value: false },
];

const columns = [
  {
    name: 'name',
    label: 'Name',
    field: (row) => `${row.first_name} ${row.last_name}`,
    align: 'left',
    sortable: true,
  },
  {
    name: 'email',
    label: 'Email',
    field: 'email',
    align: 'left',
    sortable: true,
  },
  {
    name: 'phone',
    label: 'Phone',
    field: 'phone',
    align: 'left',
  },
  {
    name: 'roles',
    label: 'Role',
    align: 'left',
  },
  {
    name: 'status',
    label: 'Status',
    field: 'is_active',
    align: 'center',
  },
  {
    name: 'created_at',
    label: 'Created',
    field: 'created_at',
    align: 'left',
    format: (val) => new Date(val).toLocaleDateString(),
    sortable: true,
  },
  {
    name: 'actions',
    label: 'Actions',
    align: 'center',
  },
];

const currentUserId = computed(() => authStore.user?.id);

onMounted(() => {
  fetchUsers();
});

function getRoleColor(roleName) {
  const colors = {
    accounts_manager: 'purple',
    teacher: 'blue',
    school_admin: 'green',
  };
  return colors[roleName] || 'grey';
}

async function fetchUsers() {
  loading.value = true;
  try {
    const params = {
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
    };
    
    if (filters.value.role) params.role = filters.value.role;
    if (filters.value.is_active !== null) params.is_active = filters.value.is_active;
    if (filters.value.search) params.search = filters.value.search;
    
    const response = await api.get('/users', { params });
    if (response.data.success) {
      users.value = response.data.data || [];
      pagination.value.rowsNumber = response.data.meta?.total || 0;
    }
  } catch (error) {
    console.error('Failed to fetch users:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to fetch users',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
}

function onRequest(props) {
  pagination.value = props.pagination;
  fetchUsers();
}

function editUser(user) {
  isEdit.value = true;
  editingUser.value = user;
  userForm.value = {
    first_name: user.first_name,
    last_name: user.last_name,
    email: user.email,
    phone: user.phone || '',
    role: user.roles?.[0]?.name || 'accounts_manager',
    password: '',
    is_active: user.is_active,
  };
  showCreateDialog.value = true;
}

function resetForm() {
  isEdit.value = false;
  editingUser.value = null;
  userForm.value = {
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    role: 'accounts_manager',
    password: '',
    is_active: true,
  };
}

async function saveUser() {
  submitting.value = true;
  try {
    const data = { ...userForm.value };
    
    // Remove password if empty (for edit)
    if (isEdit.value && !data.password) {
      delete data.password;
    }
    
    let response;
    if (isEdit.value) {
      response = await api.put(`/users/${editingUser.value.id}`, data);
    } else {
      response = await api.post('/users', data);
    }
    
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: isEdit.value ? 'User updated successfully' : 'User created successfully',
        position: 'top',
      });
      showCreateDialog.value = false;
      resetForm();
      fetchUsers();
    }
  } catch (error) {
    console.error('Failed to save user:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to save user',
      position: 'top',
    });
  } finally {
    submitting.value = false;
  }
}

function confirmDelete(user) {
  $q.dialog({
    title: 'Confirm Delete',
    message: `Are you sure you want to delete ${user.first_name} ${user.last_name}? This action cannot be undone.`,
    cancel: true,
    persistent: true,
  }).onOk(() => {
    deleteUser(user);
  });
}

async function deleteUser(user) {
  try {
    const response = await api.delete(`/users/${user.id}`);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'User deleted successfully',
        position: 'top',
      });
      fetchUsers();
    }
  } catch (error) {
    console.error('Failed to delete user:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to delete user',
      position: 'top',
    });
  }
}

function getUserDescription(user) {
  const parts = [];
  if (user.phone) {
    parts.push(`Phone: ${user.phone}`);
  }
  if (user.roles && user.roles.length > 0) {
    const roleNames = user.roles.map(r => r.display_name || r.name).join(', ');
    parts.push(`Roles: ${roleNames}`);
  }
  return parts.join(' • ') || 'No additional details';
}

// Watch filters and refetch
watch([() => filters.value.role, () => filters.value.is_active, () => filters.value.search], () => {
  pagination.value.page = 1;
  fetchUsers();
});
</script>

<style lang="scss" scoped>
.users-list-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.filters-card {
  margin-bottom: var(--spacing-md);
}

.filters-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--spacing-md);
  
  @media (min-width: 600px) {
    grid-template-columns: repeat(2, 1fr);
  }
  
  @media (min-width: 960px) {
    grid-template-columns: repeat(3, 1fr);
  }
}

.filter-item {
  width: 100%;
}

.mobile-list-view {
  display: block;
  
  @media (min-width: 960px) {
    display: none;
  }
}

.desktop-table-view {
  display: none;
  
  @media (min-width: 960px) {
    display: block;
  }
}

.loading-cards {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.cards-container {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.user-roles {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-xs);
  margin-bottom: var(--spacing-sm);
}

.card-actions {
  display: flex;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-2xl);
  text-align: center;
}

.empty-text {
  font-size: var(--font-size-base);
  color: var(--text-secondary);
  margin-top: var(--spacing-md);
}

.mobile-btn {
  @media (max-width: 599px) {
    min-width: 0;
    padding: var(--spacing-sm);
  }
}
</style>

