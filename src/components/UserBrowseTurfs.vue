<script>
import AppTopbar from '../components/AppTopbar.vue'
import UserBrowseTurfCard from '../components/UserBrowseTurfCard.vue'
import GlassButton from '../components/GlassButton.vue'
import UserSidebar from '../components/UserSidebar.vue'
import {
  getAvailableSports,
  getDhakaTodayDate,
  getFilteredTurfs,
  userBrowseTurfsMethods
} from '../support/userBrowseTurfsSupport'

export default {
  name: 'UserBrowseTurfs',
  components: {
    AppTopbar,
    UserBrowseTurfCard,
    GlassButton,
    UserSidebar
  },
  data() {
    return {
      userName: localStorage.getItem('user_name') || 'User',
      turfs: [],
      loading: false,
      statusText: '',
      errorText: '',
      bookingMap: {},
      promoLoadingMap: {},
      slotLoadingMap: {},
      dateInputDebounceMap: {},
      searchText: '',
      sportFilter: 'all',
      locationFilter: '',
      minPrice: '',
      maxPrice: '',
      sortBy: 'newest',
      todayDate: getDhakaTodayDate()
    }
  },
  computed: {
    availableSports() {
      return getAvailableSports(this.turfs)
    },
    filteredTurfs() {
      return getFilteredTurfs(this.turfs, {
        searchText: this.searchText,
        sportFilter: this.sportFilter,
        locationFilter: this.locationFilter,
        minPrice: this.minPrice,
        maxPrice: this.maxPrice,
        sortBy: this.sortBy
      })
    }
  },
  async mounted() {
    await this.loadTurfs()
  },
  methods: {
    ...userBrowseTurfsMethods,
    handleSidebarAction(key) {
      if (key === 'dashboard') {
        this.$router.push('/dashboard')
        return
      }
      if (key === 'browse') return
      if (key === 'bookings') {
        this.$router.push('/bookings')
        return
      }
      if (key === 'transactions') {
        this.$router.push('/transactions')
        return
      }
      if (key === 'wallet' || key === 'review' || key === 'support') return
    }
  }
}
</script>

<template>
  <div class="min-h-screen w-full box-border bg-[radial-gradient(circle_at_top_left,rgba(217,220,219,0.9),transparent_26%),radial-gradient(circle_at_top_right,rgba(186,195,205,0.34),transparent_22%),linear-gradient(180deg,#f5f6f7_0%,#d9dcdb_34%,#bac3cd_66%,#2d3945_100%)] p-2.5 font-poppins text-[#202833]">
    <AppTopbar wrapper-class="mb-3 max-[760px]:flex-wrap">
      <template #right>
        <div class="font-semibold">Browse Turfs</div>
      </template>
    </AppTopbar>

    <div class="grid min-h-[calc(100vh-108px)] grid-cols-[248px_minmax(0,1fr)] items-start gap-3.5 max-[900px]:grid-cols-1">
      <UserSidebar
        :user-name="userName"
        active-key="browse"
        @select="handleSidebarAction"
      />

      <main class="min-w-0 rounded-[20px] border border-white/95 bg-white/80 p-4 backdrop-blur-[14px] shadow-glass">
        <div class="flex items-center justify-between gap-2.5 max-[760px]:flex-wrap">
          <h1 class="m-0 text-[34px] font-bold">Available Turfs</h1>
          <GlassButton @click="loadTurfs" :disabled="loading">
            {{ loading ? 'Loading...' : 'Refresh' }}
          </GlassButton>
        </div>

        <div class="mt-3 grid grid-cols-[2fr_repeat(5,minmax(0,1fr))] gap-2 rounded-[14px] border border-white/95 bg-white/80 p-2.5 backdrop-blur-[14px] shadow-glass max-[1100px]:grid-cols-3 max-[760px]:grid-cols-1">
          <input
            v-model.trim="searchText"
            type="text"
            class="rounded-[10px] border border-white/95 bg-white/80 px-2.5 py-[9px] text-slate-900 backdrop-blur-[14px] shadow-glass"
            placeholder="Search turf name..."
          />

        <select v-model="sportFilter" class="rounded-[10px] border border-white/95 bg-white/80 px-2.5 py-[9px] text-slate-900 backdrop-blur-[14px] shadow-glass">
          <option value="all">All Sports</option>
          <option v-for="sport in availableSports" :key="sport" :value="sport">
            {{ sport }}
          </option>
        </select>

        <input
          v-model.trim="locationFilter"
          type="text"
          class="rounded-[10px] border border-white/95 bg-white/80 px-2.5 py-[9px] text-slate-900 backdrop-blur-[14px] shadow-glass"
          placeholder="Filter by location"
        />

        <input
          v-model.number="minPrice"
          type="number"
          min="0"
          step="1"
          class="rounded-[10px] border border-white/95 bg-white/80 px-2.5 py-[9px] text-slate-900 backdrop-blur-[14px] shadow-glass"
          placeholder="Min price"
        />

        <input
          v-model.number="maxPrice"
          type="number"
          min="0"
          step="1"
          class="rounded-[10px] border border-white/95 bg-white/80 px-2.5 py-[9px] text-slate-900 backdrop-blur-[14px] shadow-glass"
          placeholder="Max price"
        />

        <select v-model="sortBy" class="rounded-[10px] border border-white/95 bg-white/80 px-2.5 py-[9px] text-slate-900 backdrop-blur-[14px] shadow-glass">
          <option value="newest">Sort: Newest</option>
          <option value="price_low">Sort: Price Low to High</option>
          <option value="price_high">Sort: Price High to Low</option>
          <option value="name_az">Sort: Name A-Z</option>
        </select>
        </div>

        <p v-if="errorText" class="mt-2.5 text-sm font-semibold text-red-700">{{ errorText }}</p>
        <p v-else-if="statusText" class="mt-2.5 text-sm font-semibold text-blue-700">{{ statusText }}</p>

        <div v-if="!loading && filteredTurfs.length === 0" class="mt-3.5 rounded-[14px] border border-white/95 bg-white/80 p-[18px] backdrop-blur-[14px] shadow-glass">
          No turfs matched your filters.
        </div>

        <section v-else class="mt-3.5 grid grid-cols-3 gap-3.5 max-[1100px]:grid-cols-2 max-[760px]:grid-cols-1">
          <UserBrowseTurfCard
            v-for="turf in filteredTurfs"
            :key="turf.turf_id"
            :turf="turf"
            :today-date="todayDate"
            :slot-loading="slotLoadingMap[turf.turf_id] === true"
            :booking-loading="bookingMap[turf.turf_id] === true"
            :promo-loading="promoLoadingMap[turf.turf_id] === true"
            :selected-slot="getSelectedSlot(turf)"
            :price-breakdown="getPriceBreakdown(turf)"
            :format-slot-label="formatSlotLabel"
            :format-duration="formatDuration"
            :format-money="formatMoney"
            :status-text-class="statusTextClass"
            @update-field="updateTurfField"
            @date-change="onDateChange"
            @slot-change="onSlotChange"
            @apply-promo="applyPromoCode"
            @book="bookTurf"
          />
        </section>
      </main>
    </div>
  </div>
</template>
