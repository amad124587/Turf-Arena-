<template>
  <section class="space-y-3.5">
    <div class="grid grid-cols-4 gap-3 max-[1200px]:grid-cols-2 max-[900px]:grid-cols-1">
      <article
        v-for="item in statCards"
        :key="item.label"
        class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]"
      >
        <p class="m-0 text-sm text-slate-600">{{ item.label }}</p>
        <b class="mt-2 block text-[22px] text-slate-900">{{ item.value }}</b>
      </article>
    </div>

    <section class="min-h-[calc(100vh-264px)] rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)] max-[900px]:min-h-0">
      <h3 class="m-0 text-[22px] font-bold text-slate-900">Module Workspace</h3>
      <p class="mt-2 text-slate-600">You can add new owner feature buttons and modules here later.</p>

      <div class="mt-4 grid grid-cols-3 gap-2.5 max-[1200px]:grid-cols-2 max-[900px]:grid-cols-1">
        <button type="button" class="min-h-11 rounded-[10px] border border-dashed border-blue-500/45 bg-white/80 p-[10px_12px] font-semibold text-slate-700 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:border-blue-500/70 hover:bg-white hover:text-blue-900 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">+ Add Feature Button</button>
        <button type="button" class="min-h-11 rounded-[10px] border border-dashed border-blue-500/45 bg-white/80 p-[10px_12px] font-semibold text-slate-700 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:border-blue-500/70 hover:bg-white hover:text-blue-900 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">+ Add Shortcut Button</button>
        <button type="button" class="min-h-11 rounded-[10px] border border-dashed border-blue-500/45 bg-white/80 p-[10px_12px] font-semibold text-slate-700 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:border-blue-500/70 hover:bg-white hover:text-blue-900 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">+ Add Report Button</button>
        <button type="button" class="min-h-11 rounded-[10px] border border-dashed border-blue-500/45 bg-white/80 p-[10px_12px] font-semibold text-slate-700 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:border-blue-500/70 hover:bg-white hover:text-blue-900 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">+ Add Custom Module</button>
      </div>
    </section>
  </section>
</template>

<script>
export default {
  name: 'OwnerOverviewSection',
  props: {
    ownerFinance: {
      type: Object,
      required: true
    },
    formatMoney: {
      type: Function,
      required: true
    }
  },
  computed: {
    statCards() {
      return [
        { label: 'Owner Wallet Balance', value: `Tk ${this.formatMoney(this.ownerFinance.wallet_balance)}` },
        { label: 'Cancellation Earnings (20%)', value: `Tk ${this.formatMoney(this.ownerFinance.total_cancellation_earnings)}` },
        { label: 'Active Turfs', value: this.ownerFinance.active_turfs },
        { label: 'Pending Refund Requests', value: this.ownerFinance.pending_refund_requests }
      ]
    }
  }
}
</script>
