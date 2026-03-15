<script>
import {
  createOwnerSlotControlState,
  fetchOwnerSlotControl,
  formatOwnerSlotDate,
  formatOwnerSlotMoney,
  formatOwnerSlotTime,
  getOwnerSlotStatusTone,
  updateOwnerSlotAvailability
} from '../support/ownerSlotControlSupport'

export default {
  name: 'OwnerSlotControlSection',
  props: {
    ownerId: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      loading: false,
      actionLoading: false,
      message: '',
      messageType: 'info',
      filters: createOwnerSlotControlState(),
      turfs: [],
      slots: [],
      selectedSlotIds: []
    }
  },
  computed: {
    summaryCards() {
      const total = this.slots.length
      const enabled = this.slots.filter((slot) => slot.is_enabled && !slot.is_booked).length
      const disabled = this.slots.filter((slot) => !slot.is_enabled && !slot.is_booked).length
      return [
        { label: 'Total Slots', value: total },
        { label: 'Enabled', value: enabled },
        { label: 'Disabled', value: disabled }
      ]
    }
  },
  async mounted() {
    await this.loadData()
  },
  methods: {
    formatDate: formatOwnerSlotDate,
    formatTime: formatOwnerSlotTime,
    formatMoney: formatOwnerSlotMoney,
    statusTone: getOwnerSlotStatusTone,
    async loadData() {
      if (!this.ownerId) return
      this.loading = true
      this.message = ''
      try {
        const data = await fetchOwnerSlotControl(this.ownerId, this.filters.turfId, this.filters.slotDate)
        if (!data?.success) {
          this.messageType = 'error'
          this.message = data?.message || 'Failed to load owner slot data.'
          return
        }
        this.turfs = Array.isArray(data.turfs) ? data.turfs : []
        if (!this.filters.turfId && this.turfs.length) {
          this.filters.turfId = String(this.turfs[0].turf_id)
          const retry = await fetchOwnerSlotControl(this.ownerId, this.filters.turfId, this.filters.slotDate)
          this.slots = Array.isArray(retry?.slots) ? retry.slots : []
        } else {
          this.slots = Array.isArray(data.slots) ? data.slots : []
        }
        this.selectedSlotIds = []
      } catch (error) {
        this.messageType = 'error'
        this.message = error.response?.data?.message || 'Server connection failed while loading slots.'
      } finally {
        this.loading = false
      }
    },
    toggleSlot(slotId, checked) {
      if (checked) {
        this.selectedSlotIds = Array.from(new Set([...this.selectedSlotIds, slotId]))
        return
      }
      this.selectedSlotIds = this.selectedSlotIds.filter((id) => id !== slotId)
    },
    selectAllAvailable() {
      this.selectedSlotIds = this.slots.filter((slot) => !slot.is_booked).map((slot) => slot.slot_id)
    },
    clearSelection() {
      this.selectedSlotIds = []
    },
    async updateAvailability(nextState) {
      if (!this.ownerId || !this.filters.turfId || !this.selectedSlotIds.length) return
      this.actionLoading = true
      this.message = ''
      try {
        const data = await updateOwnerSlotAvailability(
          this.ownerId,
          Number(this.filters.turfId),
          this.filters.slotDate,
          this.selectedSlotIds,
          nextState
        )
        this.messageType = data?.success ? 'success' : 'error'
        this.message = data?.message || 'Slot availability updated.'
        if (typeof data?.skipped_booked === 'number' && data.skipped_booked > 0) {
          this.message += ` ${data.skipped_booked} booked slot(s) were skipped.`
        }
        await this.loadData()
      } catch (error) {
        this.messageType = 'error'
        this.message = error.response?.data?.message || 'Server connection failed while updating slot availability.'
      } finally {
        this.actionLoading = false
      }
    }
  }
}
</script>

<template>
  <section class="space-y-3.5">
    <div class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
      <div class="flex items-start justify-between gap-3 max-[900px]:flex-col">
        <div>
          <h3 class="m-0 text-[22px] font-bold text-slate-900">Control Slot Availability</h3>
          <p class="mt-2 text-slate-600">Choose a turf and day, then turn selected slots on or off without breaking booked reservations.</p>
        </div>
        <div class="rounded-[12px] border border-transparent bg-white/85 px-4 py-3 shadow-glass">
          <p class="m-0 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Selected Day</p>
          <p class="mt-1 text-base font-bold text-slate-900">{{ formatDate(filters.slotDate) }}</p>
        </div>
      </div>

      <div class="mt-4 grid grid-cols-[minmax(0,1.2fr)_200px_auto] gap-3 max-[1100px]:grid-cols-1">
        <label class="flex flex-col gap-1.5">
          <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Select Turf</span>
          <select v-model="filters.turfId" class="rounded-[10px] border border-transparent bg-white/85 px-3 py-2.5 text-sm text-slate-900 shadow-glass outline-none">
            <option value="">Choose a turf</option>
            <option v-for="turf in turfs" :key="turf.turf_id" :value="String(turf.turf_id)">
              {{ turf.turf_name }}
            </option>
          </select>
        </label>

        <label class="flex flex-col gap-1.5">
          <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Select Day</span>
          <input v-model="filters.slotDate" type="date" class="rounded-[10px] border border-transparent bg-white/85 px-3 py-2.5 text-sm text-slate-900 shadow-glass outline-none" />
        </label>

        <div class="flex items-end">
          <button type="button" class="w-full rounded-[10px] border border-transparent bg-[#3361d8] px-3.5 py-2.5 font-semibold text-white shadow-[0_10px_20px_rgba(51,97,216,0.3)] transition duration-200 hover:-translate-y-0.5 hover:bg-[#2f57c2] hover:shadow-[0_14px_20px_rgba(51,97,216,0.28)] disabled:cursor-not-allowed disabled:opacity-60" :disabled="loading || !filters.turfId" @click="loadData">
            {{ loading ? 'Loading...' : 'Load Slots' }}
          </button>
        </div>
      </div>

      <p v-if="message" class="mt-3 rounded-[10px] px-3 py-2 text-sm font-semibold" :class="messageType === 'error' ? 'bg-rose-100 text-rose-700' : messageType === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'">
        {{ message }}
      </p>
    </div>

    <div class="grid grid-cols-3 gap-3 max-[1200px]:grid-cols-2 max-[640px]:grid-cols-1">
      <article v-for="card in summaryCards" :key="card.label" class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
        <p class="m-0 text-sm text-slate-600">{{ card.label }}</p>
        <strong class="mt-2 block text-[24px] leading-none text-slate-900">{{ card.value }}</strong>
      </article>
    </div>

    <section class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
      <div class="flex items-start justify-between gap-3 max-[900px]:flex-col">
        <div>
          <h4 class="m-0 text-[22px] font-bold text-slate-900">Slot Grid</h4>
          <p class="mt-1 text-slate-600">Select free slots and switch them on or off for the chosen day.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button type="button" class="rounded-[10px] border border-transparent bg-white/80 px-3.5 py-2 text-sm font-semibold text-slate-900 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]" @click="selectAllAvailable">Select Available</button>
          <button type="button" class="rounded-[10px] border border-transparent bg-white/80 px-3.5 py-2 text-sm font-semibold text-slate-900 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]" @click="clearSelection">Clear</button>
          <button type="button" class="rounded-[10px] border border-transparent bg-white/80 px-3.5 py-2 text-sm font-semibold text-rose-700 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)] disabled:cursor-not-allowed disabled:opacity-60" :disabled="actionLoading || !selectedSlotIds.length" @click="updateAvailability(0)">Turn Off Selected</button>
          <button type="button" class="rounded-[10px] border border-transparent bg-[#3361d8] px-3.5 py-2 text-sm font-semibold text-white shadow-[0_10px_20px_rgba(51,97,216,0.3)] transition duration-200 hover:-translate-y-0.5 hover:bg-[#2f57c2] hover:shadow-[0_14px_20px_rgba(51,97,216,0.28)] disabled:cursor-not-allowed disabled:opacity-60" :disabled="actionLoading || !selectedSlotIds.length" @click="updateAvailability(1)">Turn On Selected</button>
        </div>
      </div>

      <div v-if="!slots.length" class="mt-4 rounded-[12px] border border-dashed border-slate-300 bg-white/70 px-4 py-10 text-center text-slate-600">
        Choose a turf and date to load slots.
      </div>

      <div v-else class="mt-4 grid grid-cols-4 gap-3 max-[1200px]:grid-cols-3 max-[900px]:grid-cols-2 max-[640px]:grid-cols-1">
        <label
          v-for="slot in slots"
          :key="slot.slot_id"
          class="flex cursor-pointer items-start gap-3 rounded-[14px] border border-transparent bg-white/80 p-3.5 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]"
          :class="slot.is_booked ? 'opacity-80' : ''"
        >
          <input
            :checked="selectedSlotIds.includes(slot.slot_id)"
            type="checkbox"
            class="mt-1 h-4 w-4 accent-[#3361d8]"
            :disabled="slot.is_booked"
            @change="toggleSlot(slot.slot_id, $event.target.checked)"
          />
          <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-3">
              <strong class="text-slate-900">{{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}</strong>
              <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusTone(slot)">
                {{ slot.is_booked ? 'Booked' : slot.is_enabled ? 'Enabled' : 'Disabled' }}
              </span>
            </div>
            <p class="mt-2 text-sm text-slate-600">Tk {{ formatMoney(slot.base_price) }}</p>
            <p class="mt-1 text-xs text-slate-500">
              {{ slot.is_booked ? `Locked by ${slot.booking_status || 'booking'}` : 'Available for owner control' }}
            </p>
          </div>
        </label>
      </div>
    </section>
  </section>
</template>
