<template>
  <section class="space-y-3.5">
    <div class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
      <div class="flex items-start justify-between gap-3 max-[900px]:flex-col">
        <div>
          <h3 class="m-0 text-[22px] font-bold text-slate-900">Create Promo Codes & Offers</h3>
          <p class="mt-2 text-slate-600">Create owner-specific offers that only work on this owner’s turfs during booking.</p>
        </div>
        <div class="rounded-[12px] border border-transparent bg-white/85 px-4 py-3 shadow-glass">
          <p class="m-0 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Owner</p>
          <p class="mt-1 text-base font-bold text-slate-900">{{ ownerName }}</p>
        </div>
      </div>

      <p v-if="message" class="mt-3 rounded-[10px] px-3 py-2 text-sm font-semibold" :class="messageType === 'error' ? 'bg-rose-100 text-rose-700' : messageType === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'">
        {{ message }}
      </p>
    </div>

    <div class="grid grid-cols-3 gap-3 max-[900px]:grid-cols-1">
      <article v-for="card in summaryCards" :key="card.label" class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
        <p class="m-0 text-sm text-slate-600">{{ card.label }}</p>
        <strong class="mt-2 block text-[24px] leading-none text-slate-900">{{ card.value }}</strong>
      </article>
    </div>

    <section class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
      <h4 class="m-0 text-[22px] font-bold text-slate-900">New Promo Code</h4>
      <div class="mt-4 grid grid-cols-3 gap-3 max-[1100px]:grid-cols-2 max-[640px]:grid-cols-1">
        <label class="flex flex-col gap-1.5">
          <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Promo Code</span>
          <input v-model.trim="form.code" type="text" placeholder="AXR10" class="rounded-[10px] border border-transparent bg-white/85 px-3 py-2.5 text-sm text-slate-900 shadow-glass outline-none" />
        </label>
        <label class="flex flex-col gap-1.5">
          <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Discount Type</span>
          <select v-model="form.discount_type" class="rounded-[10px] border border-transparent bg-white/85 px-3 py-2.5 text-sm text-slate-900 shadow-glass outline-none">
            <option value="percent">Percent</option>
            <option value="fixed">Fixed</option>
          </select>
        </label>
        <label class="flex flex-col gap-1.5">
          <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Discount Value</span>
          <input v-model="form.discount_value" type="number" min="1" step="0.01" placeholder="10" class="rounded-[10px] border border-transparent bg-white/85 px-3 py-2.5 text-sm text-slate-900 shadow-glass outline-none" />
        </label>
        <label class="flex flex-col gap-1.5">
          <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Min Booking Amount</span>
          <input v-model="form.min_booking_amount" type="number" min="0" step="0.01" placeholder="0" class="rounded-[10px] border border-transparent bg-white/85 px-3 py-2.5 text-sm text-slate-900 shadow-glass outline-none" />
        </label>
        <label class="flex flex-col gap-1.5">
          <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Start Date</span>
          <input v-model="form.start_date" type="date" class="rounded-[10px] border border-transparent bg-white/85 px-3 py-2.5 text-sm text-slate-900 shadow-glass outline-none" />
        </label>
        <label class="flex flex-col gap-1.5">
          <span class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">End Date</span>
          <input v-model="form.end_date" type="date" class="rounded-[10px] border border-transparent bg-white/85 px-3 py-2.5 text-sm text-slate-900 shadow-glass outline-none" />
        </label>
      </div>

      <div class="mt-4 flex justify-end">
        <button type="button" class="rounded-[10px] border border-transparent bg-[#3361d8] px-4 py-2.5 font-semibold text-white shadow-[0_10px_20px_rgba(51,97,216,0.3)] transition duration-200 hover:-translate-y-0.5 hover:bg-[#2f57c2] hover:shadow-[0_14px_20px_rgba(51,97,216,0.28)] disabled:cursor-not-allowed disabled:opacity-60" :disabled="loading" @click="submitPromo">
          {{ loading ? 'Saving...' : 'Create Promo Code' }}
        </button>
      </div>
    </section>

    <section class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
      <div class="flex items-start justify-between gap-3 max-[900px]:flex-col">
        <div>
          <h4 class="m-0 text-[22px] font-bold text-slate-900">Promo Code List</h4>
          <p class="mt-1 text-slate-600">These offers only work for turfs created by this owner.</p>
        </div>
        <button type="button" class="rounded-[10px] border border-transparent bg-white/80 px-3.5 py-2 font-semibold text-slate-900 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)] disabled:cursor-not-allowed disabled:opacity-60" :disabled="loading" @click="loadPromos">Refresh List</button>
      </div>

      <div v-if="!promos.length" class="mt-4 rounded-[12px] border border-dashed border-slate-300 bg-white/70 px-4 py-10 text-center text-slate-600">
        No promo codes created yet.
      </div>

      <div v-else class="mt-4 overflow-x-auto">
        <table class="min-w-full border-separate border-spacing-y-2 text-left">
          <thead>
            <tr class="text-xs uppercase tracking-[0.18em] text-slate-500">
              <th class="px-3 py-2">Code</th>
              <th class="px-3 py-2">Offer</th>
              <th class="px-3 py-2">Min Amount</th>
              <th class="px-3 py-2">Validity</th>
              <th class="px-3 py-2">Status</th>
              <th class="px-3 py-2">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="promo in promos" :key="promo.promo_id" class="rounded-[12px] bg-white/75 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white/90 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
              <td class="rounded-l-[12px] px-3 py-3 align-top font-semibold text-slate-900">{{ promo.code }}</td>
              <td class="px-3 py-3 align-top text-slate-700">
                {{ promo.discount_type === 'percent' ? `${promo.discount_value}% off` : `Tk ${formatMoney(promo.discount_value)} off` }}
              </td>
              <td class="px-3 py-3 align-top text-slate-700">Tk {{ formatMoney(promo.min_booking_amount) }}</td>
              <td class="px-3 py-3 align-top text-slate-700">
                {{ formatDate(promo.start_date) }} - {{ formatDate(promo.end_date) }}
              </td>
              <td class="px-3 py-3 align-top">
                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="promo.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700'">
                  {{ promo.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="rounded-r-[12px] px-3 py-3 align-top">
                <button type="button" class="rounded-[10px] border border-transparent bg-white/80 px-3 py-1.5 text-sm font-semibold text-slate-900 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)] disabled:cursor-not-allowed disabled:opacity-60" :disabled="loading" @click="togglePromo(promo)">
                  {{ promo.is_active ? 'Deactivate' : 'Activate' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </section>
</template>

<script>
import {
  buildOwnerPromoSummary,
  createOwnerPromoCode,
  createOwnerPromoForm,
  fetchOwnerPromoCodes,
  formatOwnerPromoDate,
  formatOwnerPromoMoney,
  toggleOwnerPromoCode
} from '../support/ownerPromoCodesSupport'

export default {
  name: 'OwnerPromoCodesSection',
  props: {
    ownerId: { type: Number, required: true },
    ownerName: { type: String, default: 'Owner' }
  },
  data() {
    return {
      loading: false,
      message: '',
      messageType: 'info',
      form: createOwnerPromoForm(),
      promos: [],
      summary: {
        total_codes: 0,
        active_codes: 0,
        inactive_codes: 0
      }
    }
  },
  computed: {
    summaryCards() {
      return buildOwnerPromoSummary(this.summary)
    }
  },
  async mounted() {
    await this.loadPromos()
  },
  methods: {
    formatMoney: formatOwnerPromoMoney,
    formatDate: formatOwnerPromoDate,
    async loadPromos() {
      if (!this.ownerId) return
      this.loading = true
      this.message = ''
      try {
        const data = await fetchOwnerPromoCodes(this.ownerId)
        if (!data?.success) {
          this.messageType = 'error'
          this.message = data?.message || 'Failed to load promo codes.'
          return
        }
        this.promos = Array.isArray(data.promos) ? data.promos : []
        this.summary = { ...this.summary, ...(data.summary || {}) }
      } catch (error) {
        this.messageType = 'error'
        this.message = error.response?.data?.message || 'Server connection failed while loading promo codes.'
      } finally {
        this.loading = false
      }
    },
    async submitPromo() {
      if (!this.ownerId) return
      this.loading = true
      this.message = ''
      try {
        const data = await createOwnerPromoCode(this.ownerId, this.form)
        this.messageType = data?.success ? 'success' : 'error'
        this.message = data?.message || 'Promo code request finished.'
        if (data?.success) {
          this.form = createOwnerPromoForm()
          await this.loadPromos()
        }
      } catch (error) {
        this.messageType = 'error'
        this.message = error.response?.data?.message || 'Server connection failed while creating promo code.'
      } finally {
        this.loading = false
      }
    },
    async togglePromo(promo) {
      this.loading = true
      this.message = ''
      try {
        const data = await toggleOwnerPromoCode(this.ownerId, promo.promo_id, !promo.is_active)
        this.messageType = data?.success ? 'success' : 'error'
        this.message = data?.message || 'Promo code updated.'
        await this.loadPromos()
      } catch (error) {
        this.messageType = 'error'
        this.message = error.response?.data?.message || 'Server connection failed while updating promo code.'
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
