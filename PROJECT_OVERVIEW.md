# T.O.M.E. - Training Organization Made Easy - Project Overview

## Project Summary
T.O.M.E. is a Progressive Web App (PWA) designed to organize training sessions for sports clubs. The primary goals are to support trainers' work and make the attendance process as simple as possible for users (no account registration required for basic attendance).

**Version:** 1.0.0  
**Current Location:** `/Users/sbinder/Projects/tome`  
**Deployed:** https://training.ssc-landau.de

---

## Technology Stack

### Frontend
- **Framework:** Vue.js 2.6.14 with Vue CLI 3
- **UI Component Library:** Vuetify 2.6.6 (Material Design)
- **State Management:** Vuex 3.6.2
- **Routing:** Vue Router 3.5.4
- **HTTP Client:** Axios 0.27.2
- **Language:** TypeScript 3.6.5
- **Charts:** ApexCharts 3.35.3 with vue-apexcharts 1.6.2
- **Date/Time:** Moment 2.29.3
- **Auth:** JWT (jwt-decode 3.1.2)
- **Backend Service:** Firebase 9.12.1
- **PWA:** Service Worker support via register-service-worker 1.7.2
- **CSS:** Sass with sass-loader 10.2.1
- **Icons:** Material Design Icons Iconfont 6.7.0

### Backend
- **Framework:** Laravel 8.0
- **API:** Dingo API (api-ecosystem-for-laravel/dingo-api ^3.1)
- **Authentication:** JWT Auth (tymon/jwt-auth 1.0.*)
- **Permissions:** Spatie Permission Package 3.16.0
- **Database:** Laravel ORM (Eloquent)
- **File Upload:** Intervention Image 2.5
- **Excel Export:** Maatwebsite Excel 3.1.26
- **Notifications:** Firebase Cloud Messaging (code-lts/laravel-fcm 1.6.0)
- **CORS:** fruitcake/laravel-cors 2.0
- **JSON Formatting:** grohiro/laravel-camelcase-json 2.0.0
- **Video Conferencing:** Laravel Zoom Integration (macsidigital/laravel-zoom 4.1)
- **Database Tool:** Doctrine DBAL 2.9 (for schema modifications)

---

## Project Structure

### Frontend (`/app`)
```
app/
├── public/                    # Static assets
├── src/
│   ├── components/           # Reusable Vue components
│   │   ├── ConfirmDialog.vue
│   │   ├── EditTrainingDialog.vue
│   │   ├── TrainingContent.vue
│   │   ├── TomeNavigation.vue
│   │   ├── TrainingCalendar.vue
│   │   ├── TrainerTimeline.vue
│   │   ├── TrainingParticipationBarChart.vue
│   │   ├── ProfileImage.vue
│   │   ├── GroupSelect.vue
│   │   ├── CookieUserDialog.vue
│   │   ├── EditUserDialog.vue
│   │   ├── LogoutComponent.vue
│   │   ├── UploadProfileImage.vue
│   │   ├── GroupsSelect.vue
│   │   ├── GroupsSelectDialog.vue
│   │   └── [other components]
│   ├── pages/                # Page-level Vue components (routes)
│   │   ├── AbsenceFormPage.vue           # Absence/sick leave management
│   │   ├── BranchesTablePage.vue         # Branch admin
│   │   ├── CalendarPage.vue              # Calendar view
│   │   ├── GroupsOverviewPage.vue        # Groups overview
│   │   ├── GroupsTablePage.vue           # Groups management
│   │   ├── InfoPage.vue                  # Information/FAQ
│   │   ├── LocationsPage.vue             # Training locations
│   │   ├── LoginPage.vue                 # Login form
│   │   ├── ProfilePage.vue               # User profile
│   │   ├── SignupPage.vue                # Registration form
│   │   ├── StatisticsPage.vue            # Training statistics
│   │   ├── TrainingsCheckInPage.vue      # Attendance check-in
│   │   ├── TrainingsEvaluationPage.vue   # Training evaluation/reporting
│   │   ├── TrainingsPreparePage.vue      # Training preparation
│   │   ├── TrainingSeriesPage.vue        # Training series management
│   │   ├── TrainingsTablePage.vue        # Trainings overview table
│   │   └── UsersTablePage.vue            # Users/members management
│   ├── models/               # TypeScript data models
│   │   ├── Training.ts
│   │   ├── TrainingTrainer.ts
│   │   ├── TrainingEvaluation.ts
│   │   ├── TrainingCalendarEntry.ts
│   │   ├── TrainingSeries.ts
│   │   ├── TrainingParticipant.ts
│   │   ├── User.ts
│   │   ├── SimpleUser.ts
│   │   ├── Group.ts
│   │   ├── Branch.ts
│   │   └── [other models]
│   ├── store/                # Vuex state management
│   │   ├── index.ts
│   │   ├── auth.ts           # Authentication state
│   │   ├── cookieAuth.ts     # Cookie-based auth for unregistered users
│   │   ├── masterData.ts     # Shared master data (branches, groups, etc.)
│   │   ├── snackbar.js       # UI notifications
│   │   └── mutation-types.js
│   ├── router/               # Vue Router configuration
│   ├── axios/                # Axios HTTP client setup
│   ├── plugins/              # Vue plugins
│   ├── helpers/              # Utility functions
│   ├── App.vue               # Root component
│   └── main.ts               # Application entry point
├── package.json              # Frontend dependencies
├── tsconfig.json             # TypeScript configuration
├── vue.config.js             # Vue CLI configuration
└── .env.example              # Environment variables template
```

**Key Frontend Statistics:**
- ~19,131 total lines of code across Vue, TypeScript, and PHP files
- 17 main page components (form pages)
- 30+ reusable components
- 10+ TypeScript data models

### Backend (`/server`)
```
server/
├── app/
│   ├── Api/V1/
│   │   └── Controllers/       # API endpoint controllers
│   │       ├── TrainingController.ts
│   │       ├── TrainingCalendarController.ts
│   │       ├── BranchController.ts
│   │       ├── GroupController.ts
│   │       ├── UserController.ts
│   │       ├── LocationController.ts
│   │       ├── TrainingSeriesController.ts
│   │       ├── FaqController.ts
│   │       ├── NotificationController.ts
│   │       ├── SimpleUserController.ts
│   │       ├── LoginController.ts
│   │       ├── SignUpController.ts
│   │       ├── ForgotPasswordController.ts
│   │       ├── ResetPasswordController.ts
│   │       ├── ChangePasswordController.ts
│   │       ├── ImageController.ts
│   │       └── [other controllers]
│   │   ├── Requests/          # Form request validation
│   ├── Models/                # Eloquent models
│   │   ├── Training.php
│   │   ├── TrainingSeries.php
│   │   ├── TrainingParticipation.php
│   │   ├── TrainingTrainer.php
│   │   ├── User.php
│   │   ├── Group.php
│   │   ├── Branch.php
│   │   ├── Location.php
│   │   ├── Content.php
│   │   ├── Faq.php
│   │   ├── NotificationToken.php
│   │   ├── UserTrainingNotification.php
│   │   └── [other models]
│   ├── Http/
│   │   ├── Controllers/       # Web controllers (if any)
│   │   ├── Resources/         # API response transformers
│   │   └── Middleware/        # HTTP middleware
│   ├── Mail/                  # Email classes
│   ├── Exports/               # Excel export classes
│   ├── Console/               # Artisan commands
│   ├── Exceptions/            # Custom exceptions
│   └── Providers/             # Service providers
├── database/
│   ├── migrations/            # Database schema migrations
│   │   ├── 2019_01_25_102201_create_branches_table.php
│   │   ├── 2019_01_25_102202_create_groups_table.php
│   │   ├── 2019_01_25_102203_create_locations_table.php
│   │   ├── 2019_01_25_102205_create_permission_tables.php
│   │   ├── 2019_01_25_102207_create_contents_tables.php
│   │   ├── 2019_01_25_102208_create_training_series_tables.php
│   │   ├── 2019_01_25_102209_create_trainings_tables.php
│   │   ├── 2019_05_19_000000_add_accounting_time.php
│   │   ├── 2019_09_22_102209_change_comment_notification_types.php
│   │   ├── 2019_09_29_000000_add_user_profile_image_name.php
│   │   ├── 2019_10_19_000000_add_branch_color.php
│   │   ├── 2019_11_30_102209_create_faq_table.php
│   │   ├── 2020_01_09_102209_series_replace_active_with_defer_until.php
│   │   ├── 2021_01_03_000000_create_trainer_branch_table.php
│   │   ├── 2021_01_06_000000_drop_trainer_group_table.php
│   │   ├── 2021_01_09_000000_add_zoom_id_column.php
│   │   ├── 2021_06_25_000000_add_automatic_attend_column.php
│   │   ├── 2021_09_8_000000_remove_approved_column.php
│   │   ├── 2021_09_10_000000_add_user_absence_columns.php
│   │   └── [other migrations]
│   └── seeds/                 # Database seeders
├── config/                    # Configuration files
├── routes/
│   ├── api.php               # API routes (Dingo API v1)
│   ├── web.php               # Web routes
│   └── console.php           # Console commands
├── storage/                  # Application storage
├── resources/                # Views and language files
├── public/                   # Public assets
├── composer.json             # PHP dependencies
├── .env.example              # Environment variables template
└── phpunit.xml               # Test configuration
```

---

## Core Data Models

### Main Entities

1. **User**
   - Full user accounts (trainers, administrators)
   - JWT authentication
   - Profile image support
   - Absence/sick leave tracking
   - Role-based permissions (via Spatie)

2. **SimpleUser**
   - Lightweight user accounts for attendance tracking
   - Cookie-based identification (no password required)
   - Can record attendance without full registration

3. **Training**
   - Individual training sessions
   - Associated trainer(s) and participants
   - Location information
   - Time and date
   - Soft deletion support

4. **TrainingSeries**
   - Recurring training sessions template
   - Can be deferred until specified date
   - Automatic training generation

5. **TrainingParticipation**
   - Tracks user attendance at trainings
   - Check-in/check-out timestamps
   - Presence status

6. **Group**
   - User groups/teams
   - Associated with branches
   - Member management

7. **Branch**
   - Organization branches/divisions
   - Color coding
   - Contains groups

8. **Location**
   - Training venue information
   - Referenced by trainings

9. **TrainingEvaluation**
   - Post-training feedback and evaluation
   - Evaluation of training effectiveness

10. **TrainingTrainer**
    - Association between trainers and trainings
    - Primary trainer designation

11. **Content**
    - Static content/pages

12. **Faq**
    - FAQ entries and documentation

13. **NotificationToken**
    - Firebase Cloud Messaging tokens for push notifications

14. **UserTrainingNotification**
    - Notification preferences per user

---

## API Endpoints

### Public Endpoints (No Authentication)

**Training:**
- `GET /training/simplecalendar` - Get simple training calendar
- `GET /training/simplecalendar/planned` - Get planned trainings
- `GET /training/upcoming` - Get upcoming trainings
- `GET /training/upcoming/{id}` - Get specific upcoming training
- `POST /training/{id}/checkinunregistered/{userId}` - Check in unregistered user
- `POST /training/{id}/checkoutunregistered/{userId}` - Check out unregistered user

**Master Data:**
- `GET /branch` - Get all branches
- `GET /group` - Get all groups
- `GET /group/branch/{id}` - Get groups by branch
- `GET /location` - Get all locations
- `GET /trainingSeries` - Get all training series
- `GET /simpleuser` - Get simple users
- `GET /simpleuser/trainers` - Get trainers list
- `GET /content` - Get static content
- `GET /faq` - Get FAQ entries
- `GET /faq/files` - Get info documents

**Authentication:**
- `POST /auth/signup` - User registration
- `POST /auth/login` - User login
- `POST /auth/recovery` - Send password reset email
- `POST /auth/reset` - Reset password
- `POST /auth/logout` - Logout
- `POST /auth/refresh` - Refresh JWT token
- `GET /auth/me` - Get current user

**Notifications:**
- `POST /notifications/subscribe` - Subscribe to push notifications

**Simple User:**
- `POST /simpleuser/{id}/storeAbsence` - Mark absence
- `POST /simpleuser/{id}/removeAbsence` - Remove absence

### Protected Endpoints (JWT Authentication Required)

**User Management:**
- `GET /user` - List all users
- `GET /user/sort` - Get sorted users
- `GET /user/trainer` - Get trainers
- `GET /user/birthdays` - Get users with birthdays
- `GET /user/allAbsence` - Get users with absences
- `PUT /user/me` - Update own profile
- `PUT /user/{id}` - Update user
- `DELETE /user/{id}` - Delete user
- `POST /user/unregistered` - Create unregistered user
- `POST /user/me/changepassword` - Change password
- `POST /user/me/uploadprofileimage` - Upload profile image
- `PUT /user/{id}/removeAbsence` - Remove user absence

**Group Management:**
- `POST /group` - Create group
- `PUT /group/{id}` - Update group
- `DELETE /group/{id}` - Delete group

**Location Management:**
- `POST /location` - Create location
- `PUT /location/{id}` - Update location

**Branch Management:**
- `POST /branch` - Create branch
- `PUT /branch/{id}` - Update branch
- `DELETE /branch/{id}` - Delete branch

**Training Management:**
- CRUD operations for trainings
- Evaluation and preparation endpoints

**Training Series Management:**
- Series CRUD operations

---

## Key Features

### For Trainers
1. **Training Management**
   - Create, edit, and manage training sessions
   - Schedule trainings and training series
   - View attendance
   
2. **Attendance Tracking**
   - Check in/out participants
   - View attendance history
   - Generate attendance reports

3. **Training Evaluation**
   - Evaluate training sessions
   - Record feedback
   - Generate statistics

4. **User Management**
   - Manage trainer and participant profiles
   - Track member information
   - Manage absence/sick leave

5. **Statistics**
   - View training participation charts
   - Monthly email reports of attendance statistics by branch

### For Users
1. **Easy Check-In**
   - No account registration required for basic attendance
   - Cookie-based identification
   - Simple attendance process

2. **Training Calendar**
   - View upcoming trainings
   - See training schedule
   - Join trainings

3. **Profile Management**
   - Optional user registration
   - Manage profile information
   - Upload profile picture

4. **Information**
   - Access FAQ and documents
   - View training information
   - Get organization details

### General Features
1. **Progressive Web App (PWA)**
   - Installable on mobile/desktop
   - Offline capability (via service worker)
   - Works on any device with a browser

2. **Push Notifications**
   - Firebase Cloud Messaging integration
   - Training reminders
   - Subscription management

3. **Authentication**
   - JWT-based API authentication for registered users
   - Cookie-based identification for simple users
   - Password reset functionality

4. **Multi-branch Support**
   - Organization can have multiple branches
   - Branch-specific groups
   - Branch-specific trainers

---

## Database Schema Highlights

### Key Tables
- `users` - User accounts
- `simple_users` - Simple user accounts
- `trainings` - Training sessions
- `training_series` - Recurring training templates
- `training_participants` - Attendance records
- `training_trainers` - Trainer associations
- `training_evaluations` - Training feedback
- `groups` - User groups
- `branches` - Organization branches
- `locations` - Training venues
- `contents` - Static pages/content
- `faqs` - FAQ entries
- `notification_tokens` - FCM subscription tokens
- `user_training_notifications` - Notification preferences
- `permissions` - Spatie permission roles/permissions

### Important Columns
- **soft_delete** pattern used (deleted_at for soft deletions)
- **uuid** support for some models
- **timestamp tracking** (created_at, updated_at)
- **automatic_attend** flag for auto check-in
- **zoom_id** for video conference integration
- **accounting_time** for duration tracking
- **branch_color** for UI customization

---

## Development Setup

### Frontend Setup
```bash
cd app/
npm install
npm run serve          # Development
npm run build          # Production build
npm run lint           # Lint code
```

### Backend Setup
```bash
cd server/
composer install
php artisan serve      # Development server
php artisan migrate    # Run migrations
```

### Environment Configuration
- Frontend: `app/.env.example`
- Backend: `server/.env.example`

---

## Recent Development History

### Major Updates
1. **Laravel 8.0 Upgrade** - Upgraded from earlier Laravel versions
2. **Dingo API Update** - Switched to `api-ecosystem-for-laravel/dingo-api`
3. **Node/Sass Update** - Replaced node-sass with sass package
4. **Soft Deletion Feature** - Trainings now marked for deletion, not permanently deleted
5. **Monthly Statistics Emails** - Automated branch-specific attendance reports
6. **Absence Tracking** - Comprehensive sick leave/absence management
7. **Unregistered User Improvements** - Better support for check-in without registration
8. **Training Check-In** - Auto-select trainer's branch on check-in page

### Known Issues/Notes
- Notifications temporarily disabled to prevent spam
- Image orientation handling for mobile uploads

---

## File Sizes
- Total project: ~19,131 lines of code (Vue, TypeScript, and PHP)
- Frontend application code is substantial with 17 main page components
- Backend has comprehensive API with 20+ migration files indicating mature schema

---

## Important URLs & Configurations

**Production URL:** https://training.ssc-landau.de

**API Version:** v1 (Dingo API)

**Authentication Method:** JWT + Cookie-based for simple users

**Notification Provider:** Firebase Cloud Messaging

**Zoom Integration:** Enabled (macsidigital/laravel-zoom)

---

## Dependencies Summary

### Frontend Key Packages
- Vue ecosystem: vue, vue-router, vuex
- UI: vuetify (Material Design)
- HTTP: axios
- Charts: apexcharts
- Auth: jwt-decode, firebase
- Date/time: moment
- Build: vue-cli with typescript support

### Backend Key Packages
- API: dingo-api
- Auth: JWT auth, spatie permissions
- Database: Laravel ORM, DBAL
- File handling: intervention image
- Export: maatwebsite excel
- Email: Laravel mail
- Communication: FCM, laravel-zoom
- CORS: fruitcake/laravel-cors

---

## Notes for Future Context

When referencing this project in future AI context windows:
1. **Project Type:** Full-stack Vue.js + Laravel PWA for sports training management
2. **Complexity:** Medium-to-high (multi-user, real-time attendance, complex scheduling)
3. **Key Concern Areas:**
   - Attendance accuracy (critical for tracking)
   - Multi-branch support and data isolation
   - Performance with large datasets (many trainings/participants)
   - Mobile UX for check-in process
   - Notification delivery and reliability
4. **Important Patterns:**
   - Soft deletion of trainings
   - Unregistered user support (unique feature)
   - Role-based access control
   - Firebase integration
   - Responsive Vuetify UI

---

**Last Updated:** April 1, 2026  
**Overview Created For:** Quick AI context window reference and project understanding
