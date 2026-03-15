<template>
  <div class="min-h-screen w-full box-border bg-[linear-gradient(135deg,#f8fafc_0%,#eef2ff_50%,#f3f4f6_100%)] p-2.5 font-poppins text-[#111827]">
    <AppTopbar
      wrapper-class="mb-3 w-full rounded-2xl border border-slate-100 bg-white px-4 py-3.5 shadow-[0_8px_20px_rgba(0,0,0,0.08)] backdrop-blur-0 max-md:flex-wrap"
      left-class="gap-2.5 max-md:flex-wrap"
      right-class="gap-1.5 max-md:flex-wrap"
    >
      <template #left>
        <GlassButton v-if="isOwner" class="whitespace-nowrap" @click="switchToOwnerDashboard">Switch Owner Dashboard</GlassButton>
        <GlassButton v-if="isAdmin" class="whitespace-nowrap" @click="switchToAdminDashboard">Switch Admin Dashboard</GlassButton>
      </template>

      <template #right>
        <UserDashboardProfileMenu
          :user-name="userName || 'User'"
          @profile="openProfileMenu"
          @settings="openSettingsMenu"
          @logout="logout"
        />
      </template>
    </AppTopbar>

    <div class="grid min-h-[calc(100vh-108px)] grid-cols-[248px_minmax(0,1fr)] items-start gap-3.5 max-[900px]:grid-cols-1">
      <UserSidebar
        :user-name="userName"
        active-key="dashboard"
        @select="handleSidebarAction"
      />

      <main class="min-w-0 rounded-2xl border border-slate-100 bg-white p-4 shadow-[0_8px_20px_rgba(0,0,0,0.08)]">
        <section class="flex items-start justify-between gap-3 max-[760px]:flex-col">
          <div>
            <h1 class="m-0 text-[28px] font-bold tracking-[-0.04em] text-[#141b33]">User Dashboard</h1>
            <p class="mt-3 text-[16px] text-[#2d3446]">Welcome, <b>{{ userName || 'User' }}</b> - manage your bookings easily.</p>
          </div>

          <div class="min-w-[120px] rounded-2xl border border-slate-100 bg-white px-4 py-3 text-center shadow-[0_8px_20px_rgba(0,0,0,0.08)] transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_24px_rgba(15,23,42,0.1)]">
            <p class="text-[13px] text-slate-600">Rising</p>
            <p class="mt-1 text-[20px] font-bold text-[#141b33]">{{ points }}</p>
            <p class="text-[15px] font-semibold text-[#141b33]">Reward Points</p>
          </div>
        </section>

        <section class="mt-3 grid grid-cols-4 gap-3 max-[1180px]:grid-cols-2 max-[700px]:grid-cols-1">
          <article class="rounded-[14px] border border-white/95 bg-white/88 p-4 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_16px_24px_rgba(20,32,89,0.14)]">
            <div class="flex items-center gap-2 text-[14px] font-semibold text-[#25314d]">
              <span class="flex h-7 w-7 items-center justify-center rounded-full bg-[#d8f0eb] text-[#249780] shadow-[0_6px_12px_rgba(36,151,128,0.18)]">
                <img :src="bookingsIcon" alt="" class="h-4 w-4 object-contain" aria-hidden="true" />
              </span>
              <span>Total Bookings</span>
            </div>
            <p class="mt-3 text-[20px] font-bold leading-none text-[#18223f]">{{ stats.total }}</p>
            <p class="mt-3 text-[13px] font-medium text-[#25a28a]">+12% this month</p>
          </article>

          <article class="rounded-[14px] border border-white/95 bg-white/88 p-4 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_16px_24px_rgba(20,32,89,0.14)]">
            <div class="flex items-center gap-2 text-[14px] font-semibold text-[#25314d]">
              <span class="flex h-7 w-7 items-center justify-center rounded-full bg-[#e7edf7] text-[#6f7c97] shadow-[0_6px_12px_rgba(87,102,138,0.14)]">
                <img :src="upcomingIcon" alt="" class="h-4 w-4 object-contain" aria-hidden="true" />
              </span>
              <span>Upcoming Bookings</span>
            </div>
            <p class="mt-3 text-[20px] font-bold leading-none text-[#18223f]">{{ stats.upcoming }}</p>
          </article>

          <article class="rounded-[14px] border border-white/95 bg-white/88 p-4 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_16px_24px_rgba(20,32,89,0.14)]">
            <div class="flex items-center gap-2 text-[14px] font-semibold text-[#25314d]">
              <span class="flex h-7 w-7 items-center justify-center rounded-full bg-[#e3e8f4] text-[#5d6a86] shadow-[0_6px_12px_rgba(87,102,138,0.14)]">
                <img :src="walletIcon" alt="" class="h-4 w-4 object-contain" aria-hidden="true" />
              </span>
              <span>Wallet Balance</span>
            </div>
            <p class="mt-3 text-[20px] font-bold leading-none text-[#18223f]">Tk 200</p>
            <p class="mt-3 text-[13px] text-[#687389]">Demo wallet balance</p>
          </article>

          <article class="rounded-[14px] border border-white/95 bg-white/88 p-4 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_16px_24px_rgba(20,32,89,0.14)]">
            <div class="flex items-center gap-2 text-[14px] font-semibold text-[#25314d]">
              <span class="flex h-7 w-7 items-center justify-center rounded-full bg-[#fff0d9] text-[#d39b35] shadow-[0_6px_12px_rgba(211,155,53,0.16)]">
                <img :src="rewardIcon" alt="" class="h-4 w-4 object-contain" aria-hidden="true" />
              </span>
              <span>Reward Points</span>
            </div>
            <p class="mt-3 text-[20px] font-bold leading-none text-[#18223f]">{{ points }}</p>
            <p class="mt-3 text-[13px] text-[#687389]">Reward Points</p>
          </article>
        </section>

        <section class="mt-3 grid grid-cols-[1.05fr_1.05fr_1fr] gap-3 max-[1180px]:grid-cols-1">
          <UserUpcomingCard
            ref="upcomingCard"
            :next-booking="nextBooking"
            :pending-review="pendingReview"
            :cancel-loading="cancelUpcomingLoading"
            :action-message="upcomingActionMsg"
            :format-date="formatDate"
            :format-time="formatTime"
            :format-money="formatMoney"
            :status-class="statusClass"
            :status-badge-tone="statusBadgeTone"
            @browse-turfs="goToTurfs"
            @cancel-booking="cancelUpcomingBooking"
            @open-review="openReview"
          />

          <UserActivityCard
            :activities="visibleActivities"
            :format-date-time="formatDateTime"
            @refresh="loadDashboard"
          />

          <UserWalletCard
            :points="points"
            :points-breakdown="pointsBreakdown"
          />
        </section>

        <div class="mt-3 flex justify-end gap-2 max-md:justify-start">
          <button type="button" class="appearance-none rounded-full border border-transparent bg-white/85 px-7 py-3 text-[17px] font-semibold text-slate-900 shadow-glass outline-none backdrop-blur-[14px] transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]" @click="loadAll">Refresh</button>
          <button type="button" class="appearance-none rounded-full border border-transparent bg-white/85 px-7 py-3 text-[17px] font-semibold text-slate-900 shadow-glass outline-none backdrop-blur-[14px] transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]" @click="logout">Logout</button>
        </div>
      </main>
    </div>

    <UserReviewModal
      :visible="showReviewModal"
      :pending-review="pendingReview"
      :rating="rating"
      :comment="comment"
      :message="reviewMsg"
      :loading="reviewLoading"
      @close="closeReview"
      @submit="submitReview"
      @set-rating="rating = $event"
      @update:comment="comment = $event"
    />
  </div>
</template>

<script>
import AppTopbar from './AppTopbar.vue'
import GlassButton from './GlassButton.vue'
import UserUpcomingCard from './UserUpcomingCard.vue'
import UserActivityCard from './UserActivityCard.vue'
import UserDashboardProfileMenu from './UserDashboardProfileMenu.vue'
import UserWalletCard from './UserWalletCard.vue'
import UserReviewModal from './UserReviewModal.vue'
import UserSidebar from './UserSidebar.vue'
import bookingsIcon from '../assets/total-bookings.svg'
import upcomingIcon from '../assets/upcoming-bookings.svg'
import walletIcon from '../assets/wallet-balance.svg'
import rewardIcon from '../assets/reward-points.svg'
import {
  createUserDashboardStats,
  createUserPointsBreakdown,
  getUserSession,
  userDashboardMethods
} from '../support/userDashboardSupport'

export default {
  name: 'UserDashboard',
  components: {
    AppTopbar,
    GlassButton,
    UserUpcomingCard,
    UserActivityCard,
    UserDashboardProfileMenu,
    UserSidebar,
    UserWalletCard,
    UserReviewModal
  },
  data() {
    return {
      userId: localStorage.getItem('user_id'),
      userName: localStorage.getItem('user_name'),
      bookingsIcon,
      upcomingIcon,
      walletIcon,
      rewardIcon,
      stats: createUserDashboardStats(),
      points: 0,
      pointsBreakdown: createUserPointsBreakdown(),
      nextBooking: null,
      activities: [],
      pendingReview: null,
      rating: 0,
      comment: '',
      reviewLoading: false,
      reviewMsg: '',
      showReviewModal: false,
      cancelUpcomingLoading: false,
      upcomingActionMsg: ''
    }
  },
  computed: {
    currentUser() {
      return getUserSession()
    },
    isOwner() {
      return this.currentUser?.role === 'owner'
    },
    isAdmin() {
      return this.currentUser?.role === 'admin'
    },
    visibleActivities() {
      return this.activities.slice(0, 5)
    }
  },
  async mounted() {
    if (!this.userId) {
      this.$router.push('/login')
      return
    }
    await this.loadAll()
  },
  methods: {
    ...userDashboardMethods,
    openProfileMenu() {
      window.scrollTo({ top: 0, behavior: 'smooth' })
    },
    openSettingsMenu() {
      // Demo placeholder for future settings action.
    },
    handleSidebarAction(key) {
      if (key === 'dashboard') return
      if (key === 'browse') {
        this.goToTurfs()
        return
      }
      if (key === 'bookings') {
        this.goToBookings()
        return
      }
      if (key === 'transactions') {
        this.goToTransactions()
        return
      }
      if (key === 'wallet') {
        return
      }
      if (key === 'review') {
        return
      }
      if (key === 'support') {
        return
      }
    }
  }
}
</script>
