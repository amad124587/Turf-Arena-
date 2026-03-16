# Architecture Overview

This document explains how the TurfBooking project is organized, how the frontend talks to the backend, and which files control the main user flows.

## 1. Stack

- Frontend: Vue 3 + Vue Router + Axios
- Styling: Tailwind utility classes plus shared theme helpers from `src/assets/theme.css`
- Backend: PHP endpoint files in `backend/`
- Database: MySQL schema in `database/turf_booking_system_full_schema.sql`

## 2. Frontend Structure

### Entry Layer

- `src/main.js`
  - Creates the Vue app
  - Mounts router
  - Loads global stylesheet `src/assets/theme.css`

- `src/App.vue`
  - Minimal shell
  - Only renders `<router-view />`

### Router

- `src/router/index.js`
  - Defines all public and protected routes
  - Handles role-based route guards for:
    - user dashboard
    - owner dashboard
    - admin dashboard

Current route mapping:

- `/` -> `src/components/UserRegister.vue`
- `/login` -> `src/components/UserLogin.vue`
- `/dashboard` -> `src/components/UserDashboard.vue`
- `/owner-dashboard` -> `src/components/OwnerDashboard.vue`
- `/admin-dashboard` -> `src/components/AdminDashboard.vue`
- `/browse` and `/turfs` -> `src/components/UserBrowseTurfs.vue`
- `/bookings` -> `src/components/UserMyBookingsPage.vue`

## 3. Frontend Design Pattern

The project uses a clear pattern:

1. Screen shell component
   - Example: `UserDashboard.vue`, `OwnerDashboard.vue`, `AdminDashboard.vue`

2. Smaller UI sections/components
   - Example: `UserUpcomingCard.vue`, `AdminRefundsSection.vue`, `OwnerRevenueSection.vue`

3. Support file for business logic and API requests
   - Example: `src/support/userDashboardSupport.js`

This means:

- Vue components mainly handle template and local state
- Support files contain API URLs, formatting helpers, and async actions

## 4. Shared UI Components

Core reusable UI pieces:

- `src/components/AppTopbar.vue`
  - Shared top navigation/header shell

- `src/components/GlassButton.vue`
  - Shared button style for glass buttons

- `src/components/StatusBadge.vue`
  - Shared badge color/tone mapping

## 5. User Flow Files

### Authentication

- `src/components/UserRegister.vue`
- `src/components/UserLogin.vue`
- `backend/register.php`
- `backend/login.php`

Session model:

- user data is stored in `localStorage`
- router guards read `localStorage.getItem('user')`
- role can be `user`, `owner`, or `admin`

### User Dashboard

Main files:

- `src/components/UserDashboard.vue`
- `src/components/UserUpcomingCard.vue`
- `src/components/UserActivityCard.vue`
- `src/components/UserWalletCard.vue`
- `src/components/UserReviewModal.vue`
- `src/support/userDashboardSupport.js`

Backend endpoints used:

- `backend/user_dashboard.php`
- `backend/review_pending.php`
- `backend/review_submit.php`
- `backend/cancel_booking.php`

Responsibilities:

- show stats
- show next booking
- show recent activity
- show reward points breakdown
- submit review
- send cancellation request

### Browse Turfs + Booking

Main files:

- `src/components/UserBrowseTurfs.vue`
- `src/components/UserBrowseTurfCard.vue`
- `src/support/userBrowseTurfsSupport.js`

Backend endpoints used:

- `backend/get_turfs.php`
- `backend/get_available_slots.php`
- `backend/book_slot.php`
- `backend/validate_promo_code.php`

Responsibilities:

- fetch approved/public turfs
- pick date and slot
- prevent invalid booking requests
- create booking
- validate promo code

### User Bookings

Main files:

- `src/components/UserMyBookingsPage.vue`
- `backend/get_user_bookings.php`
- `backend/cancel_booking.php`

Responsibilities:

- list user bookings
- show booking statuses
- trigger cancellation request

## 6. Owner Flow Files

### Owner Dashboard Shell

- `src/components/OwnerDashboard.vue`
- `src/support/ownerDashboardSupport.js`

The owner dashboard is tab-driven. The active tab decides which section component is rendered.

### Owner Dashboard Sections

- `src/components/OwnerOverviewSection.vue`
  - high-level owner stats
  - wallet and cancellation earnings summary

- `src/components/OwnerAddTurfsSection.vue`
  - add turf form
  - image upload
  - uses `backend/add_turf.php`

- `src/components/OwnerMyTurfsSection.vue`
  - lists owner-created turfs
  - uses `src/support/ownerTurfsSupport.js`
  - uses `backend/owner_my_turfs.php`

- `src/components/OwnerBookingsSection.vue`
  - booking list for owner turfs
  - uses `src/support/ownerBookingsSupport.js`
  - uses `backend/owner_booking_clients.php`

- `src/components/OwnerBookingClientDetailsModal.vue`
  - modal for client details in owner booking section

- `src/components/OwnerRevenueSection.vue`
  - wallet and cancellation earnings details
  - uses `backend/owner_finance.php`

- `src/components/OwnerSlotControlSection.vue`
  - owner slot management
  - uses `src/support/ownerSlotControlSupport.js`
  - uses `backend/owner_slot_control.php`

- `src/components/OwnerPromoCodesSection.vue`
  - owner promo creation and listing
  - uses `src/support/ownerPromoCodesSupport.js`
  - uses `backend/owner_promo_codes.php`
  - also depends on `backend/validate_promo_code.php` and `backend/promo_code_helper.php`

- `src/components/OwnerPlaceholderSection.vue`
  - fallback placeholder for tabs without completed logic

## 7. Admin Flow Files

### Admin Dashboard Shell

- `src/components/AdminDashboard.vue`
- `src/support/adminDashboardSupport.js`

The admin dashboard is also tab-driven.

### Admin Dashboard Sections

- `src/components/AdminOverviewSection.vue`
  - summary cards and pending queues

- `src/components/AdminVerifyTurfsSection.vue`
  - approve / reject / request changes for turf listings
  - backend: `backend/admin_turf_action.php`

- `src/components/AdminBookingRequestsSection.vue`
  - accept or reject pending bookings
  - backend: `backend/admin_booking_action.php`

- `src/components/AdminRefundsSection.vue`
  - approve or reject refund requests
  - backend: `backend/admin_refund_action.php`

- `src/components/AdminUsersOwnersSection.vue`
  - ban/unban users
  - suspend/activate owners
  - backend: `backend/admin_user_action.php`

- `src/components/AdminAnalyticsSection.vue`
  - analytics and reporting views

Backend endpoints used by admin shell:

- `backend/admin_dashboard.php`
- `backend/admin_common.php`
- `backend/admin_turf_action.php`
- `backend/admin_booking_action.php`
- `backend/admin_refund_action.php`
- `backend/admin_user_action.php`

## 8. Support Layer Summary

Support files are the business-logic layer for the frontend.

- `src/support/userDashboardSupport.js`
  - loads dashboard data
  - formats date/time/money
  - handles review submission and booking cancellation

- `src/support/userBrowseTurfsSupport.js`
  - turf browsing and booking logic

- `src/support/ownerDashboardSupport.js`
  - add turf flow
  - owner finance loading
  - image validation

- `src/support/ownerTurfsSupport.js`
  - owner turf listing logic

- `src/support/ownerBookingsSupport.js`
  - owner booking management logic

- `src/support/ownerSlotControlSupport.js`
  - slot availability logic

- `src/support/ownerPromoCodesSupport.js`
  - promo code CRUD logic

- `src/support/adminDashboardSupport.js`
  - admin dashboard refresh
  - turf/booking/refund/user-owner actions
  - message handling and action-loading state

## 9. Backend Structure

The backend currently uses a flat endpoint style instead of full MVC.

### Core utility

- `backend/db_connection.php`
  - shared MySQL connection

### User-facing endpoints

- `backend/register.php`
- `backend/login.php`
- `backend/user_dashboard.php`
- `backend/get_turfs.php`
- `backend/get_available_slots.php`
- `backend/book_slot.php`
- `backend/get_user_bookings.php`
- `backend/cancel_booking.php`
- `backend/review_pending.php`
- `backend/review_submit.php`
- `backend/validate_promo_code.php`
- `backend/promo_code_helper.php`

### Owner endpoints

- `backend/add_turf.php`
- `backend/owner_finance.php`
- `backend/owner_my_turfs.php`
- `backend/owner_booking_clients.php`
- `backend/owner_slot_control.php`
- `backend/owner_promo_codes.php`

### Admin endpoints

- `backend/admin_common.php`
- `backend/admin_dashboard.php`
- `backend/admin_turf_action.php`
- `backend/admin_booking_action.php`
- `backend/admin_refund_action.php`
- `backend/admin_user_action.php`

## 10. Database Files

- `database/turf_booking_system.sql`
  - likely base/export SQL snapshot

- `database/turf_booking_system_full_schema.sql`
  - full schema reference
  - should be treated as the main database design file

Key entity groups in schema:

- users
- turf owners
- admins
- turfs
- slots
- bookings
- payments
- reviews
- promo codes
- wallets and wallet transactions
- notifications
- refund requests
- disputes
- turf verification
- support tickets
- points logs

## 11. Data Flow Examples

### Example A: User books a turf

1. User opens `UserBrowseTurfs.vue`
2. Frontend loads turfs from `backend/get_turfs.php`
3. User selects a date and slot
4. Frontend loads slots from `backend/get_available_slots.php`
5. User submits booking
6. Frontend posts to `backend/book_slot.php`
7. Booking row is created in database

### Example B: Owner adds a turf

1. Owner opens `OwnerDashboard.vue`
2. `OwnerAddTurfsSection.vue` shows form
3. `ownerDashboardSupport.js` validates fields and image
4. Frontend posts form-data to `backend/add_turf.php`
5. Turf is inserted with pending/selected status
6. Admin later reviews turf from admin dashboard

### Example C: Admin approves refund

1. User submits cancellation request
2. Refund request enters admin queue
3. Admin opens `AdminRefundsSection.vue`
4. Frontend posts to `backend/admin_refund_action.php`
5. Backend updates refund/payment/wallet state
6. Dashboard refresh reflects new values

## 12. Current Architecture Notes

Important characteristics of the current codebase:

- frontend route components live in `src/components/`, not `src/views/`
- dashboard screens use section components plus support files
- backend is endpoint-based, not controller-based MVC
- role switching depends on `localStorage`
- owner/admin dashboards are multi-section shells
- `backend/uploads/turf_images/` contains runtime-uploaded turf images

## 13. Recommended Next Refactor If Needed

If the project grows, the clean next step would be:

1. move route-level screens from `src/components/` to `src/views/`
2. group backend endpoints by domain
3. add a shared API client wrapper
4. unify support-file naming and patterns
5. split admin and owner API endpoints into smaller domain files
