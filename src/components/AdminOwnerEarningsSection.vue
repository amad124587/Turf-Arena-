<script>
export default {
  name: 'AdminOwnerEarningsSection',
  props: {
    owners: { type: Array, default: () => [] },
    selectedOwner: { type: Object, default: null },
    selectedTurfId: { type: Number, default: 0 },
    selectedMonth: { type: String, default: '' },
    loading: { type: Boolean, default: false },
    formatMoney: { type: Function, required: true }
  },
  emits: ['select-owner', 'select-turf', 'refresh', 'set-month', 'show-lifetime'],
  computed: {
    selectedTurf() {
      if (!this.selectedOwner?.turfs?.length) return null
      return this.selectedOwner.turfs.find((item) => item.turf_id === this.selectedTurfId) || this.selectedOwner.turfs[0]
    },
    periodLabel() {
      if (!this.selectedMonth) return 'Lifetime earnings view'
      const [year, month] = String(this.selectedMonth).split('-')
      const monthIndex = Number(month) - 1
      const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
      if (monthIndex < 0 || monthIndex > 11 || !year) return `${this.selectedMonth} earnings`
      return `${monthNames[monthIndex]} ${year} earnings`
    }
  }
}
</script>

<template>
  <section class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
    <div class="flex items-start justify-between gap-3 max-[900px]:flex-col">
      <div>
        <h3 class="m-0 text-[22px] font-bold text-slate-900">Owner Earnings</h3>
        <p class="mt-2 text-slate-600">See lifetime income or switch to any month to inspect owner-wise and turf-wise earnings.</p>
      </div>
      <div class="flex flex-wrap items-center justify-end gap-2">
        <button
          type="button"
          class="rounded-[10px] border border-transparent px-3.5 py-2.5 font-semibold shadow-glass transition duration-200 disabled:cursor-not-allowed disabled:opacity-60"
          :class="!selectedMonth ? 'bg-slate-900 text-white shadow-[0_16px_24px_rgba(15,23,42,0.18)]' : 'bg-white/80 text-slate-900 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]'"
          :disabled="loading"
          @click="$emit('show-lifetime')"
        >
          Lifetime
        </button>
        <input
          :value="selectedMonth"
          type="month"
          class="rounded-[10px] border border-slate-200 bg-white/85 px-3 py-2.5 text-sm font-medium text-slate-900 shadow-glass outline-none transition duration-200 focus:border-slate-300 focus:bg-white"
          :disabled="loading"
          @input="$emit('set-month', $event.target.value)"
        />
        <button
          type="button"
          class="rounded-[10px] border border-transparent bg-white/80 px-3.5 py-2.5 font-semibold text-slate-900 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)] disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="loading"
          @click="$emit('refresh')"
        >
          {{ loading ? 'Refreshing...' : 'Refresh Earnings' }}
        </button>
      </div>
    </div>

    <p class="mt-3 text-sm font-semibold text-[#4d5d82]">{{ periodLabel }}</p>

    <div class="mt-4 grid grid-cols-[320px_minmax(0,1fr)] gap-3 max-[1100px]:grid-cols-1">
      <article class="rounded-[14px] border border-transparent bg-white/82 p-3.5 shadow-glass">
        <div class="mb-3 flex items-center justify-between gap-2">
          <h4 class="m-0 text-[18px] font-bold text-slate-900">Owners</h4>
          <span class="rounded-full bg-slate-900/5 px-3 py-1 text-xs font-semibold text-slate-600">{{ owners.length }} total</span>
        </div>

        <p v-if="loading && !owners.length" class="text-sm text-slate-600">Loading owner earnings...</p>
        <div v-else class="flex max-h-[680px] flex-col gap-2 overflow-auto pr-1">
          <button
            v-for="owner in owners"
            :key="owner.owner_id"
            type="button"
            class="rounded-[14px] border border-transparent p-3 text-left shadow-glass transition duration-200"
            :class="selectedOwner?.owner_id === owner.owner_id ? 'scale-[1.01] bg-slate-900 text-white shadow-[0_16px_24px_rgba(15,23,42,0.18)]' : 'bg-white/80 text-slate-900 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]'"
            @click="$emit('select-owner', owner.owner_id)"
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-semibold">{{ owner.owner_name }}</div>
                <div class="mt-1 text-xs" :class="selectedOwner?.owner_id === owner.owner_id ? 'text-white/75' : 'text-slate-500'">{{ owner.email }}</div>
              </div>
              <span
                class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                :class="selectedOwner?.owner_id === owner.owner_id ? 'bg-white/10 text-white' : 'bg-slate-900/5 text-slate-600'"
              >
                {{ owner.total_turfs }} turfs
              </span>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2 text-[12px]">
                <div class="rounded-[10px] px-2.5 py-2" :class="selectedOwner?.owner_id === owner.owner_id ? 'bg-white/10' : 'bg-[#eff6ff]'">
                <div :class="selectedOwner?.owner_id === owner.owner_id ? 'text-white/70' : 'text-slate-500'">{{ selectedMonth ? 'Month Total' : 'Wallet' }}</div>
                <div class="mt-1 font-bold">Tk {{ formatMoney(owner.wallet_balance) }}</div>
              </div>
              <div class="rounded-[10px] px-2.5 py-2" :class="selectedOwner?.owner_id === owner.owner_id ? 'bg-white/10' : 'bg-[#f9f3df]'">
                <div :class="selectedOwner?.owner_id === owner.owner_id ? 'text-white/70' : 'text-slate-500'">Cancellation</div>
                <div class="mt-1 font-bold">Tk {{ formatMoney(owner.cancellation_earnings_total) }}</div>
              </div>
            </div>
          </button>
        </div>
      </article>

      <article class="rounded-[14px] border border-transparent bg-white/82 p-3.5 shadow-glass">
        <div v-if="!selectedOwner" class="rounded-[14px] bg-white/70 p-5 text-sm text-slate-600 shadow-glass">
          Select an owner to open the earnings card and turf list.
        </div>

        <template v-else>
          <div class="rounded-[16px] bg-white/88 p-4 shadow-glass">
            <div class="flex items-start justify-between gap-3 max-[700px]:flex-col">
              <div>
                <h4 class="m-0 text-[22px] font-bold text-slate-900">{{ selectedOwner.owner_name }}</h4>
                <p class="mt-1 text-sm text-slate-500">{{ selectedOwner.email }}</p>
              </div>
              <span class="rounded-full bg-slate-900/5 px-3 py-1 text-xs font-semibold text-slate-600">{{ selectedOwner.turfs.length }} turf entries</span>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-3 max-[900px]:grid-cols-1">
              <div class="rounded-[14px] bg-[#eff6ff] p-3.5 shadow-glass">
                <p class="m-0 text-sm text-slate-500">{{ selectedMonth ? 'Month Total' : 'Current Wallet' }}</p>
                <p class="mt-2 text-[24px] font-bold text-slate-900">Tk {{ formatMoney(selectedOwner.turfs.reduce((sum, item) => sum + Number(item.wallet_balance || 0), 0)) }}</p>
              </div>
              <div class="rounded-[14px] bg-[#f7f3e6] p-3.5 shadow-glass">
                <p class="m-0 text-sm text-slate-500">Cancellation Earnings</p>
                <p class="mt-2 text-[24px] font-bold text-slate-900">Tk {{ formatMoney(selectedOwner.turfs.reduce((sum, item) => sum + Number(item.cancellation_earnings || 0), 0)) }}</p>
              </div>
              <div class="rounded-[14px] bg-[#eef8f2] p-3.5 shadow-glass">
                <p class="m-0 text-sm text-slate-500">Booking Income</p>
                <p class="mt-2 text-[24px] font-bold text-slate-900">Tk {{ formatMoney(selectedOwner.turfs.reduce((sum, item) => sum + Number(item.booking_income || 0), 0)) }}</p>
              </div>
            </div>
          </div>

          <div class="mt-4 grid grid-cols-[minmax(0,340px)_minmax(0,1fr)] gap-3 max-[1000px]:grid-cols-1">
            <div class="flex flex-col gap-2">
              <button
                v-for="turf in selectedOwner.turfs"
                :key="turf.turf_id"
                type="button"
                class="rounded-[14px] border border-transparent p-3 text-left shadow-glass transition duration-200"
                :class="selectedTurf?.turf_id === turf.turf_id ? 'scale-[1.01] bg-slate-900 text-white shadow-[0_16px_24px_rgba(15,23,42,0.18)]' : 'bg-white/82 text-slate-900 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]'"
                @click="$emit('select-turf', turf.turf_id)"
              >
                <div class="font-semibold">{{ turf.turf_name }}</div>
                <div class="mt-1 text-xs" :class="selectedTurf?.turf_id === turf.turf_id ? 'text-white/75' : 'text-slate-500'">{{ turf.area || 'Area N/A' }}<span v-if="turf.city">, {{ turf.city }}</span></div>
                <div class="mt-3 text-sm font-semibold">Tk {{ formatMoney(turf.wallet_balance) }}</div>
              </button>
            </div>

            <div v-if="selectedTurf" class="rounded-[16px] bg-white/88 p-4 shadow-glass">
              <div class="flex items-start justify-between gap-3 max-[700px]:flex-col">
                <div>
                  <h4 class="m-0 text-[24px] font-bold text-slate-900">{{ selectedTurf.turf_name }}</h4>
                  <p class="mt-1 text-sm text-slate-500">{{ selectedTurf.area || 'Area N/A' }}<span v-if="selectedTurf.city">, {{ selectedTurf.city }}</span></p>
                </div>
                <span class="rounded-full bg-slate-900/5 px-3 py-1 text-xs font-semibold text-slate-600">{{ selectedTurf.status }}</span>
              </div>

              <div class="mt-4 grid grid-cols-3 gap-3 max-[900px]:grid-cols-1">
                <div class="rounded-[14px] bg-[#eff6ff] p-3.5 shadow-glass">
                  <p class="m-0 text-sm text-slate-500">{{ selectedMonth ? 'Month Total' : 'Total Earned' }}</p>
                  <p class="mt-2 text-[24px] font-bold text-slate-900">Tk {{ formatMoney(selectedTurf.wallet_balance) }}</p>
                </div>
                <div class="rounded-[14px] bg-[#eef8f2] p-3.5 shadow-glass">
                  <p class="m-0 text-sm text-slate-500">Booking Income</p>
                  <p class="mt-2 text-[24px] font-bold text-slate-900">Tk {{ formatMoney(selectedTurf.booking_income) }}</p>
                </div>
                <div class="rounded-[14px] bg-[#f7f3e6] p-3.5 shadow-glass">
                  <p class="m-0 text-sm text-slate-500">Cancellation Earnings</p>
                  <p class="mt-2 text-[24px] font-bold text-slate-900">Tk {{ formatMoney(selectedTurf.cancellation_earnings) }}</p>
                </div>
              </div>

              <div class="mt-4 grid grid-cols-1 gap-3">
                <div class="rounded-[14px] bg-white/82 p-3.5 shadow-glass">
                  <p class="m-0 text-sm text-slate-500">Paid Cancellation Count</p>
                  <p class="mt-2 text-[20px] font-bold text-slate-900">{{ selectedTurf.paid_cancellation_count }}</p>
                </div>
              </div>
            </div>
          </div>
        </template>
      </article>
    </div>
  </section>
</template>
