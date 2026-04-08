<script>
import AdminAnalyticsSection from '../components/AdminAnalyticsSection.vue'
import AdminBookingRequestsSection from '../components/AdminBookingRequestsSection.vue'
import AdminOverviewSection from '../components/AdminOverviewSection.vue'
import AdminOwnerEarningsSection from '../components/AdminOwnerEarningsSection.vue'
import AdminRefundsSection from '../components/AdminRefundsSection.vue'
import AdminUsersOwnersSection from '../components/AdminUsersOwnersSection.vue'
import AdminVerifyTurfsSection from '../components/AdminVerifyTurfsSection.vue'
import adminOverviewIcon from '../assets/admin-overview-icon.svg'
import analyticsIcon from '../assets/analytics-icon.svg'
import AppTopbar from '../components/AppTopbar.vue'
import bookingRequestsIcon from '../assets/booking-requests-icon.svg'
import GlassButton from '../components/GlassButton.vue'
import ownerEarningsIcon from '../assets/owner-earnings-icon.svg'
import refundRequestsIcon from '../assets/refund-requests-icon.svg'
import UserDashboardProfileMenu from '../components/UserDashboardProfileMenu.vue'
import usersOwnersIcon from '../assets/users-owners-icon.svg'
import verifyTurfsIcon from '../assets/verify-turfs-icon.svg'
import {
  adminDashboardMethods,
  ADMIN_MENU_ITEMS,
  createAdminAnalytics,
  createAdminOwnerEarningsState,
  createAdminStats,
  getAdminSessionUser
} from '../support/adminDashboardSupport'

export default {
  name: 'AdminDashboard',
  components: {
    AdminAnalyticsSection,
    AdminBookingRequestsSection,
    AdminOverviewSection,
    AdminOwnerEarningsSection,
    AdminRefundsSection,
    AdminUsersOwnersSection,
    AdminVerifyTurfsSection,
    AppTopbar,
    GlassButton,
    UserDashboardProfileMenu
  },
  data() {
    const user = getAdminSessionUser()
    return {
      activeTab: 'overview',
      menuItems: ADMIN_MENU_ITEMS,
      message: '',
      messageType: 'info',
      actionLoadingMap: {},
      turfNotes: {},
      refundNotes: {},
      adminId: Number(localStorage.getItem('admin_id') || user.admin_id || (user.role === 'admin' ? user.user_id : 0) || 0),
      adminName: user.full_name || localStorage.getItem('user_name') || 'Admin',
      stats: createAdminStats(),
      monitorUsers: [],
      monitorOwners: [],
      pendingTurfs: [],
      pendingBookings: [],
      pendingRefunds: [],
      analytics: createAdminAnalytics(),
      ownerEarnings: createAdminOwnerEarningsState()
    }
  },
  computed: {
    adminMenuIcons() {
      return {
        overview: adminOverviewIcon,
        verifyTurfs: verifyTurfsIcon,
        bookingRequests: bookingRequestsIcon,
        refunds: refundRequestsIcon,
        ownerEarnings: ownerEarningsIcon,
        usersOwners: usersOwnersIcon,
        analytics: analyticsIcon
      }
    },
    panelTitle() {
      const item = this.menuItems.find((m) => m.key === this.activeTab)
      return item ? item.label : 'Dashboard'
    }
  },
  async mounted() {
    const user = getAdminSessionUser()
    const hasAdminId = Number(localStorage.getItem('admin_id') || user?.admin_id || (user?.role === 'admin' ? user?.user_id : 0) || 0) > 0
    if (!user || user.role !== 'admin' || !hasAdminId) {
      this.$router.push('/login')
      return
    }

    await this.loadDashboard()
  },
  methods: {
    ...adminDashboardMethods,
    openProfileMenu() {
      this.activeTab = 'overview'
      window.scrollTo({ top: 0, behavior: 'smooth' })
    },
    openSettingsMenu() {
      // Demo placeholder for future settings action.
    },
    logout() {
      localStorage.clear()
      this.$router.push('/login')
    }
  }
}
</script>

<template>
  <div class="min-h-screen w-full box-border bg-[radial-gradient(circle_at_top_left,rgba(217,220,219,0.9),transparent_26%),radial-gradient(circle_at_top_right,rgba(186,195,205,0.34),transparent_22%),linear-gradient(180deg,#f5f6f7_0%,#d9dcdb_34%,#bac3cd_66%,#2d3945_100%)] p-2.5 font-poppins text-[#202833]">
    <AppTopbar
      wrapper-class="mb-3 w-full px-4 py-3.5 max-md:flex-wrap"
      left-class="gap-2.5 max-md:flex-wrap"
      right-class="gap-1.5 max-md:flex-wrap"
    >
      <template #left>
        <GlassButton class="whitespace-nowrap" @click="switchToUserDashboard">Switch User Dashboard</GlassButton>
      </template>

      <template #right>
        <UserDashboardProfileMenu
          :user-name="adminName"
          @profile="openProfileMenu"
          @settings="openSettingsMenu"
          @logout="logout"
        />
      </template>
    </AppTopbar>

    <div class="grid min-h-[calc(100vh-108px)] grid-cols-[248px_minmax(0,1fr)] items-start gap-3.5 max-[900px]:grid-cols-1">
      <aside class="sticky top-2 rounded-[20px] border border-transparent bg-white/80 p-3 shadow-glass backdrop-blur-[14px] max-[900px]:static">
        <div class="mb-3 rounded-[16px] border border-transparent bg-white/85 px-3.5 py-3 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
          <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Admin Panel</p>
          <h2 class="mt-1 text-lg font-bold text-slate-900">{{ adminName }}</h2>
        </div>

        <nav class="flex flex-col gap-2.5">
          <button
            v-for="item in menuItems"
            :key="item.key"
            type="button"
            class="flex items-center gap-3 rounded-[14px] border border-transparent px-3.5 py-3 text-left font-medium text-slate-900 backdrop-blur-[14px] shadow-glass transition duration-200"
            :class="activeTab === item.key ? 'scale-[1.02] bg-slate-900 text-white shadow-[0_16px_24px_rgba(15,23,42,0.18)]' : 'bg-white/75 hover:-translate-y-0.5 hover:scale-[1.02] hover:bg-white/95 hover:font-semibold hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]'"
            @click="openAdminTab(item.key)"
          >
            <img
              :src="adminMenuIcons[item.key]"
              :alt="`${item.label} icon`"
              class="h-6 w-6 shrink-0 object-contain"
              :class="activeTab === item.key ? 'brightness-0 invert' : ''"
            />
            <span>{{ item.label }}</span>
          </button>
        </nav>
      </aside>

      <main class="min-w-0 rounded-[20px] border border-transparent bg-white/80 p-3.5 shadow-glass backdrop-blur-[14px]">
        <header class="mb-3.5 border-b border-slate-900/10 pb-3.5">
          <h1 class="m-0 text-[40px] font-bold text-slate-900 max-md:text-[22px]">{{ panelTitle }}</h1>
        </header>

        <transition name="page-fade" mode="out-in">
          <AdminOverviewSection
            v-if="activeTab === 'overview'"
            key="admin-overview"
            :stats="stats"
            :pending-turfs="pendingTurfs"
            :pending-bookings="pendingBookings"
            :pending-refunds="pendingRefunds"
            :format-money="formatMoney"
          />

          <AdminVerifyTurfsSection
            v-else-if="activeTab === 'verifyTurfs'"
            key="admin-verify-turfs"
            :pending-turfs="pendingTurfs"
            :notes="turfNotes"
            :is-action-loading="isActionLoading"
            :format-money="formatMoney"
            @update-note="turfNotes = { ...turfNotes, [$event.id]: $event.value }"
            @review="reviewTurf($event.item, $event.action)"
          />

          <AdminBookingRequestsSection
            v-else-if="activeTab === 'bookingRequests'"
            key="admin-booking-requests"
            :pending-bookings="pendingBookings"
            :is-action-loading="isActionLoading"
            :format-money="formatMoney"
            :format-date="formatDate"
            :format-time="formatTime"
            @review="reviewBooking($event.item, $event.action)"
          />

          <AdminRefundsSection
            v-else-if="activeTab === 'refunds'"
            key="admin-refunds"
            :pending-refunds="pendingRefunds"
            :notes="refundNotes"
            :is-action-loading="isActionLoading"
            :format-money="formatMoney"
            @update-note="refundNotes = { ...refundNotes, [$event.id]: $event.value }"
            @review="reviewRefund($event.item, $event.action)"
          />

          <AdminOwnerEarningsSection
            v-else-if="activeTab === 'ownerEarnings'"
            key="admin-owner-earnings"
            :owners="ownerEarnings.owners"
            :selected-owner="ownerEarnings.selectedOwner"
            :selected-turf-id="ownerEarnings.selectedTurfId"
            :selected-month="ownerEarnings.selectedMonth"
            :loading="ownerEarnings.loading"
            :format-money="formatMoney"
            @select-owner="selectOwnerEarnings"
            @select-turf="selectOwnerTurf"
            @refresh="loadOwnerEarnings(ownerEarnings.selectedOwner?.owner_id || 0, false)"
            @set-month="setOwnerEarningsMonth"
            @show-lifetime="showOwnerEarningsLifetime"
          />

          <AdminUsersOwnersSection
            v-else-if="activeTab === 'usersOwners'"
            key="admin-users-owners"
            :monitor-users="monitorUsers"
            :monitor-owners="monitorOwners"
            :status-tone="statusTone"
            :is-action-loading="isActionLoading"
            @toggle="toggleUserStatus($event.type, $event.id, $event.action)"
          />

          <AdminAnalyticsSection
            v-else
            key="admin-analytics"
            :analytics="analytics"
            :format-money="formatMoney"
          />
        </transition>

        <p
          v-if="message"
          class="mt-3 text-sm font-semibold"
          :class="messageType === 'success' ? 'text-green-700' : messageType === 'error' ? 'text-red-700' : 'text-slate-600'"
        >
          {{ message }}
        </p>
      </main>
    </div>
  </div>
</template>
