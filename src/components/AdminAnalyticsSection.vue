<script>
export default {
  name: 'AdminAnalyticsSection',
  props: {
    analytics: { type: Object, required: true },
    formatMoney: { type: Function, required: true }
  }
}
</script>

<template>
  <section class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
    <div>
      <h3 class="m-0 text-[18px] font-bold text-slate-900">System Analytics</h3>
      <p class="mt-2 text-slate-600">Top-rated turfs, active users, revenue trends, booking growth.</p>
    </div>

    <div class="mt-3 grid grid-cols-2 gap-3 max-[1200px]:grid-cols-1">
      <article class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
        <h3 class="m-0 text-[18px] font-bold text-slate-900">Top Rated Turfs</h3>
        <ul v-if="analytics.top_rated_turfs.length" class="mt-3 flex list-none flex-col gap-2.5 p-0">
          <li
            v-for="t in analytics.top_rated_turfs"
            :key="t.turf_id"
            class="flex items-center justify-between gap-2 border-b border-slate-200/70 pb-2 text-sm text-slate-700"
          >
            <span>{{ t.turf_name }}</span>
            <b class="text-slate-900">{{ Number(t.rating_avg || 0).toFixed(2) }}</b>
          </li>
        </ul>
        <p v-else class="mt-3 text-slate-500">No data</p>
      </article>

      <article class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
        <h3 class="m-0 text-[18px] font-bold text-slate-900">Most Active Users</h3>
        <ul v-if="analytics.most_active_users.length" class="mt-3 flex list-none flex-col gap-2.5 p-0">
          <li
            v-for="u in analytics.most_active_users"
            :key="u.user_id"
            class="flex items-center justify-between gap-2 border-b border-slate-200/70 pb-2 text-sm text-slate-700"
          >
            <span>{{ u.full_name }}</span>
            <b class="text-slate-900">{{ u.booking_count }}</b>
          </li>
        </ul>
        <p v-else class="mt-3 text-slate-500">No data</p>
      </article>

      <article class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
        <h3 class="m-0 text-[18px] font-bold text-slate-900">Revenue Trend</h3>
        <ul v-if="analytics.revenue_trend.length" class="mt-3 flex list-none flex-col gap-2.5 p-0">
          <li
            v-for="r in analytics.revenue_trend"
            :key="`rev-${r.month}`"
            class="flex items-center justify-between gap-2 border-b border-slate-200/70 pb-2 text-sm text-slate-700"
          >
            <span>{{ r.month }}</span>
            <b class="text-slate-900">Tk {{ formatMoney(r.revenue) }}</b>
          </li>
        </ul>
        <p v-else class="mt-3 text-slate-500">No data</p>
      </article>

      <article class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
        <h3 class="m-0 text-[18px] font-bold text-slate-900">Booking Growth</h3>
        <ul v-if="analytics.booking_growth.length" class="mt-3 flex list-none flex-col gap-2.5 p-0">
          <li
            v-for="g in analytics.booking_growth"
            :key="`g-${g.month}`"
            class="flex items-center justify-between gap-2 border-b border-slate-200/70 pb-2 text-sm text-slate-700"
          >
            <span>{{ g.month }}</span>
            <b class="text-slate-900">{{ g.bookings }}</b>
          </li>
        </ul>
        <p v-else class="mt-3 text-slate-500">No data</p>
      </article>
    </div>
  </section>
</template>
