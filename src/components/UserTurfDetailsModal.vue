<script>
export default {
  name: 'UserTurfDetailsModal',
  props: {
    visible: {
      type: Boolean,
      default: false
    },
    turf: {
      type: Object,
      default: null
    },
    formatMoney: {
      type: Function,
      required: true
    }
  },
  emits: ['close'],
  data() {
    return {
      previousBodyOverflow: ''
    }
  },
  watch: {
    visible: {
      immediate: true,
      handler(value) {
        if (typeof document === 'undefined') return
        if (value) {
          this.lockBodyScroll()
          return
        }
        this.unlockBodyScroll()
      }
    }
  },
  beforeUnmount() {
    this.unlockBodyScroll()
  },
  methods: {
    lockBodyScroll() {
      this.previousBodyOverflow = document.body.style.overflow
      document.body.style.overflow = 'hidden'
    },
    unlockBodyScroll() {
      document.body.style.overflow = this.previousBodyOverflow || ''
    },
    formatText(value, fallback = 'Not provided') {
      const text = String(value ?? '').trim()
      return text || fallback
    },
    formatCoordinates(lat, lng) {
      const left = String(lat ?? '').trim()
      const right = String(lng ?? '').trim()
      if (!left && !right) return 'Not provided'
      return `${left || '-'}, ${right || '-'}`
    }
  }
}
</script>

<template>
  <div
    v-if="visible && turf"
    class="fixed inset-0 z-[140] overflow-y-auto bg-slate-950/35 p-4 backdrop-blur-sm"
    @click.self="$emit('close')"
  >
    <div class="mx-auto my-6 w-full max-w-[820px] rounded-[22px] border border-white/95 bg-white/90 p-4 shadow-[0_24px_64px_rgba(15,23,42,0.18)] backdrop-blur-[18px]">
      <div class="flex items-start justify-between gap-3 border-b border-slate-900/10 pb-3">
        <div>
          <p class="m-0 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Turf Details</p>
          <h3 class="mt-1 text-[28px] font-bold text-slate-900">{{ turf.turf_name }}</h3>
          <p class="mt-1 text-sm text-slate-600">{{ formatText(turf.city || turf.area || turf.location || turf.address) }}</p>
        </div>

        <button
          type="button"
          class="rounded-[10px] border border-transparent bg-white/80 px-3.5 py-2 font-semibold text-slate-900 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]"
          @click="$emit('close')"
        >
          Close
        </button>
      </div>

      <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]">
        <div class="overflow-hidden rounded-[18px] border border-white/95 bg-white/80 shadow-glass">
          <div class="h-[250px] bg-slate-200">
            <img v-if="turf.image_full_url" :src="turf.image_full_url" :alt="turf.turf_name" class="h-full w-full object-cover" />
            <div v-else class="grid h-full w-full place-items-center font-semibold text-slate-600">No Image</div>
          </div>

          <div class="grid gap-3 p-4 sm:grid-cols-2">
            <div class="rounded-[14px] bg-white/85 p-3 shadow-glass">
              <p class="m-0 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Sport</p>
              <p class="mt-1 text-base font-bold text-slate-900">{{ formatText(turf.sport_type) }}</p>
            </div>
            <div class="rounded-[14px] bg-white/85 p-3 shadow-glass">
              <p class="m-0 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Price Per Hour</p>
              <p class="mt-1 text-base font-bold text-slate-900">Tk {{ formatMoney(turf.price_per_hour) }}</p>
            </div>
            <div class="rounded-[14px] bg-white/85 p-3 shadow-glass">
              <p class="m-0 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Featured</p>
              <p class="mt-1 text-base font-bold text-slate-900">{{ turf.is_featured ? 'Yes' : 'No' }}</p>
            </div>
            <div class="rounded-[14px] bg-white/85 p-3 shadow-glass">
              <p class="m-0 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Refund Rule</p>
              <p class="mt-1 text-base font-bold text-slate-900">{{ turf.refund_percent || 0 }}%</p>
            </div>
          </div>
        </div>

        <div class="grid gap-3">
          <article class="rounded-[18px] border border-white/95 bg-white/80 p-4 shadow-glass">
            <h4 class="m-0 text-lg font-bold text-slate-900">Location Details</h4>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
              <div>
                <p class="m-0 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Address</p>
                <p class="mt-1 text-sm text-slate-800">{{ formatText(turf.address) }}</p>
              </div>
              <div>
                <p class="m-0 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Area</p>
                <p class="mt-1 text-sm text-slate-800">{{ formatText(turf.area) }}</p>
              </div>
              <div>
                <p class="m-0 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">City</p>
                <p class="mt-1 text-sm text-slate-800">{{ formatText(turf.city) }}</p>
              </div>
              <div>
                <p class="m-0 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Location</p>
                <p class="mt-1 text-sm text-slate-800">{{ formatText(turf.location) }}</p>
              </div>
              <div class="sm:col-span-2">
                <p class="m-0 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Coordinates</p>
                <p class="mt-1 text-sm text-slate-800">{{ formatCoordinates(turf.latitude, turf.longitude) }}</p>
              </div>
            </div>
          </article>

          <article class="rounded-[18px] border border-white/95 bg-white/80 p-4 shadow-glass">
            <h4 class="m-0 text-lg font-bold text-slate-900">Booking & Cancellation</h4>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
              <div>
                <p class="m-0 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Cancel Before</p>
                <p class="mt-1 text-sm text-slate-800">{{ turf.cancel_before_hours || 0 }} hours</p>
              </div>
              <div>
                <p class="m-0 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Refund Percent</p>
                <p class="mt-1 text-sm text-slate-800">{{ turf.refund_percent || 0 }}%</p>
              </div>
            </div>
          </article>

          <article class="rounded-[18px] border border-white/95 bg-white/80 p-4 shadow-glass">
            <h4 class="m-0 text-lg font-bold text-slate-900">Description</h4>
            <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ formatText(turf.description) }}</p>
          </article>
        </div>
      </div>
    </div>
  </div>
</template>
