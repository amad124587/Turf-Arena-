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
    },
    taskCards() {
      return [
        {
          title: 'Slot Updates Needed',
          value: '2',
          note: '2 turfs need slot update',
          badgeClass: 'bg-[#e7edf7] text-[#6f7c97]',
          dotClass: 'bg-[#6f7c97]',
          noteClass: 'font-medium text-[#4b5a79]'
        },
        {
          title: 'Refund Review Queue',
          value: '1',
          note: '1 refund awaiting review',
          badgeClass: 'bg-[#fff0d9] text-[#d39b35]',
          dotClass: 'bg-[#d39b35]',
          noteClass: 'font-medium text-[#a06c10]'
        },
        {
          title: 'Bookings Today',
          value: '3',
          note: '3 bookings today',
          badgeClass: 'bg-[#d8f0eb] text-[#249780]',
          dotClass: 'bg-[#249780]',
          noteClass: 'font-medium text-[#25a28a]'
        },
        {
          title: 'Rejected Turf Edits',
          value: '1',
          note: '1 rejected turf needs edit',
          badgeClass: 'bg-[#fde6e8] text-[#d05a67]',
          dotClass: 'bg-[#d05a67]',
          noteClass: 'font-medium text-[#c24153]'
        }
      ]
    }
  }
}
</script>

<template>
  <section class="space-y-3">
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

    <div class="grid grid-cols-4 gap-3 max-[1200px]:grid-cols-2 max-[700px]:grid-cols-1">
      <article
        v-for="item in taskCards"
        :key="item.title"
        class="rounded-[14px] border border-white/95 bg-white/88 p-4 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_16px_24px_rgba(20,32,89,0.14)]"
      >
        <div class="flex items-center gap-2 text-[14px] font-semibold text-[#25314d]">
          <span
            class="flex h-7 w-7 items-center justify-center rounded-full shadow-[0_6px_12px_rgba(87,102,138,0.14)]"
            :class="item.badgeClass"
          >
            <span class="h-2.5 w-2.5 rounded-full" :class="item.dotClass"></span>
          </span>
          <span>{{ item.title }}</span>
        </div>
        <p class="mt-3 text-[20px] font-bold leading-none text-[#18223f]">{{ item.value }}</p>
        <p class="mt-3 text-[13px]" :class="item.noteClass">{{ item.note }}</p>
      </article>
    </div>
  </section>
</template>
