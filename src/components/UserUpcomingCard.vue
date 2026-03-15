<template>
  <article class="flex min-h-[420px] flex-col rounded-2xl border border-slate-100 bg-white p-[32px_22px_18px] text-slate-900 shadow-[0_8px_20px_rgba(0,0,0,0.08)] transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_12px_22px_rgba(0,0,0,0.1)]">
    <h3 class="mb-4 text-[24px] font-semibold tracking-[-0.03em] text-[#141b33]">Upcoming Booking</h3>

    <div v-if="nextBooking" class="flex-1 text-[17px] text-[#30384d]">
      <div class="mb-1.5"><b class="text-[#1b2236]">{{ nextBooking.turf_name || 'Turf' }}</b></div>
      <div class="mb-1.5">{{ formatDate(nextBooking.slot_date) }}</div>
      <div class="mb-1.5">
        {{ formatTime(nextBooking.start_time) }} - {{ formatTime(nextBooking.end_time) }}
      </div>
      <div class="mb-1.5">Price: Tk {{ formatMoney(nextBooking.booked_price) }}</div>
      <StatusBadge
        class="mt-2"
        :label="nextBooking.booking_status || 'pending'"
        :tone="statusBadgeTone(nextBooking.booking_status)"
      />
    </div>

    <div v-else class="flex flex-1 flex-col items-center justify-center text-center text-[#30384d]">
      <img :src="footballImage" alt="" class="h-[88px] w-auto object-contain" aria-hidden="true" />
      <p class="mt-4 text-[20px] font-semibold tracking-[-0.03em] text-[#1b2236]">No upcoming booking</p>
      <p class="mt-2 text-[15px] text-[#5c667d]">Browse turfs to reserve your next slot</p>
      <button
        type="button"
        class="mt-6 appearance-none rounded-[16px] border border-[#16213d] bg-[linear-gradient(180deg,#16213d_0%,#0f1830_100%)] px-6 py-3 text-[16px] font-semibold text-white shadow-[0_16px_28px_rgba(15,24,48,0.28)] outline-none transition duration-200 hover:-translate-y-0.5 hover:bg-[linear-gradient(180deg,#1a2748_0%,#121d37_100%)] hover:shadow-[0_18px_30px_rgba(15,24,48,0.34)]"
        @click="$emit('browse-turfs')"
      >
        Browse Turfs
      </button>
    </div>

    <div v-if="nextBooking && statusClass(nextBooking.booking_status) === 'confirmed' && nextBooking.refund_request_status !== 'pending'" class="mt-auto flex flex-wrap gap-2">
      <button
        type="button"
        class="appearance-none rounded-[14px] border border-transparent bg-white/75 px-4 py-3 font-semibold text-slate-900 shadow-glass outline-none backdrop-blur-[14px] transition duration-200 hover:-translate-y-0.5 hover:scale-[1.02] hover:bg-white/95 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)] disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="cancelLoading"
        @click="$emit('cancel-booking')"
      >
        {{ cancelLoading ? 'Cancelling...' : 'Cancel Booking' }}
      </button>
    </div>
    <div v-if="pendingReview" class="mt-3 flex flex-wrap gap-2">
      <button
        type="button"
        class="appearance-none rounded-[14px] border border-transparent bg-white/75 px-4 py-3 font-semibold text-slate-900 shadow-glass outline-none backdrop-blur-[14px] transition duration-200 hover:-translate-y-0.5 hover:scale-[1.02] hover:bg-white/95 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]"
        @click="$emit('open-review')"
      >
        Rate Completed Booking
      </button>
    </div>
    <p v-if="nextBooking && nextBooking.refund_request_status === 'pending'" class="mt-2 text-sm text-[#111827]">Cancellation request is pending admin approval.</p>
    <p v-if="actionMessage" class="mt-2 text-sm text-[#111827]">{{ actionMessage }}</p>
  </article>
</template>

<script>
import StatusBadge from './StatusBadge.vue'
import footballImage from '../assets/football.png'

export default {
  name: 'UserUpcomingCard',
  components: {
    StatusBadge
  },
  data() {
    return {
      footballImage
    }
  },
  props: {
    nextBooking: {
      type: Object,
      default: null
    },
    pendingReview: {
      type: Object,
      default: null
    },
    cancelLoading: {
      type: Boolean,
      default: false
    },
    actionMessage: {
      type: String,
      default: ''
    },
    formatDate: {
      type: Function,
      required: true
    },
    formatTime: {
      type: Function,
      required: true
    },
    formatMoney: {
      type: Function,
      required: true
    },
    statusClass: {
      type: Function,
      required: true
    },
    statusBadgeTone: {
      type: Function,
      required: true
    }
  },
  emits: ['browse-turfs', 'cancel-booking', 'open-review']
}
</script>
