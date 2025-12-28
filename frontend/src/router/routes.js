const routes = [
  // Authentication routes with AuthLayout (no sidebar/header)
  {
    path: '/',
    component: () => import('src/layouts/AuthLayout.vue'),
    children: [
      {
        path: '',
        name: 'home',
        component: () => import('src/pages/IndexPage.vue'),
        meta: { requiresAuth: false },
      },
      {
        path: 'login',
        name: 'login',
        component: () => import('src/pages/LoginPage.vue'),
        meta: { guestOnly: true },
      },
      {
        path: 'register',
        name: 'register',
        component: () => import('src/pages/RegisterPage.vue'),
        meta: { guestOnly: true },
      },
    ],
  },

  // Main application routes with MainLayout (with sidebar/header)
  {
    path: '/app',
    component: () => import('src/layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        redirect: '/app/dashboard',
      },
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('src/pages/DashboardPage.vue'),
        meta: { requiresAuth: true },
      },

      // Super Admin Routes
      {
        path: 'super-admin/schools',
        name: 'super-admin-schools',
        component: () => import('src/pages/super-admin/SchoolsListPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin'] },
      },
      {
        path: 'super-admin/schools/:id',
        name: 'super-admin-school-detail',
        component: () => import('src/pages/super-admin/SchoolDetailPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin'] },
      },

      // Student Management Module
      {
        path: 'students',
        name: 'students',
        component: () => import('src/pages/students/StudentsListPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin', 'teacher'] },
      },
      {
        path: 'students/create',
        name: 'students-create',
        component: () => import('src/pages/students/StudentCreatePage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin'] },
      },
      {
        path: 'students/:id',
        name: 'student-detail',
        component: () => import('src/pages/students/StudentDetailPage.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'students/:id/edit',
        name: 'student-edit',
        component: () => import('src/pages/students/StudentEditPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin'] },
      },

      // Teacher Management Module
      {
        path: 'teachers',
        name: 'teachers',
        component: () => import('src/pages/teachers/TeachersListPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin'] },
      },
      {
        path: 'teachers/create',
        name: 'teachers-create',
        component: () => import('src/pages/teachers/TeacherCreatePage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin'] },
      },
      {
        path: 'teachers/:id',
        name: 'teacher-detail',
        component: () => import('src/pages/teachers/TeacherDetailPage.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'teachers/:id/edit',
        name: 'teacher-edit',
        component: () => import('src/pages/teachers/TeacherEditPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin'] },
      },

      // Class Management Module
      {
        path: 'classes',
        name: 'classes',
        component: () => import('src/pages/classes/ClassesListPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin', 'teacher'] },
      },
      {
        path: 'classes/create',
        name: 'classes-create',
        component: () => import('src/pages/classes/ClassCreatePage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin'] },
      },
      {
        path: 'classes/:id',
        name: 'class-detail',
        component: () => import('src/pages/classes/ClassDetailPage.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'classes/:id/edit',
        name: 'class-edit',
        component: () => import('src/pages/classes/ClassEditPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin'] },
      },
      {
        path: 'classes/:id/students',
        name: 'class-students',
        component: () => import('src/pages/classes/ClassStudentsPage.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'classes/:id/subjects',
        name: 'class-subjects',
        component: () => import('src/pages/classes/ClassSubjectsPage.vue'),
        meta: { requiresAuth: true },
      },

      // Subject Management Module
      {
        path: 'subjects',
        name: 'subjects',
        component: () => import('src/pages/subjects/SubjectsListPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin'] },
      },
      {
        path: 'subjects/create',
        name: 'subjects-create',
        component: () => import('src/pages/subjects/SubjectCreatePage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin'] },
      },
      {
        path: 'subjects/:id',
        name: 'subject-detail',
        component: () => import('src/pages/subjects/SubjectDetailPage.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'subjects/:id/edit',
        name: 'subject-edit',
        component: () => import('src/pages/subjects/SubjectEditPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin'] },
      },

      // Attendance Management Module
      {
        path: 'attendance',
        name: 'attendance',
        component: () => import('src/pages/attendance/AttendancePage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin', 'teacher'] },
      },
      {
        path: 'attendance/mark',
        name: 'attendance-mark',
        component: () => import('src/pages/attendance/MarkAttendancePage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin', 'teacher'] },
      },
      {
        path: 'attendance/reports',
        name: 'attendance-reports',
        component: () => import('src/pages/attendance/AttendanceReportsPage.vue'),
        meta: { requiresAuth: true },
      },

      // Exams & Assessments Module
      {
        path: 'exams',
        name: 'exams',
        component: () => import('src/pages/exams/ExamsListPage.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'exams/create',
        name: 'exams-create',
        component: () => import('src/pages/exams/ExamCreatePage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin', 'teacher'] },
      },
      {
        path: 'exams/:id',
        name: 'exam-detail',
        component: () => import('src/pages/exams/ExamDetailPage.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'assessments',
        name: 'assessments',
        component: () => import('src/pages/exams/AssessmentsListPage.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'assessments/create',
        name: 'assessments-create',
        component: () => import('src/pages/exams/AssessmentCreatePage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin', 'teacher'] },
      },
      {
        path: 'assessments/:id',
        name: 'assessment-detail',
        component: () => import('src/pages/exams/AssessmentDetailPage.vue'),
        meta: { requiresAuth: true },
      },

      // Results Module
      {
        path: 'results',
        name: 'results',
        component: () => import('src/pages/results/ResultsPage.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'results/enter',
        name: 'results-enter',
        component: () => import('src/pages/results/EnterResultsPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin', 'teacher'] },
      },
      {
        path: 'results/:studentId',
        name: 'student-results',
        component: () => import('src/pages/results/StudentResultsPage.vue'),
        meta: { requiresAuth: true },
      },

      // Report Cards Module
      {
        path: 'report-cards',
        name: 'report-cards',
        component: () => import('src/pages/report-cards/ReportCardsPage.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'report-cards/generate',
        name: 'report-cards-generate',
        component: () => import('src/pages/report-cards/GenerateReportCardPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin', 'teacher'] },
      },
      {
        path: 'report-cards/:studentId/:termId',
        name: 'report-card-view',
        component: () => import('src/pages/report-cards/ReportCardViewPage.vue'),
        meta: { requiresAuth: true },
      },

      // Term Management Module
      {
        path: 'terms',
        name: 'terms',
        component: () => import('src/pages/terms/TermsListPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin'] },
      },
      {
        path: 'terms/create',
        name: 'terms-create',
        component: () => import('src/pages/terms/TermCreatePage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin'] },
      },
      {
        path: 'terms/:id',
        name: 'term-detail',
        component: () => import('src/pages/terms/TermDetailPage.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'terms/:id/edit',
        name: 'term-edit',
        component: () => import('src/pages/terms/TermEditPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin'] },
      },
      {
        path: 'academic-years',
        name: 'academic-years',
        component: () => import('src/pages/terms/AcademicYearsPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin'] },
      },
      {
        path: 'academic-years/create',
        name: 'academic-years-create',
        component: () => import('src/pages/terms/AcademicYearCreatePage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin'] },
      },
      {
        path: 'academic-years/:id',
        name: 'academic-year-detail',
        component: () => import('src/pages/terms/AcademicYearDetailPage.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'academic-years/:id/edit',
        name: 'academic-year-edit',
        component: () => import('src/pages/terms/AcademicYearEditPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin'] },
      },

      // Parent Portal Module
      {
        path: 'parent/link-child',
        name: 'parent-link-child',
        component: () => import('src/pages/parent/LinkChildPage.vue'),
        meta: { requiresAuth: true, roles: ['parent'] },
      },
      {
        path: 'parent/children',
        name: 'parent-children',
        component: () => import('src/pages/parent/MyChildrenPage.vue'),
        meta: { requiresAuth: true, roles: ['parent'] },
      },
      {
        path: 'parent/children/:id',
        name: 'parent-child-detail',
        component: () => import('src/pages/parent/ChildDetailPage.vue'),
        meta: { requiresAuth: true, roles: ['parent'] },
      },
      {
        path: 'parent/subscriptions',
        name: 'parent-subscriptions',
        component: () => import('src/pages/parent/SubscriptionsPage.vue'),
        meta: { requiresAuth: true, roles: ['parent'] },
      },
      {
        path: 'parent/payments',
        name: 'parent-payments',
        component: () => import('src/pages/parent/PaymentsPage.vue'),
        meta: { requiresAuth: true, roles: ['parent'] },
      },
      {
        path: 'parent/payment/:studentId/:termId?',
        name: 'parent-payment',
        component: () => import('src/pages/parent/PaymentPage.vue'),
        meta: { requiresAuth: true, roles: ['parent'] },
      },
      {
        path: 'parent/verify-payment',
        name: 'parent-verify-payment',
        component: () => import('src/pages/parent/VerifyPaymentPage.vue'),
        meta: { requiresAuth: true, roles: ['parent'] },
      },
      {
        path: 'parent/children/:id/attendance',
        name: 'parent-child-attendance',
        component: () => import('src/pages/parent/ChildAttendancePage.vue'),
        meta: { requiresAuth: true, roles: ['parent'] },
      },
      {
        path: 'parent/children/:id/results',
        name: 'parent-child-results',
        component: () => import('src/pages/parent/ChildResultsPage.vue'),
        meta: { requiresAuth: true, roles: ['parent'] },
      },
      {
        path: 'parent/children/:id/report-cards',
        name: 'parent-child-report-cards',
        component: () => import('src/pages/parent/ChildReportCardsPage.vue'),
        meta: { requiresAuth: true, roles: ['parent'] },
      },
      {
        path: 'parent/children/:id/assessments',
        name: 'parent-child-assessments',
        component: () => import('src/pages/parent/ChildAssessmentsPage.vue'),
        meta: { requiresAuth: true, roles: ['parent'] },
      },

      // Subscription & Payments Module (Admin)
      {
        path: 'subscriptions',
        name: 'subscriptions',
        component: () => import('src/pages/subscriptions/SubscriptionsListPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin'] },
      },
      {
        path: 'payments',
        name: 'payments',
        component: () => import('src/pages/payments/PaymentsListPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin', 'accounts_manager'] },
      },
      {
        path: 'payments/:id',
        name: 'payment-detail',
        component: () => import('src/pages/payments/PaymentDetailPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin', 'accounts_manager'] },
      },
      
      // Fees Management Module (Admin)
      {
        path: 'fees',
        name: 'fees',
        component: () => import('src/pages/fees/FeesListPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin', 'accounts_manager'] },
      },
      
      // User Management Module (School Admin only)
      {
        path: 'users',
        name: 'users',
        component: () => import('src/pages/users/UsersListPage.vue'),
        meta: { requiresAuth: true, roles: ['school_admin'] },
      },
      {
        path: 'fees/create',
        name: 'fees-create',
        component: () => import('src/pages/fees/FeeCreatePage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin', 'accounts_manager'] },
      },
      {
        path: 'fees/:id/edit',
        name: 'fees-edit',
        component: () => import('src/pages/fees/FeeCreatePage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin', 'accounts_manager'] },
      },
      
      // Subscription Prices Management (Super Admin only)
      {
        path: 'subscription-prices',
        name: 'subscription-prices',
        component: () => import('src/pages/subscription-prices/SubscriptionPricesListPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin'] },
      },
      {
        path: 'subscription-prices/create',
        name: 'subscription-prices-create',
        component: () => import('src/pages/subscription-prices/SubscriptionPriceCreatePage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin'] },
      },
      {
        path: 'subscription-prices/:id/edit',
        name: 'subscription-prices-edit',
        component: () => import('src/pages/subscription-prices/SubscriptionPriceCreatePage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin'] },
      },

      // Notifications Module
      {
        path: 'notifications',
        name: 'notifications',
        component: () => import('src/pages/notifications/NotificationsPage.vue'),
        meta: { requiresAuth: true },
      },

      // Profile & Settings
      {
        path: 'profile',
        name: 'profile',
        component: () => import('src/pages/profile/ProfilePage.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: 'settings',
        name: 'settings',
        component: () => import('src/pages/settings/SettingsPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin'] },
      },
      {
        path: 'settings/grading-scales',
        name: 'grading-scales',
        component: () => import('src/pages/settings/GradingScalesPage.vue'),
        meta: { requiresAuth: true, roles: ['super_admin', 'school_admin'] },
      },
    ],
  },

  // Redirect /dashboard to /app/dashboard for convenience
  {
    path: '/dashboard',
    redirect: '/app/dashboard',
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('src/pages/ErrorNotFound.vue'),
  },
];

export default routes;

