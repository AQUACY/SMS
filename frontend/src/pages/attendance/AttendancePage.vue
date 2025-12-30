<template>
  <q-page class="attendance-page">
    <!-- Mobile Header -->
    <div class="mobile-only">
      <MobilePageHeader
        title="Attendance"
        subtitle="View and manage attendance records"
      >
        <template #actions>
          <q-btn
            v-if="authStore.isTeacher || authStore.isSchoolAdmin || authStore.isSuperAdmin"
            flat
            round
            dense
            icon="check_circle"
            color="primary"
            to="/app/attendance/mark"
            class="q-mr-xs"
          >
            <q-tooltip>Mark Attendance</q-tooltip>
          </q-btn>
          <q-btn
            flat
            round
            dense
            icon="picture_as_pdf"
            color="negative"
            @click="showPdfDialog = true"
          >
            <q-tooltip>Generate PDF</q-tooltip>
          </q-btn>
        </template>
      </MobilePageHeader>
    </div>

    <!-- Desktop Header -->
    <div class="desktop-only q-pa-lg">
      <div class="row items-center justify-between q-mb-lg">
        <div>
          <div class="text-h5 text-weight-bold">Attendance</div>
          <div class="text-body2 text-grey-7">View and manage attendance records</div>
        </div>
        <div class="row q-gutter-sm">
          <q-btn
            v-if="authStore.isTeacher || authStore.isSchoolAdmin || authStore.isSuperAdmin"
            color="primary"
            label="Mark Attendance"
            icon="check_circle"
            unelevated
            to="/app/attendance/mark"
          />
          <q-btn
            color="negative"
            label="Generate PDF"
            icon="picture_as_pdf"
            unelevated
            @click="showPdfDialog = true"
          />
        </div>
      </div>
    </div>

    <div class="page-content">
      <!-- Filters -->
      <MobileCard variant="default" padding="md" class="q-mb-md">
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-3">
            <q-select
              v-model="filterClass"
              :options="classOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              label="Filter by Class"
              clearable
              @update:model-value="onFilter"
            />
          </div>
          <div class="col-12 col-md-3">
            <q-select
              v-model="filterTerm"
              :options="termOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              label="Filter by Term"
              clearable
              @update:model-value="onFilter"
            />
          </div>
          <div class="col-12 col-md-3">
            <q-input
              v-model="filterDate"
              label="Filter by Date"
              type="date"
              clearable
              @update:model-value="onFilter"
            />
          </div>
          <div class="col-12 col-md-3">
            <q-select
              v-model="filterStatus"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              label="Filter by Status"
              clearable
              @update:model-value="onFilter"
            />
          </div>
        </div>
      </MobileCard>

      <!-- Attendance Table -->
      <MobileCard variant="default" padding="md">
        <!-- Mobile View: Card List -->
        <div class="mobile-only">
          <div v-if="loading" class="text-center q-pa-xl">
            <q-spinner color="primary" size="3em" />
          </div>
          <div v-else-if="attendance.length === 0" class="empty-state">
            <q-icon name="event_busy" size="64px" color="grey-5" />
            <div class="empty-text">No Attendance Records</div>
            <div class="empty-subtext">No attendance records found for the selected filters.</div>
          </div>
          <div v-else class="attendance-list">
            <MobileListCard
              v-for="record in attendance"
              :key="record.id"
              :title="getStudentName(record.student)"
              :subtitle="formatDate(record.date)"
              :description="`${record.class?.name || 'N/A'} - ${record.term?.name || 'N/A'}`"
              icon="event"
              :badge="formatStatus(record.status)"
              :badge-color="getStatusColor(record.status)"
              :clickable="true"
              @click="viewDetails(record)"
            >
              <template #extra>
                <div class="card-actions">
                  <q-btn
                    flat
                    dense
                    round
                    icon="visibility"
                    size="sm"
                    @click.stop="viewDetails(record)"
                    class="q-mr-xs"
                  />
                  <q-btn
                    v-if="canEditAttendance(record)"
                    flat
                    dense
                    round
                    icon="edit"
                    size="sm"
                    color="primary"
                    @click.stop="editAttendance(record)"
                  />
                </div>
              </template>
            </MobileListCard>
          </div>
        </div>

        <!-- Desktop View: Table -->
        <div class="desktop-only">
          <q-table
          :rows="attendance"
          :columns="columns"
          :loading="loading"
          :pagination="pagination"
          @request="onRequest"
          row-key="id"
          flat
          class="attendance-table"
        >
          <template v-slot:body-cell-status="props">
            <q-td :props="props">
              <q-badge
                :color="getStatusColor(props.value)"
                :label="formatStatus(props.value)"
              />
            </q-td>
          </template>

          <template v-slot:body-cell-student="props">
            <q-td :props="props">
              {{ getStudentName(props.row.student) }}
            </q-td>
          </template>

          <template v-slot:body-cell-date="props">
            <q-td :props="props">
              {{ formatDate(props.value) }}
            </q-td>
          </template>

          <template v-slot:body-cell-actions="props">
            <q-td :props="props">
              <q-btn
                flat
                dense
                icon="visibility"
                @click="viewDetails(props.row)"
                size="sm"
                class="q-mr-xs"
              >
                <q-tooltip>View Details</q-tooltip>
              </q-btn>
              <q-btn
                v-if="canEditAttendance(props.row)"
                flat
                dense
                icon="edit"
                @click="editAttendance(props.row)"
                size="sm"
                color="primary"
              >
                <q-tooltip>Edit Attendance</q-tooltip>
              </q-btn>
            </q-td>
          </template>
          </q-table>
        </div>
      </MobileCard>
    </div>

    <!-- Edit Attendance Dialog -->
    <q-dialog v-model="showEditDialog" persistent>
      <q-card style="min-width: 400px">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Edit Attendance</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section v-if="editingAttendance">
          <div class="q-mb-md">
            <div class="text-body2 text-grey-7">Student</div>
            <div class="text-h6">{{ getStudentName(editingAttendance.student) }}</div>
          </div>
          <div class="q-mb-md">
            <div class="text-body2 text-grey-7">Date</div>
            <div class="text-body1">{{ formatDate(editingAttendance.date) }}</div>
          </div>
          <div class="q-mb-md">
            <div class="text-body2 text-grey-7">Class</div>
            <div class="text-body1">{{ editingAttendance.class?.name || 'N/A' }}</div>
          </div>
          <q-select
            v-model="editForm.status"
            :options="statusOptions"
            option-label="label"
            option-value="value"
            emit-value
            map-options
            label="Status *"
            class="q-mb-md"
          />
          <q-input
            v-model="editForm.remarks"
            label="Remarks"
            type="textarea"
            rows="3"
            outlined
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" color="grey" v-close-popup />
          <q-btn
            flat
            label="Update"
            color="primary"
            :loading="updating"
            @click="updateAttendance"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- PDF Generation Dialog -->
    <q-dialog v-model="showPdfDialog" persistent>
      <q-card style="min-width: 400px">
        <q-card-section class="row items-center q-pb-none">
          <div class="text-h6">Generate Attendance PDF</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <q-select
            v-model="pdfForm.class_id"
            :options="classOptions"
            option-label="label"
            option-value="value"
            emit-value
            map-options
            label="Class *"
            class="q-mb-md"
          />
          <q-select
            v-model="pdfForm.term_id"
            :options="termOptions"
            option-label="label"
            option-value="value"
            emit-value
            map-options
            label="Term *"
            class="q-mb-md"
          />
          <q-radio
            v-model="pdfForm.dateType"
            val="single"
            label="Single Date"
            class="q-mb-sm"
          />
          <q-radio
            v-model="pdfForm.dateType"
            val="range"
            label="Date Range"
            class="q-mb-sm"
          />
          <q-radio
            v-model="pdfForm.dateType"
            val="all"
            label="All Dates in Term"
            class="q-mb-md"
          />
          <q-input
            v-if="pdfForm.dateType === 'single'"
            v-model="pdfForm.date"
            label="Date"
            type="date"
            class="q-mb-md"
          />
          <div v-if="pdfForm.dateType === 'range'" class="row q-col-gutter-sm q-mb-md">
            <div class="col-6">
              <q-input
                v-model="pdfForm.start_date"
                label="Start Date"
                type="date"
              />
            </div>
            <div class="col-6">
              <q-input
                v-model="pdfForm.end_date"
                label="End Date"
                type="date"
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancel" color="grey" v-close-popup />
          <q-btn
            flat
            label="Preview"
            color="primary"
            icon="visibility"
            :loading="generatingPdf"
            @click="previewPdf"
          />
          <q-btn
            flat
            label="Download"
            color="negative"
            icon="download"
            :loading="generatingPdf"
            @click="downloadPdf"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'src/stores/auth';
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
import MobileListCard from 'src/components/mobile/MobileListCard.vue';
import api from 'src/services/api';

const router = useRouter();
const $q = useQuasar();
const authStore = useAuthStore();

const loading = ref(false);
const attendance = ref([]);
const classes = ref([]);
const terms = ref([]);
const filterClass = ref(null);
const filterTerm = ref(null);
const filterDate = ref(null);
const filterStatus = ref(null);
const showEditDialog = ref(false);
const editingAttendance = ref(null);
const updating = ref(false);
const editForm = ref({
  status: '',
  remarks: '',
});
const showPdfDialog = ref(false);
const generatingPdf = ref(false);
const pdfForm = ref({
  class_id: null,
  term_id: null,
  dateType: 'all',
  date: null,
  start_date: null,
  end_date: null,
});

const pagination = ref({
  page: 1,
  rowsPerPage: 15,
  rowsNumber: 0,
});

const statusOptions = [
  { label: 'Present', value: 'present' },
  { label: 'Absent', value: 'absent' },
  { label: 'Late', value: 'late' },
  { label: 'Excused', value: 'excused' },
];

const columns = [
  {
    name: 'date',
    label: 'Date',
    field: 'date',
    align: 'left',
    sortable: true,
  },
  {
    name: 'student',
    label: 'Student',
    field: 'student',
    align: 'left',
  },
  {
    name: 'class',
    label: 'Class',
    field: (row) => row.class?.name || 'N/A',
    align: 'left',
  },
  {
    name: 'term',
    label: 'Term',
    field: (row) => row.term?.name || 'N/A',
    align: 'left',
  },
  {
    name: 'status',
    label: 'Status',
    field: 'status',
    align: 'center',
  },
  {
    name: 'marked_by',
    label: 'Marked By',
    field: (row) => {
      if (row.marked_by) {
        return `${row.marked_by.first_name || ''} ${row.marked_by.last_name || ''}`.trim() || row.marked_by.email;
      }
      return 'N/A';
    },
    align: 'left',
  },
  {
    name: 'actions',
    label: 'Actions',
    field: 'actions',
    align: 'center',
  },
];

const getStatusColor = (status) => {
  const colors = {
    present: 'positive',
    absent: 'negative',
    late: 'warning',
    excused: 'info',
  };
  return colors[status] || 'grey';
};

const formatStatus = (status) => {
  return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown';
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  const d = new Date(date);
  return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
};

const getStudentName = (student) => {
  if (!student) return 'Unknown';
  const parts = [student.first_name, student.middle_name, student.last_name].filter(Boolean);
  return parts.join(' ') || 'Unknown';
};

const classOptions = computed(() => {
  return classes.value.map((cls) => ({
    label: cls.name,
    value: cls.id,
  }));
});

const termOptions = computed(() => {
  return terms.value.map((term) => ({
    label: term.name,
    value: term.id,
  }));
});

const fetchClasses = async () => {
  try {
    const response = await api.get('/classes', { params: { per_page: 100 } });
    if (response.data.success) {
      classes.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch classes:', error);
  }
};

const fetchTerms = async () => {
  try {
    const response = await api.get('/terms', { params: { per_page: 100 } });
    if (response.data.success) {
      terms.value = response.data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch terms:', error);
  }
};

const fetchAttendance = async () => {
  loading.value = true;
  try {
    const params = {
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
    };

    if (filterClass.value) {
      params.class_id = filterClass.value;
    }

    if (filterTerm.value) {
      params.term_id = filterTerm.value;
    }

    if (filterDate.value) {
      params.date = filterDate.value;
    }

    if (filterStatus.value) {
      params.status = filterStatus.value;
    }

    const response = await api.get('/attendance', { params });

    if (response.data.success) {
      attendance.value = response.data.data || [];
      if (response.data.meta) {
        pagination.value.rowsNumber = response.data.meta.total || 0;
        pagination.value.page = response.data.meta.current_page || 1;
      }
    }
  } catch (error) {
    console.error('Failed to fetch attendance:', error);
    $q.notify({
      type: 'negative',
      message: 'Failed to load attendance records. Please try again.',
      position: 'top',
    });
  } finally {
    loading.value = false;
  }
};

const onRequest = (props) => {
  pagination.value.page = props.pagination.page;
  pagination.value.rowsPerPage = props.pagination.rowsPerPage;
  fetchAttendance();
};

const onFilter = () => {
  pagination.value.page = 1;
  fetchAttendance();
};

const canEditAttendance = (record) => {
  // Super admins and school admins can always edit
  if (authStore.isSuperAdmin || authStore.isSchoolAdmin) {
    return true;
  }
  // Teachers can only edit attendance they marked
  if (authStore.isTeacher && record.marked_by && record.marked_by.id === authStore.user?.id) {
    return true;
  }
  return false;
};

const viewDetails = (record) => {
  if (record.student_id) {
    router.push(`/app/students/${record.student_id}`);
  }
};

const editAttendance = (record) => {
  editingAttendance.value = record;
  editForm.value = {
    status: record.status,
    remarks: record.remarks || '',
  };
  showEditDialog.value = true;
};

const updateAttendance = async () => {
  if (!editingAttendance.value) return;

  updating.value = true;
  try {
    const response = await api.put(`/attendance/${editingAttendance.value.id}`, editForm.value);
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: 'Attendance updated successfully',
        position: 'top',
      });
      showEditDialog.value = false;
      editingAttendance.value = null;
      // Refresh the attendance list
      fetchAttendance();
    }
  } catch (error) {
    console.error('Failed to update attendance:', error);
    $q.notify({
      type: 'negative',
      message: error.response?.data?.message || 'Failed to update attendance. Please try again.',
      position: 'top',
    });
  } finally {
    updating.value = false;
  }
};

const validatePdfForm = () => {
  if (!pdfForm.value.class_id || !pdfForm.value.term_id) {
    $q.notify({
      type: 'warning',
      message: 'Please select class and term',
      position: 'top',
    });
    return false;
  }

  if (pdfForm.value.dateType === 'single' && !pdfForm.value.date) {
    $q.notify({
      type: 'warning',
      message: 'Please select a date',
      position: 'top',
    });
    return false;
  }

  if (pdfForm.value.dateType === 'range' && (!pdfForm.value.start_date || !pdfForm.value.end_date)) {
    $q.notify({
      type: 'warning',
      message: 'Please select start and end dates',
      position: 'top',
    });
    return false;
  }

  return true;
};

const buildPdfUrl = (action) => {
  const baseURL = api.defaults.baseURL || 'http://localhost:8000/api';
  const params = new URLSearchParams({
    class_id: pdfForm.value.class_id,
    term_id: pdfForm.value.term_id,
    action: action,
  });

  if (pdfForm.value.dateType === 'single') {
    params.append('date', pdfForm.value.date);
  } else if (pdfForm.value.dateType === 'range') {
    params.append('start_date', pdfForm.value.start_date);
    params.append('end_date', pdfForm.value.end_date);
  }

  return `${baseURL}/attendance/pdf?${params.toString()}`;
};

const previewPdf = async () => {
  if (!validatePdfForm()) return;

  generatingPdf.value = true;
  
  try {
    const token = authStore.token;
    let url = buildPdfUrl('preview');
    
    // Add token to URL for authentication (temporary for preview only)
    // This allows us to open the URL directly in a new tab
    url += `&token=${encodeURIComponent(token)}`;

    // Open the backend URL directly in a new tab
    // The backend will set Content-Disposition: inline for preview
    const newWindow = window.open(url, '_blank');
    
    if (!newWindow) {
      throw new Error('Popup blocked. Please allow popups for this site to preview PDFs.');
    }

    $q.notify({
      type: 'positive',
      message: 'PDF opened in new tab',
      position: 'top',
    });

    showPdfDialog.value = false;
  } catch (error) {
    console.error('Failed to preview PDF:', error);
    $q.notify({
      type: 'negative',
      message: error.message || 'Failed to preview PDF. Please try again.',
      position: 'top',
    });
  } finally {
    generatingPdf.value = false;
  }
};

const downloadPdf = async () => {
  if (!validatePdfForm()) return;

  generatingPdf.value = true;
  
  try {
    const token = authStore.token;
    const url = buildPdfUrl('download');

    // For download, fetch and trigger download immediately
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/pdf',
      },
    });

    if (response.ok) {
      const blob = await response.blob();
      const blobUrl = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = blobUrl;
      link.download = `attendance_sheet_${pdfForm.value.class_id}_${pdfForm.value.term_id}_${new Date().toISOString().split('T')[0]}.pdf`;
      document.body.appendChild(link);
      link.click();
      link.remove();
      
      // Clean up after download
      setTimeout(() => {
        window.URL.revokeObjectURL(blobUrl);
      }, 100);

      $q.notify({
        type: 'positive',
        message: 'PDF downloaded successfully',
        position: 'top',
      });

      showPdfDialog.value = false;
    } else {
      throw new Error('Failed to generate PDF');
    }
  } catch (error) {
    console.error('Failed to download PDF:', error);
    $q.notify({
      type: 'negative',
      message: error.message || 'Failed to download PDF. Please try again.',
      position: 'top',
    });
  } finally {
    generatingPdf.value = false;
  }
};

onMounted(() => {
  fetchClasses();
  fetchTerms();
  fetchAttendance();
});
</script>

<script>
export default {
  name: 'AttendancePage',
};
</script>

<style lang="scss" scoped>
.attendance-page {
  padding: 0;
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.mobile-only {
  display: block;
  
  @media (min-width: 768px) {
    display: none;
  }
}

.desktop-only {
  display: none;
  
  @media (min-width: 768px) {
    display: block;
  }
}

.page-content {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: 0;
  }
}

.attendance-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.card-actions {
  display: flex;
  gap: var(--spacing-xs);
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
  font-size: var(--font-size-lg);
  font-weight: 600;
  color: var(--text-primary);
  margin-top: var(--spacing-md);
}

.empty-subtext {
  font-size: var(--font-size-base);
  color: var(--text-secondary);
  margin-top: var(--spacing-sm);
}

.attendance-table {
  :deep(.q-table__top) {
    padding: 0;
  }
}
</style>
