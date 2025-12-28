<template>
  <div class="row q-col-gutter-md">
    <!-- Assigned Classes -->
    <div class="col-12 col-md-6">
      <q-card class="widget-card">
        <q-card-section>
          <div class="row items-center justify-between q-mb-md">
            <div class="text-h6 text-weight-bold">My Classes</div>
            <q-btn flat dense label="View All" color="primary" size="sm" to="/app/classes" />
          </div>
          <q-list separator v-if="assignedClasses && assignedClasses.length > 0">
            <q-item 
              v-for="classItem in assignedClasses" 
              :key="classItem.id" 
              clickable 
              :to="`/app/classes/${classItem.id}`"
            >
              <q-item-section avatar>
                <q-icon name="class" color="primary" size="32px" />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ classItem.name }}</q-item-label>
                <q-item-label caption>{{ classItem.level }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-badge color="primary" :label="`${classItem.student_count} students`" />
              </q-item-section>
            </q-item>
          </q-list>
          <div v-else class="text-center q-pa-md text-grey-6">
            No classes assigned
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- Today's Attendance -->
    <div v-if="todayAttendance" class="col-12 col-md-6">
      <q-card class="widget-card">
        <q-card-section>
          <div class="text-h6 text-weight-bold q-mb-md">Today's Attendance</div>
          <div class="row q-col-gutter-sm">
            <div class="col-6">
              <div class="text-center">
                <div class="text-h4 text-weight-bold text-green">{{ todayAttendance.present || 0 }}</div>
                <div class="text-caption text-grey-7">Present</div>
              </div>
            </div>
            <div class="col-6">
              <div class="text-center">
                <div class="text-h4 text-weight-bold text-red">{{ todayAttendance.absent || 0 }}</div>
                <div class="text-caption text-grey-7">Absent</div>
              </div>
            </div>
          </div>
          <q-btn 
            flat 
            dense 
            label="Mark Attendance" 
            color="primary" 
            size="sm" 
            class="full-width q-mt-md"
            to="/app/attendance"
          />
        </q-card-section>
      </q-card>
    </div>

    <!-- Recent Assessments -->
    <div class="col-12 col-lg-6">
      <q-card class="widget-card">
        <q-card-section>
          <div class="row items-center justify-between q-mb-md">
            <div class="text-h6 text-weight-bold">Recent Assessments</div>
            <q-btn flat dense label="View All" color="primary" size="sm" to="/app/assessments" />
          </div>
          <q-list separator v-if="recentAssessments && recentAssessments.length > 0">
            <q-item 
              v-for="assessment in recentAssessments" 
              :key="assessment.id" 
              clickable 
              :to="`/app/assessments/${assessment.id}`"
            >
              <q-item-section avatar>
                <q-icon 
                  :name="assessment.is_finalized ? 'check_circle' : 'edit'" 
                  :color="assessment.is_finalized ? 'positive' : 'warning'" 
                  size="32px" 
                />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ assessment.name }}</q-item-label>
                <q-item-label caption>
                  {{ assessment.class_subject?.class?.name || 'N/A' }} - 
                  {{ assessment.class_subject?.subject?.name || 'N/A' }}
                </q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-badge 
                  :color="assessment.is_finalized ? 'positive' : 'warning'" 
                  :label="assessment.is_finalized ? 'Finalized' : 'Pending'" 
                />
              </q-item-section>
            </q-item>
          </q-list>
          <div v-else class="text-center q-pa-md text-grey-6">
            No assessments yet
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- Active Term -->
    <div v-if="activeTerm" class="col-12 col-lg-6">
      <q-card class="widget-card">
        <q-card-section>
          <div class="text-h6 text-weight-bold q-mb-md">Active Term</div>
          <div class="text-h5 q-mb-sm">{{ activeTerm.name }}</div>
          <div class="text-caption text-grey-7">Current academic term</div>
          <q-btn 
            flat 
            dense 
            label="View Term Details" 
            color="primary" 
            size="sm" 
            class="full-width q-mt-md"
            :to="`/app/terms/${activeTerm.id}`"
          />
        </q-card-section>
      </q-card>
    </div>

    <!-- Quick Actions -->
    <div class="col-12">
      <q-card class="widget-card">
        <q-card-section>
          <div class="text-h6 text-weight-bold q-mb-md">Quick Actions</div>
          <div class="row q-gutter-sm">
            <q-btn color="primary" label="Mark Attendance" icon="checklist" unelevated to="/app/attendance" />
            <q-btn color="secondary" label="Create Assessment" icon="edit" unelevated to="/app/assessments" />
            <q-btn color="accent" label="Enter Results" icon="assessment" unelevated to="/app/results" />
            <q-btn color="positive" label="My Classes" icon="class" unelevated to="/app/classes" />
          </div>
        </q-card-section>
      </q-card>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({}),
  },
});

const assignedClasses = computed(() => props.stats.assigned_classes_list || []);
const todayAttendance = computed(() => props.stats.today_attendance);
const recentAssessments = computed(() => props.stats.recent_assessments || []);
const activeTerm = computed(() => props.stats.active_term);
</script>

<style lang="scss" scoped>
.widget-card {
  border-radius: 16px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}
</style>

