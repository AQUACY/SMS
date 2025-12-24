# School Management System - Database Schema (ERD)

## Overview
This document defines the complete database schema for a multi-school management system with parent subscription monetization.

## Core Principles
- **Multi-school isolation**: Each school has its own database
- **Term lifecycle**: Draft → Active → Closing (Grace Period) → Closed → Archived
- **Parent subscriptions**: Per student, per term, via Mobile Money
- **RBAC**: Enforced at database and API levels

---

## Entity Relationship Diagram

### 1. SCHOOL & ACADEMIC STRUCTURE

#### `schools`
- `id` (PK, BIGINT, UNSIGNED, AUTO_INCREMENT)
- `name` (VARCHAR 255)
- `code` (VARCHAR 50, UNIQUE) - Unique school identifier
- `domain` (VARCHAR 255, UNIQUE) - School's custom domain
- `logo` (VARCHAR 255, NULLABLE)
- `address` (TEXT, NULLABLE)
- `phone` (VARCHAR 20, NULLABLE)
- `email` (VARCHAR 255, NULLABLE)
- `is_active` (BOOLEAN, DEFAULT true)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)
- `deleted_at` (TIMESTAMP, NULLABLE) - Soft delete

**Indexes:**
- `idx_schools_code` (code)
- `idx_schools_domain` (domain)

---

#### `academic_years`
- `id` (PK, BIGINT, UNSIGNED, AUTO_INCREMENT)
- `school_id` (FK → schools.id)
- `name` (VARCHAR 100) - e.g., "2024/2025"
- `start_date` (DATE)
- `end_date` (DATE)
- `is_active` (BOOLEAN, DEFAULT false) - Only one active per school
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)
- `deleted_at` (TIMESTAMP, NULLABLE)

**Indexes:**
- `idx_academic_years_school` (school_id)
- `idx_academic_years_active` (school_id, is_active)

**Constraints:**
- Only ONE active academic year per school at a time

---

#### `terms`
- `id` (PK, BIGINT, UNSIGNED, AUTO_INCREMENT)
- `academic_year_id` (FK → academic_years.id)
- `name` (VARCHAR 50) - e.g., "First Term", "Second Term"
- `term_number` (TINYINT) - 1, 2, or 3
- `start_date` (DATE)
- `end_date` (DATE)
- `status` (ENUM: 'draft', 'active', 'closing', 'closed', 'archived') - DEFAULT 'draft'
- `grace_period_days` (INT, DEFAULT 7) - Days in grace period
- `grace_period_end` (DATE, NULLABLE) - Calculated when status becomes 'closing'
- `closed_at` (TIMESTAMP, NULLABLE) - When term was closed
- `archived_at` (TIMESTAMP, NULLABLE) - When term was archived
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)
- `deleted_at` (TIMESTAMP, NULLABLE)

**Indexes:**
- `idx_terms_academic_year` (academic_year_id)
- `idx_terms_status` (status)
- `idx_terms_active` (academic_year_id, status) - For finding active term

**Constraints:**
- Only ONE active term per academic year at a time
- term_number must be 1, 2, or 3
- end_date must be after start_date

**Term Lifecycle Rules:**
- **Draft**: Can be edited, not visible to parents/teachers
- **Active**: Visible, can create assessments, attendance
- **Closing**: Grace period - teachers can finalize, no new assessments
- **Closed**: Read-only, subscription expires
- **Archived**: Historical record

---

### 2. USER MANAGEMENT & RBAC

#### `users`
- `id` (PK, BIGINT, UNSIGNED, AUTO_INCREMENT)
- `school_id` (FK → schools.id, NULLABLE) - NULL for Super Admin
- `email` (VARCHAR 255, UNIQUE)
- `password` (VARCHAR 255) - Hashed
- `first_name` (VARCHAR 100)
- `last_name` (VARCHAR 100)
- `phone` (VARCHAR 20, NULLABLE)
- `avatar` (VARCHAR 255, NULLABLE)
- `email_verified_at` (TIMESTAMP, NULLABLE)
- `is_active` (BOOLEAN, DEFAULT true)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)
- `deleted_at` (TIMESTAMP, NULLABLE)

**Indexes:**
- `idx_users_school` (school_id)
- `idx_users_email` (email)
- `idx_users_active` (school_id, is_active)

---

#### `roles`
- `id` (PK, BIGINT, UNSIGNED, AUTO_INCREMENT)
- `name` (VARCHAR 50, UNIQUE) - 'super_admin', 'school_admin', 'teacher', 'parent'
- `display_name` (VARCHAR 100)
- `description` (TEXT, NULLABLE)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**Predefined Roles:**
1. `super_admin` - Platform owner
2. `school_admin` - School administrator
3. `teacher` - Free user, can manage classes
4. `parent` - Paid user, read-only access

---

#### `user_roles`
- `id` (PK, BIGINT, UNSIGNED, AUTO_INCREMENT)
- `user_id` (FK → users.id)
- `role_id` (FK → roles.id)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**Indexes:**
- `idx_user_roles_user` (user_id)
- `idx_user_roles_role` (role_id)
- `UNIQUE idx_user_role_unique` (user_id, role_id)

---

### 3. STUDENT & PARENT MANAGEMENT

#### `students`
- `id` (PK, BIGINT, UNSIGNED, AUTO_INCREMENT)
- `school_id` (FK → schools.id)
- `student_number` (VARCHAR 50, UNIQUE) - School-specific student ID
- `first_name` (VARCHAR 100)
- `last_name` (VARCHAR 100)
- `middle_name` (VARCHAR 100, NULLABLE)
- `date_of_birth` (DATE, NULLABLE)
- `gender` (ENUM: 'male', 'female', 'other', NULLABLE)
- `photo` (VARCHAR 255, NULLABLE)
- `address` (TEXT, NULLABLE)
- `phone` (VARCHAR 20, NULLABLE)
- `email` (VARCHAR 255, NULLABLE)
- `is_active` (BOOLEAN, DEFAULT true)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)
- `deleted_at` (TIMESTAMP, NULLABLE)

**Indexes:**
- `idx_students_school` (school_id)
- `idx_students_number` (student_number)
- `idx_students_active` (school_id, is_active)

---

#### `parents`
- `id` (PK, BIGINT, UNSIGNED, AUTO_INCREMENT)
- `user_id` (FK → users.id, UNIQUE) - Links to users table
- `phone` (VARCHAR 20) - For Mobile Money
- `momo_provider` (VARCHAR 50, NULLABLE) - 'mtn', 'vodafone', 'airteltigo'
- `momo_number` (VARCHAR 20, NULLABLE)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)
- `deleted_at` (TIMESTAMP, NULLABLE)

**Indexes:**
- `idx_parents_user` (user_id)
- `idx_parents_phone` (phone)

---

#### `student_parent`
- `id` (PK, BIGINT, UNSIGNED, AUTO_INCREMENT)
- `student_id` (FK → students.id)
- `parent_id` (FK → parents.id)
- `relationship` (VARCHAR 50) - 'father', 'mother', 'guardian', 'other'
- `is_primary` (BOOLEAN, DEFAULT false) - Primary contact
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**Indexes:**
- `idx_student_parent_student` (student_id)
- `idx_student_parent_parent` (parent_id)
- `UNIQUE idx_student_parent_unique` (student_id, parent_id)

---

#### `teachers`
- `id` (PK, BIGINT, UNSIGNED, AUTO_INCREMENT)
- `user_id` (FK → users.id, UNIQUE) - Links to users table
- `staff_number` (VARCHAR 50, NULLABLE)
- `qualification` (VARCHAR 255, NULLABLE)
- `specialization` (VARCHAR 255, NULLABLE)
- `hire_date` (DATE, NULLABLE)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)
- `deleted_at` (TIMESTAMP, NULLABLE)

**Indexes:**
- `idx_teachers_user` (user_id)
- `idx_teachers_staff_number` (staff_number)

---

### 4. ENROLLMENT & CLASSES

#### `classes`
- `id` (PK, BIGINT, UNSIGNED, AUTO_INCREMENT)
- `school_id` (FK → schools.id)
- `name` (VARCHAR 100) - e.g., "JHS 1A", "Primary 3B"
- `level` (VARCHAR 50) - e.g., "JHS 1", "Primary 3"
- `section` (VARCHAR 50, NULLABLE) - e.g., "A", "B"
- `capacity` (INT, DEFAULT 30)
- `class_teacher_id` (FK → teachers.id, NULLABLE)
- `academic_year_id` (FK → academic_years.id)
- `is_active` (BOOLEAN, DEFAULT true)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)
- `deleted_at` (TIMESTAMP, NULLABLE)

**Indexes:**
- `idx_classes_school` (school_id)
- `idx_classes_academic_year` (academic_year_id)
- `idx_classes_teacher` (class_teacher_id)

---

#### `enrollments`
- `id` (PK, BIGINT, UNSIGNED, AUTO_INCREMENT)
- `student_id` (FK → students.id)
- `class_id` (FK → classes.id)
- `academic_year_id` (FK → academic_years.id)
- `enrollment_date` (DATE)
- `status` (ENUM: 'active', 'transferred', 'graduated', 'withdrawn') - DEFAULT 'active'
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)
- `deleted_at` (TIMESTAMP, NULLABLE)

**Indexes:**
- `idx_enrollments_student` (student_id)
- `idx_enrollments_class` (class_id)
- `idx_enrollments_academic_year` (academic_year_id)
- `idx_enrollments_status` (student_id, status)

**Constraints:**
- One active enrollment per student per academic year

---

### 5. SUBJECTS & ASSESSMENTS

#### `subjects`
- `id` (PK, BIGINT, UNSIGNED, AUTO_INCREMENT)
- `school_id` (FK → schools.id)
- `name` (VARCHAR 100)
- `code` (VARCHAR 20, NULLABLE) - e.g., "MATH", "ENG"
- `description` (TEXT, NULLABLE)
- `is_core` (BOOLEAN, DEFAULT false)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)
- `deleted_at` (TIMESTAMP, NULLABLE)

**Indexes:**
- `idx_subjects_school` (school_id)
- `idx_subjects_code` (code)

---

#### `class_subjects`
- `id` (PK, BIGINT, UNSIGNED, AUTO_INCREMENT)
- `class_id` (FK → classes.id)
- `subject_id` (FK → subjects.id)
- `teacher_id` (FK → teachers.id) - Subject teacher
- `academic_year_id` (FK → academic_years.id)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**Indexes:**
- `idx_class_subjects_class` (class_id)
- `idx_class_subjects_subject` (subject_id)
- `idx_class_subjects_teacher` (teacher_id)
- `UNIQUE idx_class_subject_unique` (class_id, subject_id, academic_year_id)

---

#### `assessments`
- `id` (PK, BIGINT, UNSIGNED, AUTO_INCREMENT)
- `term_id` (FK → terms.id)
- `class_subject_id` (FK → class_subjects.id)
- `teacher_id` (FK → teachers.id)
- `name` (VARCHAR 255) - e.g., "Mid-Term Exam", "Assignment 1"
- `type` (ENUM: 'exam', 'quiz', 'assignment', 'project', 'other')
- `total_marks` (DECIMAL(5,2))
- `weight` (DECIMAL(5,2)) - Percentage weight in final grade
- `assessment_date` (DATE)
- `due_date` (DATE, NULLABLE)
- `is_finalized` (BOOLEAN, DEFAULT false)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)
- `deleted_at` (TIMESTAMP, NULLABLE)

**Indexes:**
- `idx_assessments_term` (term_id)
- `idx_assessments_class_subject` (class_subject_id)
- `idx_assessments_teacher` (teacher_id)

**Constraints:**
- Cannot create assessments when term status is 'closing', 'closed', or 'archived'
- Only teachers assigned to class_subject can create assessments

---

#### `results`
- `id` (PK, BIGINT, UNSIGNED, AUTO_INCREMENT)
- `assessment_id` (FK → assessments.id)
- `student_id` (FK → students.id)
- `marks_obtained` (DECIMAL(5,2))
- `grade` (VARCHAR(10), NULLABLE) - Calculated grade
- `remarks` (TEXT, NULLABLE)
- `entered_by` (FK → users.id) - Teacher who entered
- `entered_at` (TIMESTAMP)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)
- `deleted_at` (TIMESTAMP, NULLABLE)

**Indexes:**
- `idx_results_assessment` (assessment_id)
- `idx_results_student` (student_id)
- `idx_results_entered_by` (entered_by)
- `UNIQUE idx_result_unique` (assessment_id, student_id)

**Constraints:**
- marks_obtained cannot exceed assessment.total_marks

---

### 6. ATTENDANCE

#### `attendance`
- `id` (PK, BIGINT, UNSIGNED, AUTO_INCREMENT)
- `term_id` (FK → terms.id)
- `class_id` (FK → classes.id)
- `student_id` (FK → students.id)
- `date` (DATE)
- `status` (ENUM: 'present', 'absent', 'late', 'excused')
- `remarks` (TEXT, NULLABLE)
- `marked_by` (FK → users.id) - Teacher who marked
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**Indexes:**
- `idx_attendance_term` (term_id)
- `idx_attendance_class` (class_id)
- `idx_attendance_student` (student_id)
- `idx_attendance_date` (date)
- `UNIQUE idx_attendance_unique` (term_id, student_id, date)

---

### 7. SUBSCRIPTIONS & PAYMENTS

#### `subscriptions`
- `id` (PK, BIGINT, UNSIGNED, AUTO_INCREMENT)
- `parent_id` (FK → parents.id)
- `student_id` (FK → students.id)
- `term_id` (FK → terms.id)
- `status` (ENUM: 'active', 'expired', 'cancelled') - DEFAULT 'active'
- `amount` (DECIMAL(10,2))
- `currency` (VARCHAR(3), DEFAULT 'GHS')
- `starts_at` (TIMESTAMP) - When subscription begins
- `expires_at` (TIMESTAMP) - When term closes or subscription expires
- `payment_id` (FK → payments.id, NULLABLE)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**Indexes:**
- `idx_subscriptions_parent` (parent_id)
- `idx_subscriptions_student` (student_id)
- `idx_subscriptions_term` (term_id)
- `idx_subscriptions_status` (status)
- `idx_subscriptions_active` (parent_id, student_id, term_id, status)
- `UNIQUE idx_subscription_unique` (parent_id, student_id, term_id)

**Constraints:**
- One subscription per parent-student-term combination
- Subscription expires when term status becomes 'closed'
- Parents can only access data for terms with active subscriptions

---

#### `payments`
- `id` (PK, BIGINT, UNSIGNED, AUTO_INCREMENT)
- `parent_id` (FK → parents.id)
- `student_id` (FK → students.id)
- `term_id` (FK → terms.id)
- `amount` (DECIMAL(10,2))
- `currency` (VARCHAR(3), DEFAULT 'GHS')
- `payment_method` (ENUM: 'momo', 'bank', 'cash', 'other') - DEFAULT 'momo'
- `momo_provider` (VARCHAR(50), NULLABLE) - 'mtn', 'vodafone', 'airteltigo'
- `momo_transaction_id` (VARCHAR(100), NULLABLE) - External transaction ID
- `reference` (VARCHAR(100), UNIQUE) - Internal payment reference
- `status` (ENUM: 'pending', 'processing', 'completed', 'failed', 'cancelled') - DEFAULT 'pending'
- `webhook_data` (JSON, NULLABLE) - Store webhook payload
- `verified_at` (TIMESTAMP, NULLABLE) - When payment was verified
- `verified_by` (VARCHAR(50), NULLABLE) - 'webhook', 'admin', 'manual'
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**Indexes:**
- `idx_payments_parent` (parent_id)
- `idx_payments_student` (student_id)
- `idx_payments_term` (term_id)
- `idx_payments_status` (status)
- `idx_payments_reference` (reference)
- `idx_payments_momo_transaction` (momo_transaction_id)

---

### 8. NOTIFICATIONS & ALERTS

#### `notifications`
- `id` (PK, BIGINT, UNSIGNED, AUTO_INCREMENT)
- `user_id` (FK → users.id)
- `type` (VARCHAR(50)) - 'payment', 'attendance', 'result', 'general'
- `title` (VARCHAR(255))
- `message` (TEXT)
- `data` (JSON, NULLABLE) - Additional data
- `is_read` (BOOLEAN, DEFAULT false)
- `read_at` (TIMESTAMP, NULLABLE)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**Indexes:**
- `idx_notifications_user` (user_id)
- `idx_notifications_read` (user_id, is_read)
- `idx_notifications_type` (type)

---

### 9. AUDIT & LOGGING

#### `audit_logs`
- `id` (PK, BIGINT, UNSIGNED, AUTO_INCREMENT)
- `school_id` (FK → schools.id, NULLABLE)
- `user_id` (FK → users.id, NULLABLE)
- `action` (VARCHAR(100)) - 'create', 'update', 'delete', 'view', 'login', 'logout'
- `model` (VARCHAR(100)) - Model name
- `model_id` (BIGINT, NULLABLE) - ID of affected record
- `old_values` (JSON, NULLABLE) - Before change
- `new_values` (JSON, NULLABLE) - After change
- `ip_address` (VARCHAR(45), NULLABLE)
- `user_agent` (TEXT, NULLABLE)
- `created_at` (TIMESTAMP)

**Indexes:**
- `idx_audit_logs_school` (school_id)
- `idx_audit_logs_user` (user_id)
- `idx_audit_logs_model` (model, model_id)
- `idx_audit_logs_created` (created_at)

---

## Database Relationships Summary

### One-to-Many
- School → Academic Years
- Academic Year → Terms
- School → Users
- School → Students
- School → Classes
- School → Subjects
- User → User Roles
- Academic Year → Enrollments
- Class → Enrollments
- Term → Assessments
- Term → Attendance
- Term → Subscriptions
- Parent → Subscriptions
- Student → Subscriptions
- Assessment → Results
- User → Notifications

### Many-to-Many
- Users ↔ Roles (via user_roles)
- Students ↔ Parents (via student_parent)
- Classes ↔ Subjects (via class_subjects)

---

## Key Constraints & Business Rules

1. **Term Lifecycle**: Only one active term per academic year
2. **Subscription Access**: Parents can only access data for terms with active subscriptions
3. **Assessment Creation**: Blocked when term is in 'closing', 'closed', or 'archived' status
4. **Multi-school Isolation**: All queries must filter by school_id
5. **Parent Read-only**: Enforced at API middleware level
6. **Teacher Free Access**: Teachers have full access to their assigned classes/subjects

---

## Indexes Strategy

- **Foreign Keys**: All foreign keys indexed
- **Lookup Fields**: student_id, term_id, parent_id heavily indexed
- **Composite Indexes**: For common query patterns (e.g., parent_id + student_id + term_id)
- **Unique Constraints**: Prevent duplicate subscriptions, enrollments, results

---

## Soft Deletes

Tables with soft deletes:
- schools
- academic_years
- terms
- users
- students
- parents
- teachers
- enrollments
- subjects
- assessments
- results

---

## Next Steps

1. Create Laravel migrations based on this schema
2. Implement models with relationships
3. Create middleware for RBAC and subscription enforcement
4. Build API endpoints with proper access control

