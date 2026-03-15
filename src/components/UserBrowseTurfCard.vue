<script>
import GlassButton from './GlassButton.vue'

export default {
  name: 'UserBrowseTurfCard',
  components: {
    GlassButton
  },
  props: {
    turf: {
      type: Object,
      required: true
    },
    todayDate: {
      type: String,
      required: true
    },
    slotLoading: {
      type: Boolean,
      default: false
    },
    bookingLoading: {
      type: Boolean,
      default: false
    },
    promoLoading: {
      type: Boolean,
      default: false
    },
    selectedSlot: {
      type: Object,
      default: null
    },
    priceBreakdown: {
      type: Object,
      default: null
    },
    formatSlotLabel: {
      type: Function,
      required: true
    },
    formatDuration: {
      type: Function,
      required: true
    },
    formatMoney: {
      type: Function,
      required: true
    },
    statusTextClass: {
      type: Function,
      required: true
    }
  },
  emits: ['update-field', 'date-change', 'slot-change', 'book', 'apply-promo'],
  methods: {
    emitUpdate(field, value) {
      this.$emit('update-field', this.turf, field, value)
    },
    onSlotSelect(event) {
      this.emitUpdate('selected_slot_id', Number(event.target.value || 0))
      this.$emit('slot-change', this.turf)
    }
  }
}
</script>

<template>
  <article class="overflow-hidden rounded-2xl border border-white/95 bg-white/80 backdrop-blur-[14px] shadow-glass">
    <div class="relative h-[170px] w-full bg-slate-200">
      <img v-if="turf.image_full_url" :src="turf.image_full_url" :alt="turf.turf_name" class="h-full w-full object-cover" />
      <div v-else class="grid h-full w-full place-items-center font-semibold text-slate-600">No Image</div>

      <span v-if="turf.is_new" class="absolute left-2 top-2 rounded-full bg-green-500/90 px-2 py-1 text-xs font-bold text-green-950">New Turf</span>
    </div>

    <div class="p-3.5">
      <h3 class="mb-2 text-[22px] font-semibold">{{ turf.turf_name }}</h3>
      <p class="my-1 text-slate-700"><b>Sport:</b> {{ turf.sport_type }}</p>
      <p class="my-1 text-slate-700"><b>Location:</b> {{ turf.city || turf.area || turf.location || turf.address }}</p>
      <p class="my-1 text-slate-700"><b>Price:</b> Tk {{ Number(turf.price_per_hour || 0).toFixed(2) }}/hr</p>

      <div class="mt-3 flex flex-col gap-2.5">
        <div class="text-sm font-bold text-slate-900">Book Slot</div>

        <div class="grid grid-cols-2 gap-2 max-[760px]:grid-cols-1">
          <label class="flex flex-col gap-1 text-xs text-slate-700">
            Select Date
            <input
              type="date"
              :min="todayDate"
              :value="turf.booking_date"
              class="rounded-lg border border-white/95 bg-white/80 px-2 py-2 text-slate-900 backdrop-blur-[14px] shadow-glass"
              @input="emitUpdate('booking_date', $event.target.value)"
              @change="$emit('date-change', turf)"
            />
          </label>

          <label class="flex flex-col gap-1 text-xs text-slate-700">
            Select Available Slot
            <select
              :value="turf.selected_slot_id"
              class="rounded-lg border border-white/95 bg-white/80 px-2 py-2 text-slate-900 backdrop-blur-[14px] shadow-glass"
              :disabled="slotLoading || !turf.available_slots.length"
              @change="onSlotSelect($event)"
            >
              <option :value="0">{{ slotLoading ? 'Loading...' : 'Select slot' }}</option>
              <option v-for="slot in turf.available_slots" :key="slot.slot_id" :value="slot.slot_id">
                {{ formatSlotLabel(slot) }}
              </option>
            </select>
          </label>
        </div>

        <p class="m-0 text-xs font-semibold text-blue-800">Available Slots: {{ turf.available_slots.length }}</p>
        <div v-if="selectedSlot" class="rounded-xl border border-slate-200/80 bg-white/75 p-2.5">
          <h4 class="mb-2 text-sm font-semibold text-slate-900">Confirm Booking</h4>

          <div class="grid grid-cols-3 gap-1.5 max-[1100px]:grid-cols-1">
            <p class="m-0 text-xs text-slate-700"><b>Date:</b> {{ turf.booking_date }}</p>
            <p class="m-0 text-xs text-slate-700"><b>Time:</b> {{ formatSlotLabel(selectedSlot) }}</p>
            <p class="m-0 text-xs text-slate-700"><b>Price/hr:</b> Tk {{ Number(turf.price_per_hour || 0).toFixed(2) }}</p>
          </div>

        <div v-if="priceBreakdown" class="mt-2 flex flex-col gap-1 border-t border-slate-200/90 pt-2">
            <div class="flex items-center justify-between text-[13px] text-slate-700">
              <span>Duration</span>
              <b>{{ formatDuration(priceBreakdown.durationHours) }}</b>
            </div>
            <div class="flex items-center justify-between text-[13px] text-slate-700">
              <span>Subtotal</span>
              <b>Tk {{ formatMoney(priceBreakdown.subtotal) }}</b>
            </div>
            <div v-if="priceBreakdown.promoCode" class="flex items-center justify-between text-[13px] text-emerald-700">
              <span>Promo ({{ priceBreakdown.promoCode }})</span>
              <b>- Tk {{ formatMoney(priceBreakdown.discountAmount) }}</b>
            </div>
            <div class="flex items-center justify-between text-[13px] text-slate-700">
              <span>Service Fee</span>
              <b>Tk {{ formatMoney(priceBreakdown.serviceFee) }}</b>
            </div>
            <div class="mt-0.5 flex items-center justify-between text-[13px] font-bold text-slate-900">
              <span>Total</span>
              <b>Tk {{ formatMoney(priceBreakdown.total) }}</b>
            </div>
          </div>

          <div class="mt-2 grid grid-cols-[minmax(0,1fr)_auto] gap-2 max-[760px]:grid-cols-1">
            <label class="flex flex-col gap-1 text-xs text-slate-700">
              Promo Code
              <input
                :value="turf.promo_code"
                type="text"
                class="rounded-lg border border-white/95 bg-white/80 px-2 py-2 text-slate-900 backdrop-blur-[14px] shadow-glass"
                placeholder="Enter promo code"
                @input="emitUpdate('promo_code', $event.target.value.toUpperCase())"
              />
            </label>

            <div class="flex items-end">
              <GlassButton
                class="w-full rounded-[10px] px-3 py-[9px] font-bold"
                :disabled="bookingLoading || promoLoading"
                @click="$emit('apply-promo', turf)"
              >
                {{ promoLoading ? 'Applying...' : 'Apply Promo' }}
              </GlassButton>
            </div>
          </div>

          <label class="mt-2 flex flex-col gap-1 text-xs text-slate-700">
            Payment Method
            <select
              :value="turf.payment_method"
              class="rounded-lg border border-white/95 bg-white/80 px-2 py-2 text-slate-900 backdrop-blur-[14px] shadow-glass"
              @change="emitUpdate('payment_method', $event.target.value)"
            >
              <option value="cash">Cash</option>
              <option value="bkash">bKash</option>
              <option value="nagad">Nagad</option>
              <option value="card">Card</option>
            </select>
          </label>

          <GlassButton
            class="mt-2 w-full rounded-[10px] px-3 py-[9px] font-bold"
            :disabled="bookingLoading"
            @click="$emit('book', turf)"
          >
            {{ bookingLoading ? 'Booking...' : 'Confirm Booking' }}
          </GlassButton>
        </div>
      </div>

      <p v-if="turf.message" class="mt-2.5 text-sm font-semibold" :class="statusTextClass(turf.messageType)">{{ turf.message }}</p>
    </div>
  </article>
</template>
