<template>
  <section class="space-y-3.5">
    <div class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
      <div class="flex items-start justify-between gap-3 max-[900px]:flex-col">
        <div>
          <h3 class="m-0 text-[22px] font-bold text-slate-900">Client Details Access</h3>
          <p class="mt-2 text-slate-600">Open any booking and instantly see that customer’s contact details without leaving the owner panel.</p>
        </div>
        <div class="rounded-[12px] border border-transparent bg-white/85 px-4 py-3 shadow-glass">
          <p class="m-0 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total Bookings</p>
          <p class="mt-1 text-base font-bold text-slate-900">{{ filteredBookings.length }}</p>
        </div>
      </div>

      <div class="mt-4 flex flex-wrap items-end gap-3">
        <label class="flex min-w-[280px] flex-1 flex-col gap-1.5 max-[640px]:min-w-0">
          <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Search Booking or Client</span>
          <input v-model.trim="search" type="text" placeholder="Booking ID, client name, email, turf..." class="rounded-[10px] border border-transparent bg-white/85 px-3 py-2.5 text-sm text-slate-900 shadow-glass outline-none" />
        </label>
        <button type="button" class="rounded-[10px] border border-transparent bg-[#3361d8] px-3.5 py-2.5 font-semibold text-white shadow-[0_10px_20px_rgba(51,97,216,0.3)] transition duration-200 hover:-translate-y-0.5 hover:bg-[#2f57c2] hover:shadow-[0_14px_20px_rgba(51,97,216,0.28)] disabled:cursor-not-allowed disabled:opacity-60" :disabled="loading" @click="loadBookings">
          {{ loading ? 'Refreshing...' : 'Refresh List' }}
        </button>
      </div>

      <p v-if="message" class="mt-3 rounded-[10px] px-3 py-2 text-sm font-semibold" :class="messageType === 'error' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-700'">
        {{ message }}
      </p>
    </div>

    <section class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
      <div v-if="!filteredBookings.length" class="rounded-[12px] border border-dashed border-slate-300 bg-white/70 px-4 py-10 text-center text-slate-600">
        No owner bookings found for client access.
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full border-separate border-spacing-y-2 text-left">
          <thead>
            <tr class="text-xs uppercase tracking-[0.18em] text-slate-500">
              <th class="px-3 py-2">Booking</th>
              <th class="px-3 py-2">Turf</th>
              <th class="px-3 py-2">Date & Slot</th>
              <th class="px-3 py-2">Client</th>
              <th class="px-3 py-2">Status</th>
              <th class="px-3 py-2">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="booking in filteredBookings"
              :key="booking.booking_id"
              class="rounded-[12px] bg-white/75 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white/90 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]"
            >
              <td class="rounded-l-[12px] px-3 py-3 align-top">
                <div class="font-semibold text-slate-900">#{{ booking.booking_id }}</div>
                <p class="mt-1 text-xs text-slate-500">Tk {{ formatMoney(booking.booked_price) }}</p>
              </td>
              <td class="px-3 py-3 align-top">
                <div class="font-semibold text-slate-900">{{ booking.turf_name }}</div>
                <p class="mt-1 text-sm text-slate-600">{{ booking.sport_type || 'General' }}</p>
              </td>
              <td class="px-3 py-3 align-top">
                <div class="font-semibold text-slate-900">{{ formatDate(booking.slot_date) }}</div>
                <p class="mt-1 text-sm text-slate-600">{{ formatTime(booking.start_time) }} - {{ formatTime(booking.end_time) }}</p>
              </td>
              <td class="px-3 py-3 align-top">
                <div class="font-semibold text-slate-900">{{ booking.client.full_name }}</div>
                <p class="mt-1 text-sm text-slate-600">{{ booking.client.email }}</p>
              </td>
              <td class="px-3 py-3 align-top">
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusTone(booking.booking_status)">
                  {{ booking.booking_status }}
                </span>
              </td>
              <td class="rounded-r-[12px] px-3 py-3 align-top">
                <button type="button" class="rounded-[10px] border border-transparent bg-white/80 px-3.5 py-2 text-sm font-semibold text-slate-900 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]" @click="openClientDetails(booking)">View Client</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <OwnerBookingClientDetailsModal
      :visible="detailsOpen"
      :booking="selectedBooking"
      :format-date="formatDate"
      :format-time="formatTime"
      :format-money="formatMoney"
      :status-tone="statusTone"
      @close="closeClientDetails"
      @email="emailClient"
      @call="callClient"
    />
  </section>
</template>

<script>
import {
  fetchOwnerBookingClients,
  filterOwnerClientBookings,
  formatOwnerClientDate,
  formatOwnerClientMoney,
  formatOwnerClientTime,
  getOwnerClientStatusTone
} from '../support/ownerBookingsSupport'
import OwnerBookingClientDetailsModal from './OwnerBookingClientDetailsModal.vue'

export default {
  name: 'OwnerBookingsSection',
  components: {
    OwnerBookingClientDetailsModal
  },
  props: {
    ownerId: { type: Number, required: true }
  },
  data() {
    return {
      loading: false,
      message: '',
      messageType: 'info',
      bookings: [],
      search: '',
      detailsOpen: false,
      selectedBooking: null
    }
  },
  computed: {
    filteredBookings() {
      return filterOwnerClientBookings(this.bookings, this.search)
    }
  },
  async mounted() {
    await this.loadBookings()
  },
  methods: {
    formatDate: formatOwnerClientDate,
    formatTime: formatOwnerClientTime,
    formatMoney: formatOwnerClientMoney,
    statusTone: getOwnerClientStatusTone,
    async loadBookings() {
      if (!this.ownerId) return
      this.loading = true
      this.message = ''
      try {
        const data = await fetchOwnerBookingClients(this.ownerId)
        if (!data?.success) {
          this.messageType = 'error'
          this.message = data?.message || 'Failed to load client details.'
          return
        }
        this.bookings = Array.isArray(data.bookings) ? data.bookings : []
      } catch (error) {
        this.messageType = 'error'
        this.message = error.response?.data?.message || 'Server connection failed while loading booking clients.'
      } finally {
        this.loading = false
      }
    },
    openClientDetails(booking) {
      this.selectedBooking = booking
      this.detailsOpen = true
    },
    closeClientDetails() {
      this.detailsOpen = false
      this.selectedBooking = null
    },
    emailClient(booking) {
      const email = booking?.client?.email || ''
      if (!email) return
      window.location.href = `mailto:${email}?subject=${encodeURIComponent(`Booking #${booking.booking_id} - Turf Arena`)}` 
    },
    callClient(booking) {
      const phone = booking?.client?.phone || ''
      if (!phone) return
      window.location.href = `tel:${phone}`
    }
  }
}
</script>
