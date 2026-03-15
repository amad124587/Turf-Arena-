<template>
  <section class="space-y-3.5">
    <div class="grid grid-cols-4 gap-3 max-[1200px]:grid-cols-2 max-[900px]:grid-cols-1">
      <article
        v-for="item in topStats"
        :key="item.label"
        class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]"
      >
        <p class="m-0 text-sm text-slate-600">{{ item.label }}</p>
        <b class="mt-2 block text-[22px] text-slate-900">{{ item.value }}</b>
      </article>
    </div>

    <div class="grid grid-cols-4 gap-3 max-[1200px]:grid-cols-2 max-[900px]:grid-cols-1">
      <article
        v-for="item in lowerStats"
        :key="item.label"
        class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]"
      >
        <p class="m-0 text-sm text-slate-600">{{ item.label }}</p>
        <b class="mt-2 block text-[22px] text-slate-900">{{ item.value }}</b>
      </article>
    </div>

    <div class="grid grid-cols-3 gap-3 max-[1200px]:grid-cols-2 max-[900px]:grid-cols-1">
      <article class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
        <h3 class="m-0 text-[18px] font-bold text-slate-900">Pending Turf Queue</h3>
        <p v-if="!pendingTurfs.length" class="mt-3 text-slate-500">No pending turf requests.</p>
        <ul v-else class="mt-3 flex list-none flex-col gap-2.5 p-0">
          <li
            v-for="item in pendingTurfs.slice(0, 5)"
            :key="item.turf_id"
            class="flex items-center justify-between gap-2 border-b border-slate-200/70 pb-2 text-sm text-slate-700"
          >
            <span>{{ item.turf_name }} ({{ item.owner_name }})</span>
            <b class="text-slate-900">{{ item.sport_type }}</b>
          </li>
        </ul>
      </article>

      <article class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
        <h3 class="m-0 text-[18px] font-bold text-slate-900">Pending Booking Requests</h3>
        <p v-if="!pendingBookings.length" class="mt-3 text-slate-500">No pending bookings.</p>
        <ul v-else class="mt-3 flex list-none flex-col gap-2.5 p-0">
          <li
            v-for="item in pendingBookings.slice(0, 5)"
            :key="item.booking_id"
            class="flex items-center justify-between gap-2 border-b border-slate-200/70 pb-2 text-sm text-slate-700"
          >
            <span>#{{ item.booking_id }} - {{ item.user_name }}</span>
            <b class="text-slate-900">{{ item.turf_name }}</b>
          </li>
        </ul>
      </article>

      <article class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
        <h3 class="m-0 text-[18px] font-bold text-slate-900">Pending Refund Requests</h3>
        <p v-if="!pendingRefunds.length" class="mt-3 text-slate-500">No pending refunds.</p>
        <ul v-else class="mt-3 flex list-none flex-col gap-2.5 p-0">
          <li
            v-for="item in pendingRefunds.slice(0, 5)"
            :key="item.refund_id"
            class="flex items-center justify-between gap-2 border-b border-slate-200/70 pb-2 text-sm text-slate-700"
          >
            <span>#{{ item.refund_id }} - {{ item.user_name }}</span>
            <b class="text-slate-900">Tk {{ formatMoney(item.requested_amount) }}</b>
          </li>
        </ul>
      </article>
    </div>
  </section>
</template>

<script>
export default {
  name: 'AdminOverviewSection',
  props: {
    stats: { type: Object, required: true },
    pendingTurfs: { type: Array, default: () => [] },
    pendingBookings: { type: Array, default: () => [] },
    pendingRefunds: { type: Array, default: () => [] },
    formatMoney: { type: Function, required: true }
  },
  computed: {
    topStats() {
      return [
        { label: 'Pending Turfs', value: this.stats.pending_turfs },
        { label: 'Pending Bookings', value: this.stats.pending_bookings },
        { label: 'Pending Refunds', value: this.stats.pending_refunds },
        { label: "Today's Revenue", value: `Tk ${this.formatMoney(this.stats.today_revenue)}` }
      ]
    },
    lowerStats() {
      return [
        { label: 'Active Users', value: this.stats.users_active },
        { label: 'Banned Users', value: this.stats.users_banned },
        { label: 'Verified Owners', value: this.stats.owners_verified },
        { label: 'Suspended Owners', value: this.stats.owners_suspended }
      ]
    }
  }
}
</script>
