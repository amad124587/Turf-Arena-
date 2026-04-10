<script>
export default {
  name: 'BookingRequestsSection',
  props: {
    pendingBookings: { type: Array, default: () => [] },
    isActionLoading: { type: Function, required: true },
    formatMoney: { type: Function, required: true },
    formatDate: { type: Function, required: true },
    formatTime: { type: Function, required: true },
    title: { type: String, default: 'Accept / Reject Booking Requests' },
    subtitle: { type: String, default: 'Points are awarded instantly when a booking is confirmed.' },
    emptyText: { type: String, default: 'No pending booking requests.' },
    primaryActionLabel: { type: String, default: 'Accept' },
    secondaryActionLabel: { type: String, default: 'Reject' }
  },
  emits: ['review']
}
</script>

<template>
  <section class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
    <div>
      <h3 class="m-0 text-[18px] font-bold text-slate-900">{{ title }}</h3>
      <p class="mt-2 text-slate-600">{{ subtitle }}</p>
    </div>

    <p v-if="!pendingBookings.length" class="mt-3 text-slate-500">{{ emptyText }}</p>

    <div v-else class="mt-3 grid grid-cols-2 gap-3 max-[1200px]:grid-cols-1">
      <article
        v-for="item in pendingBookings"
        :key="item.booking_id"
        class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]"
      >
        <h4 class="m-0 text-[18px] font-bold text-slate-900">Booking #{{ item.booking_id }} - {{ item.turf_name }}</h4>
        <div class="mt-3 space-y-1.5 text-sm text-slate-700">
          <p class="m-0"><b class="text-slate-900">User:</b> {{ item.user_name }} ({{ item.user_email }})</p>
          <p class="m-0"><b class="text-slate-900">Date:</b> {{ formatDate(item.slot_date) }}</p>
          <p class="m-0"><b class="text-slate-900">Time:</b> {{ formatTime(item.start_time) }} - {{ formatTime(item.end_time) }}</p>
          <p class="m-0"><b class="text-slate-900">Price:</b> Tk {{ formatMoney(item.booked_price) }}</p>
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
          <button
            type="button"
            class="rounded-[10px] border border-transparent bg-[#3361d8] px-3.5 py-2 font-semibold text-white shadow-[0_10px_20px_rgba(51,97,216,0.3)] transition duration-200 hover:-translate-y-0.5 hover:bg-[#2f57c2] hover:shadow-[0_14px_20px_rgba(51,97,216,0.28)] disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="isActionLoading('booking', item.booking_id)"
            @click="$emit('review', { item, action: 'confirm' })"
          >
            {{ primaryActionLabel }}
          </button>
          <button
            type="button"
            class="rounded-[10px] border border-transparent bg-white/80 px-3.5 py-2 font-semibold text-[#991b1b] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)] disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="isActionLoading('booking', item.booking_id)"
            @click="$emit('review', { item, action: 'reject' })"
          >
            {{ secondaryActionLabel }}
          </button>
        </div>
      </article>
    </div>
  </section>
</template>
