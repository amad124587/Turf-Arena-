<script>
export default {
  name: 'UserWalletCard',
  computed: {
    rows() {
      return [
        {
          key: 'booking',
          label: 'From confirmed/completed bookings',
          value: this.pointsBreakdown.booking
        },
        {
          key: 'review',
          label: 'Reviews',
          value: this.pointsBreakdown.review
        },
        {
          key: 'referral',
          label: 'Referral / Promo bonus',
          value: this.pointsBreakdown.referral
        },
        {
          key: 'resell',
          label: 'Slot resell',
          value: this.pointsBreakdown.resell
        }
      ]
    }
  },
  props: {
    points: {
      type: Number,
      default: 0
    },
    pointsBreakdown: {
      type: Object,
      default: () => ({
        booking: 0,
        review: 0,
        referral: 0,
        resell: 0
      })
    }
  }
}
</script>

<template>
  <article class="flex min-h-[420px] flex-col rounded-2xl border border-slate-100 bg-white p-[32px_18px_18px] text-slate-900 shadow-[0_8px_20px_rgba(0,0,0,0.08)] transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_12px_22px_rgba(0,0,0,0.1)]">
    <h3 class="mb-4 text-[24px] font-semibold tracking-[-0.03em] text-[#141b33]">Wallet / Points Breakdown</h3>

    <div v-for="item in rows" :key="item.key" class="mb-4">
      <div class="flex items-center justify-between gap-2.5 text-[17px] text-[#2f374a]">
        <span>{{ item.label }}</span>
        <b class="text-[#1a2135]">{{ item.value }}</b>
      </div>
    </div>

    <div class="mt-auto flex items-center justify-between pt-3 text-[18px] font-bold text-[#1a2135]">
      <span>Total Reward Points</span>
      <b>{{ points }}</b>
    </div>
  </article>
</template>
