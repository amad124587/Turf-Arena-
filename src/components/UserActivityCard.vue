<script>
import pendingIcon from '../assets/pending.svg'
import confirmedIcon from '../assets/confirmed.svg'
import disabledIcon from '../assets/disabled.svg'
import cancelledIcon from '../assets/cancelled.svg'

export default {
  name: 'UserActivityCard',
  props: {
    activities: {
      type: Array,
      default: () => []
    },
    formatDateTime: {
      type: Function,
      required: true
    }
  },
  emits: ['refresh'],
  data() {
    return {
      pendingIcon,
      confirmedIcon,
      disabledIcon,
      cancelledIcon
    }
  },
  methods: {
    getActivityIcon(activity) {
      const text = `${activity?.message || ''} ${activity?.detail_message || ''}`.toLowerCase()
      if (text.includes('approved') || text.includes('confirmed') || text.includes('success') || text.includes('refund approved')) {
        return this.confirmedIcon
      }
      if (text.includes('cancel') || text.includes('rejected') || text.includes('failed') || text.includes('declined')) {
        return this.cancelledIcon
      }
      if (text.includes('disabled') || text.includes('unavailable') || text.includes('expired') || text.includes('blocked')) {
        return this.disabledIcon
      }
      return this.pendingIcon
    }
  }
}
</script>

<template>
  <article class="flex min-h-[420px] flex-col overflow-visible rounded-2xl border border-slate-100 bg-white p-[32px_14px_18px] text-slate-900 shadow-[0_8px_20px_rgba(0,0,0,0.08)] transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_12px_22px_rgba(0,0,0,0.1)]">
    <h3 class="mb-4 px-1 text-[24px] font-semibold tracking-[-0.03em] text-[#141b33]">Recent Activity</h3>

    <div v-if="activities.length" class="flex flex-1 flex-col gap-1.5 overflow-visible">
      <div v-for="(a, i) in activities" :key="i" class="relative overflow-visible rounded-2xl border border-slate-100 bg-white p-[14px_14px_12px] shadow-[0_8px_20px_rgba(0,0,0,0.06)]">
        <div class="flex items-start justify-between gap-2.5">
          <div class="flex min-w-0 flex-1 items-start gap-3">
            <span class="mt-0.5 flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-50/90 shadow-[0_8px_16px_rgba(15,23,42,0.08)]">
              <img :src="getActivityIcon(a)" alt="" class="h-7 w-7 object-contain" aria-hidden="true" />
            </span>
            <div class="min-w-0 flex-1">
              <div class="text-[17px] font-semibold text-[#1c2337]">{{ a.message }}</div>
              <div class="mt-1 text-[13px] text-[#4c566d]">{{ formatDateTime(a.time) }}</div>
            </div>
          </div>
          <div v-if="a.detail_message" class="group relative ml-1 mt-0.5 shrink-0">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-slate-100 bg-white text-[11px] leading-none text-slate-900 shadow-[0_6px_14px_rgba(0,0,0,0.06)]" aria-label="Admin note">&#9993;</span>
            <div class="pointer-events-none invisible absolute right-0 top-[calc(100%+6px)] z-[120] w-[min(320px,75vw)] translate-y-1 rounded-[10px] border border-slate-100 bg-white px-2.5 py-2 text-xs leading-[1.45] text-[#111827] opacity-0 shadow-[0_8px_20px_rgba(0,0,0,0.08)] transition duration-200 whitespace-normal break-words group-hover:pointer-events-auto group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
              {{ a.detail_message }}
            </div>
          </div>
        </div>
      </div>
    </div>
    <div v-else class="flex-1 px-1 text-[17px] text-[#30384d]">No activity yet</div>

    <div class="mt-3 flex flex-wrap gap-1.5">
      <button type="button" class="appearance-none rounded-full border border-transparent bg-white/85 px-4 py-3 font-semibold text-slate-900 shadow-glass outline-none backdrop-blur-[14px] transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]" @click="$emit('refresh')">Refresh Activity</button>
    </div>
  </article>
</template>
