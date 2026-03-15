<script>
export default {
  name: 'AdminVerifyTurfsSection',
  props: {
    pendingTurfs: { type: Array, default: () => [] },
    notes: { type: Object, required: true },
    isActionLoading: { type: Function, required: true },
    formatMoney: { type: Function, required: true }
  },
  emits: ['update-note', 'review']
}
</script>

<template>
  <section class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
    <div>
      <h3 class="m-0 text-[18px] font-bold text-slate-900">Verify Turf Listings</h3>
      <p class="mt-2 text-slate-600">Approve, reject, or request changes before public browse visibility.</p>
    </div>

    <p v-if="!pendingTurfs.length" class="mt-3 text-slate-500">No pending turf verification requests.</p>

    <div v-else class="mt-3 grid grid-cols-2 gap-3 max-[1200px]:grid-cols-1">
      <article
        v-for="item in pendingTurfs"
        :key="item.turf_id"
        class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]"
      >
        <h4 class="m-0 text-[18px] font-bold text-slate-900">{{ item.turf_name }}</h4>
        <div class="mt-3 space-y-1.5 text-sm text-slate-700">
          <p class="m-0"><b class="text-slate-900">Owner:</b> {{ item.owner_name }} ({{ item.owner_email }})</p>
          <p class="m-0"><b class="text-slate-900">Sport:</b> {{ item.sport_type }}</p>
          <p class="m-0"><b class="text-slate-900">Price:</b> Tk {{ formatMoney(item.price_per_hour) }}/hr</p>
          <p class="m-0"><b class="text-slate-900">Location:</b> {{ item.city || item.area || item.address }}</p>
        </div>

        <textarea
          :value="notes[item.turf_id] || ''"
          rows="2"
          placeholder="Optional note"
          class="mt-3 min-h-[88px] w-full rounded-[12px] border border-white/95 bg-white/90 px-3 py-2.5 text-sm text-slate-900 shadow-glass outline-none transition focus:border-blue-300"
          @input="$emit('update-note', { id: item.turf_id, value: $event.target.value })"
        />

        <div class="mt-3 flex flex-wrap gap-2">
          <button
            type="button"
            class="rounded-[10px] border border-transparent bg-[#3361d8] px-3.5 py-2 font-semibold text-white shadow-[0_10px_20px_rgba(51,97,216,0.3)] transition duration-200 hover:-translate-y-0.5 hover:bg-[#2f57c2] hover:shadow-[0_14px_20px_rgba(51,97,216,0.28)] disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="isActionLoading('turf', item.turf_id)"
            @click="$emit('review', { item, action: 'approved' })"
          >
            Approve
          </button>
          <button
            type="button"
            class="rounded-[10px] border border-transparent bg-white/80 px-3.5 py-2 font-semibold text-[#854d0e] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)] disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="isActionLoading('turf', item.turf_id)"
            @click="$emit('review', { item, action: 'requested_changes' })"
          >
            Request Changes
          </button>
          <button
            type="button"
            class="rounded-[10px] border border-transparent bg-white/80 px-3.5 py-2 font-semibold text-[#991b1b] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)] disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="isActionLoading('turf', item.turf_id)"
            @click="$emit('review', { item, action: 'rejected' })"
          >
            Reject
          </button>
        </div>
      </article>
    </div>
  </section>
</template>
