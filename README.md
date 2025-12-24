# School Management System (SMS)

A comprehensive, production-ready School Management System built for Ghanaian schools with parent subscription monetization.

## 🎯 Project Overview

This is a multi-school management system that allows each school to have:
- Its own domain
- Its own hosting
- Its own database
- Same shared codebase

## 🏗️ Architecture

### Tech Stack

**Backend:**
- Laravel 12 (API-only)
- JWT Authentication (tymon/jwt-auth)
- MySQL/MariaDB
- RESTful API

**Frontend:**
- Vue 3
- Quasar Framework
- Pinia (State Management)
- Axios (API Calls)
- Capacitor (Android builds)

### Database Structure

The system uses a comprehensive database schema with 20+ tables supporting:
- Multi-school isolation
- Academic structure (years, terms)
- User management with RBAC
- Student & parent management
- Enrollment & classes
- Subjects & assessments
- Results & attendance
- Subscriptions & payments
- Notifications & audit logs

See `database-schema-erd.md` for complete schema documentation.

## 🔐 Core Business Rules

### Academic Structure
- 3 academic terms per year
- Only ONE active term at a time
- Term Lifecycle: **Draft → Active → Closing (Grace Period) → Closed → Archived**

### User Roles
1. **Super Admin** - Platform owner
2. **School Admin** - School administrator
3. **Teacher** - FREE users, can manage classes
4. **Parent** - Paid users, read-only access

### Parent Subscription Model
- **Per student, per term**
- Paid via Mobile Money
- Subscription expires when term closes
- **Fairness Rule**: Parents can ONLY view data for terms they paid for (current or archived)
- No free access to past terms
- No back-reading without payment

## 📦 System Modules (MVP)

1. ✅ Student Management
2. ✅ Attendance Management
3. ✅ Exams & Results
4. ✅ Report Cards
5. ✅ Parent Portal
6. ✅ Teacher Portal
7. ✅ Term Management
8. ✅ Subscription & Payments
9. ✅ Alerts & Notifications

## 🚀 Getting Started

### Prerequisites
- PHP 8.2+
- Composer
- MySQL/MariaDB
- Node.js 18+ (for frontend)

### Backend Setup

1. **Install Dependencies**
```bash
cd backend
composer install
```

2. **Configure Environment**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Configure Database**
Edit `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sms_db
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

4. **Run Migrations**
```bash
php artisan migrate
```

5. **Seed Roles**
```bash
php artisan db:seed --class=RoleSeeder
```

6. **Configure JWT**
```bash
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

7. **Start Server**
```bash
php artisan serve
```

### Frontend Setup (Coming Soon)

```bash
cd frontend
npm install
quasar dev
```

## 🔒 Security & Access Control

### Middleware

The system includes several middleware for access control:

1. **RoleMiddleware** - Enforces role-based access
   ```php
   Route::middleware(['role:school_admin,teacher'])->group(function () {
       // Routes
   });
   ```

2. **ParentSubscriptionMiddleware** - Enforces parent subscription access
   ```php
   Route::middleware(['parent.subscription'])->group(function () {
       // Parent-only routes
   });
   ```

3. **SchoolScopeMiddleware** - Ensures queries are scoped to user's school
   ```php
   Route::middleware(['school.scope'])->group(function () {
       // School-scoped routes
   });
   ```

4. **TermStatusMiddleware** - Prevents actions on closed/archived terms
   ```php
   Route::middleware(['term.status'])->group(function () {
       // Term-sensitive routes
   });
   ```

### API Authentication

All API requests require JWT authentication:
```
Authorization: Bearer {token}
```

## 📊 Database Schema

Key tables:
- `schools` - School information
- `users` - User accounts
- `roles` & `user_roles` - RBAC
- `academic_years` & `terms` - Academic structure
- `students` & `parents` - Student/parent management
- `teachers` - Teacher profiles
- `classes` & `enrollments` - Class management
- `subjects` & `class_subjects` - Subject management
- `assessments` & `results` - Assessment & grading
- `attendance` - Attendance tracking
- `subscriptions` & `payments` - Monetization
- `notifications` - In-app notifications
- `audit_logs` - Audit trail

## 💰 Payment Flow

1. Parent tries to access term data
2. Access denied if no subscription
3. Prompt payment
4. Mobile Money payment
5. Webhook verification
6. Create subscription
7. Grant access

## 🔄 Term Lifecycle

1. **Draft** - Can be edited, not visible to parents/teachers
2. **Active** - Visible, can create assessments, attendance
3. **Closing** - Grace period, teachers can finalize, no new assessments
4. **Closed** - Read-only, subscription expires
5. **Archived** - Historical record

## 📝 Development Guidelines

- Follow SOLID principles
- Write clean, documented code
- Add comments for complex logic
- Avoid hardcoding values
- Structure code professionally
- Assume future scaling to 100+ schools

## 📄 License

[Your License Here]

## 👥 Contributors

[Your Team Here]

