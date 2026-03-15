<template>
  <section class="min-h-[calc(100vh-190px)] rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)] max-[900px]:min-h-0">
    <div class="mb-2.5">
      <h3 class="m-0 text-[22px] text-slate-900">Owner Wallet & Cancellation Earnings</h3>
      <p class="mt-2 text-slate-600">Admin approved cancellation gives 20% share to owner wallet.</p>
    </div>

    <div class="mt-2.5 grid grid-cols-4 gap-3 max-[1200px]:grid-cols-2 max-[900px]:grid-cols-1">
      <article class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
        <p class="m-0 text-sm text-slate-900">Current Wallet Balance: <b>Tk {{ formatMoney(ownerFinance.wallet_balance) }}</b></p>
      </article>
      <article class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
        <p class="m-0 text-sm text-slate-900">Total Cancellation Earnings: <b>Tk {{ formatMoney(ownerFinance.total_cancellation_earnings) }}</b></p>
      </article>
      <article class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
        <p class="m-0 text-sm text-slate-900">Cancelled Bookings: <b>{{ ownerFinance.cancelled_bookings }}</b></p>
      </article>
      <article class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
        <p class="m-0 text-sm text-slate-900">Pending Refund Requests: <b>{{ ownerFinance.pending_refund_requests }}</b></p>
      </article>
    </div>

    <div class="mt-4 border-t border-slate-900/10 pt-3">
      <h4 class="m-0 text-base text-slate-900">Recent Wallet Transactions</h4>
      <p v-if="financeLoading" class="mt-2 text-slate-600">Loading wallet data...</p>
      <p v-else-if="!ownerTransactions.length" class="mt-2 text-slate-600">No wallet transactions yet.</p>
      <div v-else class="mt-2.5 flex flex-col gap-2">
        <div v-for="txn in ownerTransactions" :key="txn.txn_id" class="flex items-center justify-between gap-3 rounded-[10px] border border-white/95 bg-white/85 p-[10px_12px] shadow-[0_6px_14px_rgba(15,23,42,0.07)]">
          <span class="text-[13px] text-slate-700">{{ txn.reference_note || txn.txn_type }}</span>
          <b>Tk {{ formatMoney(txn.amount) }}</b>
        </div>
      </div>
      <div class="mt-3 flex justify-end gap-2">
        <button
          type="button"
          class="rounded-[10px] border border-transparent bg-white/80 px-3.5 py-2.5 font-semibold text-slate-900 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)] disabled:cursor-not-allowed disabled:opacity-60"
          @click="$emit('refresh')"
          :disabled="financeLoading"
        >
          {{ financeLoading ? 'Refreshing...' : 'Refresh Wallet' }}
        </button>
      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: 'OwnerRevenueSection',
  props: {
    ownerFinance: { type: Object, required: true },
    ownerTransactions: { type: Array, default: () => [] },
    financeLoading: { type: Boolean, default: false },
    formatMoney: { type: Function, required: true }
  },
  emits: ['refresh']
}
</script>
