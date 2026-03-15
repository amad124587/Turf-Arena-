<script>
import axios from 'axios'
import AppTopbar from '../components/AppTopbar.vue'
import GlassButton from '../components/GlassButton.vue'
import StatusBadge from '../components/StatusBadge.vue'
import UserSidebar from '../components/UserSidebar.vue'

const API_PROTOCOL = typeof window !== 'undefined' && window.location.protocol.startsWith('http')
  ? window.location.protocol
  : 'http:'
const API_HOST = typeof window !== 'undefined' ? window.location.hostname : '127.0.0.1'
const API_BASE = `${API_PROTOCOL}//${API_HOST}/turfbooking/backend`

export default {
  name: 'UserMyBookingsPage',
  components: {
    AppTopbar,
    GlassButton,
    StatusBadge,
    UserSidebar
  },
  data() {
    return {
      userId: Number(localStorage.getItem('user_id') || 0),
      userName: localStorage.getItem('user_name') || 'User',
      bookings: [],
      loading: false,
      cancelLoadingId: null,
      message: '',
      messageType: 'info',
      activeFilter: 'all',
      statusFilters: [
        { label: 'All', value: 'all' },
        { label: 'Confirmed', value: 'confirmed' },
        { label: 'Pending', value: 'pending' },
        { label: 'Completed', value: 'completed' },
        { label: 'Cancelled', value: 'cancelled' }
      ]
    }
  },
  computed: {
    filteredBookings() {
      if (this.activeFilter === 'all') return this.bookings
      return this.bookings.filter((b) => String(b.booking_status).toLowerCase() === this.activeFilter)
    }
  },
  async mounted() {
    if (!this.userId) {
      this.$router.push('/login')
      return
    }
    await this.loadBookings()
  },
  methods: {
    badgeTone(status) {
      const value = String(status || '').toLowerCase()
      if (value === 'confirmed') return 'success'
      if (value === 'pending') return 'warning'
      if (value === 'cancelled') return 'danger'
      if (value === 'completed') return 'info'
      return 'neutral'
    },
    formatDate(value) {
      if (!value) return 'N/A'
      const d = new Date(value)
      if (Number.isNaN(d.getTime())) return value
      return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
    },
    formatTime(value) {
      if (!value) return 'N/A'
      const txt = String(value)
      const parts = txt.split(':')
      if (parts.length < 2) return txt
      let hour = Number(parts[0])
      const minute = parts[1]
      if (Number.isNaN(hour)) return txt
      const ampm = hour >= 12 ? 'PM' : 'AM'
      hour %= 12
      if (hour === 0) hour = 12
      return `${hour}:${minute} ${ampm}`
    },
    async loadBookings() {
      this.loading = true
      this.message = ''
      try {
        const response = await axios.get(`${API_BASE}/get_user_bookings.php?user_id=${this.userId}`)
        if (response.data?.success) {
          this.bookings = Array.isArray(response.data.bookings) ? response.data.bookings : []
        } else {
          this.message = response.data?.message || 'Could not load bookings.'
          this.messageType = 'error'
        }
      } catch (error) {
        this.message = error.response?.data?.message || 'Server connection failed.'
        this.messageType = 'error'
      } finally {
        this.loading = false
      }
    },
    async cancelBooking(row) {
      this.cancelLoadingId = row.booking_id
      this.message = ''
      try {
        const payload = {
          user_id: this.userId,
          booking_id: row.booking_id
        }
        const response = await axios.post(`${API_BASE}/cancel_booking.php`, payload, {
          headers: { 'Content-Type': 'application/json' }
        })

        if (response.data?.success) {
          this.message = `Cancellation request sent. After admin approval: Refund Tk ${Number(response.data.refund_amount || 0).toFixed(2)} (80%)`
          this.messageType = 'success'
          await this.loadBookings()
        } else {
          this.message = response.data?.message || 'Cancellation failed.'
          this.messageType = 'error'
        }
      } catch (error) {
        this.message = error.response?.data?.message || 'Server connection failed.'
        this.messageType = 'error'
      } finally {
        this.cancelLoadingId = null
      }
    },
    handleSidebarAction(key) {
      if (key === 'dashboard') {
        this.$router.push('/dashboard')
        return
      }
      if (key === 'browse') {
        this.$router.push('/browse')
        return
      }
      if (key === 'bookings') return
      if (key === 'transactions') {
        this.$router.push('/transactions')
        return
      }
      if (key === 'wallet' || key === 'review' || key === 'support') return
    }
  }
}
</script>

<template>
  <div class="min-h-screen box-border bg-[linear-gradient(135deg,#f8fafc_0%,#eef2ff_50%,#f3f4f6_100%)] p-2.5 font-poppins">
    <AppTopbar wrapper-class="mb-3">
      <template #right>
        <div class="font-semibold text-slate-900">My Bookings</div>
      </template>
    </AppTopbar>

    <div class="grid min-h-[calc(100vh-108px)] grid-cols-[248px_minmax(0,1fr)] items-start gap-3.5 max-[900px]:grid-cols-1">
      <UserSidebar
        :user-name="userName"
        active-key="bookings"
        @select="handleSidebarAction"
      />

      <main class="min-w-0 rounded-[20px] border border-white/95 bg-white/80 p-4 backdrop-blur-[14px] shadow-glass">
      <div class="flex flex-wrap items-center justify-between gap-2.5">
        <div>
          <h1 class="m-0 text-[26px] font-bold tracking-[-0.03em] text-slate-900">My Bookings</h1>
          <p class="mt-1 text-sm text-slate-600">Track your reserved slots, booking status, and match schedule.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <GlassButton
            v-for="item in statusFilters"
            :key="item.value"
            class="px-3 py-[7px]"
            :active="activeFilter === item.value"
            @click="activeFilter = item.value"
          >
            {{ item.label }}
          </GlassButton>
        </div>
        <GlassButton @click="loadBookings" :disabled="loading">
          {{ loading ? 'Loading...' : 'Refresh' }}
        </GlassButton>
      </div>

      <p
        v-if="message"
        class="mt-2.5 font-semibold"
        :class="{
          'text-green-700': messageType === 'success',
          'text-red-700': messageType === 'error',
          'text-blue-700': messageType === 'info'
        }"
      >
        {{ message }}
      </p>

      <div v-if="!loading && filteredBookings.length === 0" class="mt-3 rounded-xl border border-white/95 bg-white/80 p-4 backdrop-blur-[14px] shadow-glass">
        No bookings found.
      </div>

      <section class="mt-3 flex flex-col gap-3">
        <article
          v-for="row in filteredBookings"
          :key="row.booking_id"
          class="rounded-[14px] border border-white/95 bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass"
        >
          <div class="mb-2 flex items-center justify-between gap-2.5">
            <h3 class="m-0 text-xl font-semibold text-slate-900">{{ row.turf_name }}</h3>
            <StatusBadge :label="row.booking_status" :tone="badgeTone(row.booking_status)" />
          </div>

          <div class="grid grid-cols-3 gap-2 text-sm text-slate-700 max-[900px]:grid-cols-1">
            <div><b>Date:</b> {{ formatDate(row.slot_date) }}</div>
            <div><b>Time:</b> {{ formatTime(row.start_time) }} - {{ formatTime(row.end_time) }}</div>
            <div><b>Booking ID:</b> #{{ row.booking_id }}</div>
            <div><b>Price:</b> Tk {{ Number(row.booked_price || 0).toFixed(2) }}</div>
            <div><b>Location:</b> {{ row.location || 'N/A' }}</div>
          </div>

          <p v-if="row.booking_status === 'cancelled' && Number(row.refund_amount || 0) > 0" class="mt-2 text-sm text-slate-500">
            Refund details are available in Transaction History.
          </p>

          <div class="mt-2.5 flex justify-end">
            <GlassButton
              v-if="row.can_cancel"
              class="px-3 py-2 font-bold text-red-700"
              :disabled="cancelLoadingId === row.booking_id"
              @click="cancelBooking(row)"
            >
              {{ cancelLoadingId === row.booking_id ? 'Cancelling...' : 'Cancel Booking' }}
            </GlassButton>
          </div>
        </article>
      </section>
      </main>
    </div>
  </div>
</template>
