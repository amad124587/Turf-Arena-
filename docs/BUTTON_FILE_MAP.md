# Button And File Map

This file maps visible buttons, tabs, and screens to the main files that control them.

## Router

- `src/router/index.js`
  - `/` -> `UserRegister`
  - `/login` -> `UserLogin`
  - `/dashboard` -> `UserDashboard`
  - `/owner-dashboard` -> `OwnerDashboard`
  - `/admin-dashboard` -> `AdminDashboard`
  - `/browse` and `/turfs` -> `UserBrowseTurfs`
  - `/bookings` -> `UserMyBookingsPage`

## User

- `Register`
  - `src/components/UserRegister.vue`
  - `backend/register.php`

- `Login`
  - `src/components/UserLogin.vue`
  - `backend/login.php`

- `User Dashboard`
  - `src/components/UserDashboard.vue`
  - `src/support/userDashboardSupport.js`
  - `backend/user_dashboard.php`
  - `backend/review_pending.php`
  - `backend/review_submit.php`

- `Browse Turfs`
  - `src/components/UserBrowseTurfs.vue`
  - `src/components/UserBrowseTurfCard.vue`
  - `src/support/userBrowseTurfsSupport.js`
  - `backend/get_turfs.php`
  - `backend/get_available_slots.php`
  - `backend/book_slot.php`
  - `backend/validate_promo_code.php`

- `My Bookings`
  - `src/components/UserMyBookingsPage.vue`
  - `backend/get_user_bookings.php`
  - `backend/cancel_booking.php`

## Owner

- `Owner Dashboard Shell`
  - `src/components/OwnerDashboard.vue`
  - `src/support/ownerDashboardSupport.js`

- `Overview`
  - `src/components/OwnerOverviewSection.vue`
  - `backend/owner_finance.php`

- `Add Turfs`
  - `src/components/OwnerAddTurfsSection.vue`
  - `backend/add_turf.php`

- `My Turfs`
  - `src/components/OwnerMyTurfsSection.vue`
  - `src/support/ownerTurfsSupport.js`
  - `backend/owner_my_turfs.php`

- `Bookings`
  - `src/components/OwnerBookingsSection.vue`
  - `src/components/OwnerBookingClientDetailsModal.vue`
  - `src/support/ownerBookingsSupport.js`
  - `backend/owner_booking_clients.php`

- `Revenue`
  - `src/components/OwnerRevenueSection.vue`
  - `backend/owner_finance.php`

- `Slot Control`
  - `src/components/OwnerSlotControlSection.vue`
  - `src/support/ownerSlotControlSupport.js`
  - `backend/owner_slot_control.php`

- `Promo Codes`
  - `src/components/OwnerPromoCodesSection.vue`
  - `src/support/ownerPromoCodesSupport.js`
  - `backend/owner_promo_codes.php`
  - `backend/validate_promo_code.php`
  - `backend/promo_code_helper.php`

- `Fallback Placeholder`
  - `src/components/OwnerPlaceholderSection.vue`

## Admin

- `Admin Dashboard Shell`
  - `src/components/AdminDashboard.vue`
  - `src/support/adminDashboardSupport.js`
  - `backend/admin_dashboard.php`
  - `backend/admin_common.php`

- `Overview`
  - `src/components/AdminOverviewSection.vue`

- `Verify Turfs`
  - `src/components/AdminVerifyTurfsSection.vue`
  - `backend/admin_turf_action.php`

- `Booking Requests`
  - `src/components/AdminBookingRequestsSection.vue`
  - `backend/admin_booking_action.php`

- `Refund Requests`
  - `src/components/AdminRefundsSection.vue`
  - `backend/admin_refund_action.php`

- `Users & Owners`
  - `src/components/AdminUsersOwnersSection.vue`
  - `backend/admin_user_action.php`

- `Analytics`
  - `src/components/AdminAnalyticsSection.vue`
  - `backend/admin_dashboard.php`
