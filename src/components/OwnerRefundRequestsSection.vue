<script>
export default {
  name: 'RefundRequestsSection',
  props: {
    pendingRefunds: { type: Array, default: () => [] },
    notes: { type: Object, required: true },
    isActionLoading: { type: Function, required: true },
    formatMoney: { type: Function, required: true },
    title: { type: String, default: 'Manage Refund Requests' },
    subtitle: { type: String, default: 'Review request then approve or reject.' },
    emptyText: { type: String, default: 'No pending refund requests.' },
    notePlaceholder: { type: String, default: 'Admin note' },
    primaryActionLabel: { type: String, default: 'Approve' },
    secondaryActionLabel: { type: String, default: 'Reject' }
  },
  emits: ['update-note', 'review']
}
</script>

<template>
  <section class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
    <div>
      <h3 class="m-0 text-[18px] font-bold text-slate-900">{{ title }}</h3>
      <p class="mt-2 text-slate-600">{{ subtitle }}</p>
    </div>

    <p v-if="!pendingRefunds.length" class="mt-3 text-slate-500">{{ emptyText }}</p>

    <div v-else class="mt-3 grid grid-cols-2 gap-3 max-[1200px]:grid-cols-1">
      <article
        v-for="item in pendingRefunds"
        :key="item.refund_id"
        class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]"
      >
        <h4 class="m-0 text-[18px] font-bold text-slate-900">Refund #{{ item.refund_id }} - Booking #{{ item.booking_id }}</h4>
        <div class="mt-3 space-y-1.5 text-sm text-slate-700">
          <p class="m-0"><b class="text-slate-900">User:</b> {{ item.user_name }} ({{ item.user_email }})</p>
          <p class="m-0"><b class="text-slate-900">Booking Status:</b> {{ item.booking_status }}</p>
          <p class="m-0"><b class="text-slate-900">Requested:</b> Tk {{ formatMoney(item.requested_amount) }}</p>
        </div>

        <textarea
          :value="notes[item.refund_id] || ''"
          rows="2"
          :placeholder="notePlaceholder"
          class="mt-3 min-h-[88px] w-full rounded-[12px] border border-white/95 bg-white/90 px-3 py-2.5 text-sm text-slate-900 shadow-glass outline-none transition focus:border-blue-300"
          @input="$emit('update-note', { id: item.refund_id, value: $event.target.value })"
        />

        <div class="mt-3 flex flex-wrap gap-2">
          <button
            type="button"
            class="rounded-[10px] border border-transparent bg-[#3361d8] px-3.5 py-2 font-semibold text-white shadow-[0_10px_20px_rgba(51,97,216,0.3)] transition duration-200 hover:-translate-y-0.5 hover:bg-[#2f57c2] hover:shadow-[0_14px_20px_rgba(51,97,216,0.28)] disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="isActionLoading('refund', item.refund_id)"
            @click="$emit('review', { item, action: 'approved' })"
          >
            {{ primaryActionLabel }}
          </button>
          <button
            type="button"
            class="rounded-[10px] border border-transparent bg-white/80 px-3.5 py-2 font-semibold text-[#991b1b] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)] disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="isActionLoading('refund', item.refund_id)"
            @click="$emit('review', { item, action: 'rejected' })"
          >
            {{ secondaryActionLabel }}
          </button>
        </div>
      </article>
    </div>
  </section>
</template>
