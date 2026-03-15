<script>
export default {
  name: 'OwnerBookingClientDetailsModal',
  props: {
    visible: { type: Boolean, default: false },
    booking: { type: Object, default: null },
    formatDate: { type: Function, required: true },
    formatTime: { type: Function, required: true },
    formatMoney: { type: Function, required: true },
    statusTone: { type: Function, required: true }
  },
  emits: ['close', 'email', 'call']
}
</script>

<template>

<div v-if="visible && booking" class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/30 p-4 backdrop-blur-sm" @click.self="$emit('close')">

  <div class="w-full max-w-[760px] rounded-[20px] border border-transparent bg-white/90 p-4 shadow-[0_24px_64px_rgba(15,23,42,0.18)] backdrop-blur-[18px]">

    <div class="flex items-start justify-between gap-3 border-b border-slate-900/10 pb-3">

      <div>

        <h3 class="m-0 text-[26px] font-bold text-slate-900">Client Details</h3>
          <p class="mt-1 text-slate-600">Booking #{{ booking.booking_id }} · {{ booking.turf_name }}</p>
        </div>
        <button type="button" class="rounded-[10px] border border-transparent bg-white/80 px-3.5 py-2 font-semibold text-slate-900 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]" @click="$emit('close')">Close</button>
      </div>

      <div class="mt-4 grid grid-cols-2 gap-3 max-[760px]:grid-cols-1">
        <article class="rounded-[14px] border border-transparent bg-white/80 p-3.5 shadow-glass">
          <h4 class="m-0 text-lg font-bold text-slate-900">Client Contact</h4>
          <p class="mt-3 text-sm text-slate-600">Full Name</p>
          <p class="m-0 font-semibold text-slate-900">{{ booking.client.full_name }}</p>
          <p class="mt-3 text-sm text-slate-600">Email</p>
          <p class="m-0 font-semibold text-slate-900">{{ booking.client.email }}</p>
          <p class="mt-3 text-sm text-slate-600">Phone</p>
          <p class="m-0 font-semibold text-slate-900">{{ booking.client.phone || 'No phone added' }}</p>
        </article>

        <article class="rounded-[14px] border border-transparent bg-white/80 p-3.5 shadow-glass">
          <h4 class="m-0 text-lg font-bold text-slate-900">Booking Snapshot</h4>
          <p class="mt-3 text-sm text-slate-600">Status</p>
          <p class="m-0"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusTone(booking.booking_status)">{{ booking.booking_status }}</span></p>
          <p class="mt-3 text-sm text-slate-600">Date</p>
          <p class="m-0 font-semibold text-slate-900">{{ formatDate(booking.slot_date) }}</p>
          <p class="mt-3 text-sm text-slate-600">Slot</p>
          <p class="m-0 font-semibold text-slate-900">{{ formatTime(booking.start_time) }} - {{ formatTime(booking.end_time) }}</p>
          <p class="mt-3 text-sm text-slate-600">Amount</p>
          <p class="m-0 font-semibold text-slate-900">Tk {{ formatMoney(booking.booked_price) }}</p>
        </article>
      </div>

      <div class="mt-4 flex flex-wrap justify-end gap-2">
        <button type="button" class="rounded-[10px] border border-transparent bg-white/80 px-3.5 py-2.5 font-semibold text-slate-900 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]" @click="$emit('email', booking)">Email Client</button>
        <button type="button" class="rounded-[10px] border border-transparent bg-[#3361d8] px-3.5 py-2.5 font-semibold text-white shadow-[0_10px_20px_rgba(51,97,216,0.3)] transition duration-200 hover:-translate-y-0.5 hover:bg-[#2f57c2] hover:shadow-[0_14px_20px_rgba(51,97,216,0.28)]" @click="$emit('call', booking)">Call Client</button>
      </div>
    </div>
  </div>
</template>
