# LivingspringCBT

LivingspringCBT is a custom PHP-based computer-based testing platform built for a school environment. It supports three core user groups:

- Students who log in, take assessments, resume interrupted sessions, and view results
- Teachers who manage question banks, configure assessments, and track performance
- Admins who manage users, subjects, exams, notifications, and maintenance controls

## Application Overview

The application uses a lightweight custom MVC structure instead of a full framework. Requests enter through `public/index.php`, routes are declared in `routes.php`, controllers live in `App/controllers`, views live in `App/views`, and shared helpers live in `helpers.php`.

The platform is designed around role-based flows:

- Student flow: login, dashboard, assessment selection, exam session, results, corrections
- Teacher flow: login, subject dashboard, question management, duration setup, performance tracking, admin messaging
- Admin flow: authentication, dashboards, teacher and student management, exam activation, audit and maintenance tools

## Main Features

### Student Features

- Student login by name, class, and password
- Dashboard showing available assessments and recent attempts
- Start and resume exam sessions
- Timed assessments with autosave-aware session persistence
- Result history and correction review
- Duplicate session protection for student accounts
- Class-level access locking controlled by admin

### Teacher Features

- Teacher login with role and permission checks
- Subject-restricted access based on assigned subjects
- Question creation, editing, deletion, and context management
- Assessment configuration by class, term, task type, duration, and question limit
- Performance tracking from recorded attempts
- Messaging and alert flow to admin
- Presence ping support for activity tracking

### Admin Features

- Admin authentication and dashboard summaries
- Teacher creation, update, activation, deactivation, and deletion
- Student creation, update, class-lock control, and deletion
- Subject management
- Exam activation by class and subject
- Notifications and teacher messaging tools
- Audit and login monitoring pages
- Maintenance mode for temporarily pausing non-admin access

## Architecture

### Entry Point and Routing

- `public/index.php` boots the app, starts the session, sets security headers, and dispatches the request
- `routes.php` maps URLs to controller files
- `Framework/Router.php` handles route matching, CSRF checks, middleware-style access control, and maintenance-mode enforcement

### Framework Layer

The project includes a small internal framework:

- `Framework/Router.php` for route dispatch
- `Framework/Session.php` for session handling, flash messages, and CSRF tokens
- `Framework/Database.php` for PDO-based database access
- `Framework/Authorization.php` for simple ownership checks

This approach keeps the project lightweight and easy to host on a plain PHP/XAMPP setup.

## Database Design

The application uses two database configurations:

- `config/config-db.php` for core application data such as admins, teachers, students, notifications, and maintenance settings
- `config/config-db2.php` for assessment data such as question banks, assessment configs, attempts, and exam sessions

By default these point to:

- `livingspring_cbt`
- `livingspring_cbt_subjects`

This split likely exists to separate account/administrative records from assessment and question-bank data.

## Key Mechanics and Why They Exist

### Role-Based Access

Routes are protected using middleware keys such as `guest`, `student`, `teacher`, `teacher_question`, `admin`, and `admin_guest`. This is why users are redirected automatically when they try to access pages outside their role.

### Live Permission Refresh

Teacher and admin session records are re-checked against the database during routing. This ensures changes like deactivation, role changes, or question-management permissions take effect immediately.

### Student Session Control

Student logins use `active_session_token` and `active_session_seen_at` to reduce duplicate access. This is why a student account cannot safely be used in multiple places at once.

### Persistent Exam Sessions

Exam state is stored both in PHP session and in database-backed exam-session records. This allows students to resume interrupted work, and it reduces the chance of losing an attempt during refreshes or temporary disconnections.

### On-Demand Schema Updates

The application creates and updates some tables at runtime through helper functions instead of using a separate migration system. This makes setup easier for a custom deployment, but it also means schema logic is embedded in the application code.

### Exam Activation and Class Locks

Admins can control which exams are active for each class and can also lock an entire class out of student access. This helps the school control exam windows and prevent unauthorized usage.

### Maintenance Mode

Maintenance mode is stored in the database and enforced centrally in the router. Non-admin users are blocked while admin access remains available for recovery and updates.

## Important Data Structures

Some important tables managed by the application include:

- `teacher_users`
- `student_users`
- `student_class_locks`
- `assessment_configs`
- `exam_attempts`
- `student_exam_sessions`
- `exam_session_events`
- `exam_question_analytics`
- `exam_active_subjects`
- `app_maintenance_mode`

## Typical User Journey

### Student Journey

1. Student logs in with name, class, and password.
2. The app verifies the account is active, not class-locked, and not already in live use elsewhere.
3. The student sees available assessments on the dashboard.
4. When an exam starts, questions are loaded, shuffled where applicable, and stored in session state.
5. Progress is processed during the attempt and finalized into result records on submission or timeout.

### Teacher Journey

1. Teacher logs in and is limited to assigned subjects.
2. The teacher creates or manages assessment contexts for a class and task type.
3. Questions are added to the relevant bank table and linked through assessment configuration records.
4. The teacher monitors performance and can contact admin when needed.

### Admin Journey

1. Admin logs in through the admin route.
2. Admin manages teachers, students, and subjects.
3. Admin activates exams for specific classes and subjects.
4. Admin reviews analytics, alerts, messages, and audit information.
5. Admin can enable maintenance mode when updates are needed.

## Project Structure

```text
public/                  Web entry point and public assets
App/controllers/         Route handlers grouped by role
App/views/               View templates
App/views/partials/      Shared layout fragments
Framework/               Small custom framework classes
config/                  Database configuration
helpers.php              Shared helper and exam/session utility logic
routes.php               Route definitions
tests/                   Test and seed scripts
```

## Notes

- The app is optimized for a straightforward PHP hosting environment such as XAMPP
- Much of the business logic is implemented directly in controllers and helpers
- The system has evolved over time, so some newer features are implemented as schema checks and incremental runtime upgrades
