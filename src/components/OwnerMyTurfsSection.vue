<template>
  <section class="space-y-3.5">
    <div class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
      <div class="flex items-start justify-between gap-3 max-[900px]:flex-col">
        <div>
          <h3 class="m-0 text-[22px] font-bold text-slate-900">My Turfs</h3>
          <p class="mt-2 text-slate-600">See all turfs created by this owner and quickly turn them active or inactive.</p>
        </div>
        <button
          type="button"
          class="rounded-[10px] border border-transparent bg-white/80 px-3.5 py-2 font-semibold text-slate-900 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)] disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="loading"
          @click="loadTurfs"
        >
          {{ loading ? 'Refreshing...' : 'Refresh List' }}
        </button>
      </div>

      <p v-if="message" class="mt-3 rounded-[10px] px-3 py-2 text-sm font-semibold" :class="messageType === 'error' ? 'bg-rose-100 text-rose-700' : messageType === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'">
        {{ message }}
      </p>
    </div>

    <div class="grid grid-cols-4 gap-3 max-[1100px]:grid-cols-2 max-[640px]:grid-cols-1">
      <article
        v-for="card in summaryCards"
        :key="card.label"
        class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]"
      >
        <p class="m-0 text-sm text-slate-600">{{ card.label }}</p>
        <strong class="mt-2 block text-[24px] leading-none text-slate-900">{{ card.value }}</strong>
      </article>
    </div>

    <div v-if="!turfs.length && !loading" class="rounded-[14px] border border-transparent bg-white/80 p-8 text-center text-slate-600 backdrop-blur-[14px] shadow-glass">
      No turfs found for this owner yet.
    </div>

    <div v-else class="grid grid-cols-2 gap-3 max-[1100px]:grid-cols-1">
      <article
        v-for="turf in turfs"
        :key="turf.turf_id"
        class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]"
      >
        <div class="flex items-start justify-between gap-3 max-[640px]:flex-col">
          <div>
            <h4 class="m-0 text-xl font-bold text-slate-900">{{ turf.turf_name }}</h4>
            <p class="mt-1 text-sm text-slate-600">{{ turf.sport_type }} · {{ turf.city || turf.area || turf.address }}</p>
          </div>
          <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusTone(turf.status)">
            {{ turf.status }}
          </span>
        </div>

        <div class="mt-3 grid grid-cols-2 gap-2.5 text-sm text-slate-700 max-[640px]:grid-cols-1">
          <p class="m-0"><b class="text-slate-900">Price:</b> Tk {{ formatMoney(turf.price_per_hour) }}/hr</p>
          <p class="m-0"><b class="text-slate-900">Created:</b> {{ formatDate(turf.created_at) }}</p>
          <p class="m-0"><b class="text-slate-900">Refund:</b> {{ turf.refund_percent }}%</p>
          <p class="m-0"><b class="text-slate-900">Cancel Window:</b> {{ turf.cancel_before_hours }} hrs</p>
        </div>

        <p v-if="turf.status === 'pending'" class="mt-3 text-sm font-medium text-amber-700">
          This turf is waiting for admin approval, so it cannot be switched yet.
        </p>
        <p v-else-if="turf.status === 'rejected'" class="mt-3 text-sm font-medium text-rose-700">
          This turf was rejected by admin. Activate is blocked until it is approved again.
        </p>

        <div class="mt-3 flex justify-end">
          <button
            type="button"
            class="rounded-[10px] border border-transparent px-4 py-2.5 font-semibold shadow-glass transition duration-200 hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60"
            :class="turf.status === 'active' ? 'bg-rose-100 text-rose-700 hover:bg-rose-200 hover:shadow-[0_14px_20px_rgba(190,24,93,0.14)]' : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 hover:shadow-[0_14px_20px_rgba(22,163,74,0.14)]'"
            :disabled="loading || !canToggle(turf)"
            @click="toggleTurf(turf)"
          >
            {{ turf.status === 'active' ? 'Turn Off Turf' : 'Turn On Turf' }}
          </button>
        </div>
      </article>
    </div>
  </section>
</template>

<script>
import {
  buildOwnerTurfSummary,
  fetchOwnerTurfs,
  formatOwnerTurfDate,
  formatOwnerTurfMoney,
  getOwnerTurfStatusTone,
  toggleOwnerTurfStatus
} from '../support/ownerTurfsSupport'

export default {
  name: 'OwnerMyTurfsSection',
  props: {
    ownerId: { type: Number, required: true }
  },
  data() {
    return {
      loading: false,
      message: '',
      messageType: 'info',
      turfs: [],
      summary: {
        total: 0,
        active: 0,
        inactive: 0,
        pending: 0
      }
    }
  },
  computed: {
    summaryCards() {
      return buildOwnerTurfSummary(this.summary)
    }
  },
  async mounted() {
    await this.loadTurfs()
  },
  methods: {
    formatMoney: formatOwnerTurfMoney,
    formatDate: formatOwnerTurfDate,
    statusTone: getOwnerTurfStatusTone,
    canToggle(turf) {
      const status = String(turf?.status || '').toLowerCase()
      return status === 'active' || status === 'inactive'
    },
    async loadTurfs() {
      if (!this.ownerId) return
      this.loading = true
      this.message = ''
      try {
        const data = await fetchOwnerTurfs(this.ownerId)
        if (!data?.success) {
          this.messageType = 'error'
          this.message = data?.message || 'Failed to load owner turfs.'
          return
        }
        this.turfs = Array.isArray(data.turfs) ? data.turfs : []
        this.summary = { ...this.summary, ...(data.summary || {}) }
      } catch (error) {
        this.messageType = 'error'
        this.message = error.response?.data?.message || 'Server connection failed while loading owner turfs.'
      } finally {
        this.loading = false
      }
    },
    async toggleTurf(turf) {
      if (!this.canToggle(turf)) return
      this.loading = true
      this.message = ''
      const nextStatus = String(turf.status || '').toLowerCase() === 'active' ? 'inactive' : 'active'

      try {
        const data = await toggleOwnerTurfStatus(this.ownerId, turf.turf_id, nextStatus)
        this.messageType = data?.success ? 'success' : 'error'
        this.message = data?.message || 'Turf status updated.'
        if (data?.success) {
          await this.loadTurfs()
        }
      } catch (error) {
        this.messageType = 'error'
        this.message = error.response?.data?.message || 'Server connection failed while updating turf status.'
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
