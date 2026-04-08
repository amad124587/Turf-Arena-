<script>
import axios from 'axios'
import AppTopbar from '../components/AppTopbar.vue'
import GlassButton from '../components/GlassButton.vue'
import StatusBadge from '../components/StatusBadge.vue'
import UserSidebar from '../components/UserSidebar.vue'

const API_PROTOCOL = typeof window !== 'undefined' && window.location.protocol.startsWith('http')
  ? window.location.protocol
  : 'http:'
const API_HOST = typeof window !== 'undefined' ? window.location.hostname : '127.0.0.1'
const API_BASE = `${API_PROTOCOL}//${API_HOST}/turfbooking/backend`

export default {
  name: 'UserTransactionHistoryPage',
  components: {
    AppTopbar,
    GlassButton,
    StatusBadge,
    UserSidebar
  },
  data() {
    return {
      userId: Number(localStorage.getItem('user_id') || 0),
      userName: localStorage.getItem('user_name') || 'User',
      rows: [],
      loading: false
    }
  },
  computed: {
    totalPaid() {
      return this.rows.filter((row) => row.amount < 0).reduce((sum, row) => sum + Math.abs(row.amount), 0).toFixed(2)
    },
    totalRefunded() {
      return this.rows.filter((row) => row.amount > 0).reduce((sum, row) => sum + row.amount, 0).toFixed(2)
    }
  },
  async mounted() {
    if (!this.userId) {
      this.$router.push('/login')
      return
    }
    await this.loadTransactions()
  },
  methods: {
    async loadTransactions() {
      this.loading = true
      try {
        const response = await axios.get(`${API_BASE}/get_user_transactions.php?user_id=${this.userId}`)
        if (response.data?.success) {
          this.rows = Array.isArray(response.data.transactions) ? response.data.transactions : []
        } else {
          this.rows = []
        }
      } catch (error) {
        this.rows = []
      } finally {
        this.loading = false
      }
    },
    async refreshTransactions() {
      await this.loadTransactions()
    },
    handleSidebarAction(key) {
      if (key === 'dashboard') {
        this.$router.push('/dashboard')
        return
      }
      if (key === 'browse') {
        this.$router.push('/browse')
        return
      }
      if (key === 'bookings') {
        this.$router.push('/bookings')
        return
      }
      if (key === 'transactions') return
      if (key === 'wallet' || key === 'review' || key === 'support') return
    }
  }
}
</script>

<template>
  <div class="min-h-screen box-border bg-[radial-gradient(circle_at_top_left,rgba(217,220,219,0.9),transparent_26%),radial-gradient(circle_at_top_right,rgba(186,195,205,0.34),transparent_22%),linear-gradient(180deg,#f5f6f7_0%,#d9dcdb_34%,#bac3cd_66%,#2d3945_100%)] p-2.5 font-poppins">
    <AppTopbar wrapper-class="mb-3">
      <template #right>
        <div class="font-semibold text-slate-900">Transaction History</div>
      </template>
    </AppTopbar>

    <div class="grid min-h-[calc(100vh-108px)] grid-cols-[248px_minmax(0,1fr)] items-start gap-3.5 max-[900px]:grid-cols-1">
      <UserSidebar
        :user-name="userName"
        active-key="transactions"
        @select="handleSidebarAction"
      />

      <main class="min-w-0 rounded-[20px] border border-white/95 bg-white/80 p-4 backdrop-blur-[14px] shadow-glass">
      <div class="flex flex-wrap items-center justify-between gap-2.5">
        <div>
          <h1 class="m-0 text-[26px] font-bold tracking-[-0.03em] text-slate-900">Transaction History</h1>
          <p class="mt-1 text-sm text-slate-600">Review your booking payments, wallet refunds, and credits.</p>
        </div>
        <GlassButton @click="refreshTransactions" :disabled="loading">
          {{ loading ? 'Loading...' : 'Refresh' }}
        </GlassButton>
      </div>

      <section class="mt-4 grid grid-cols-3 gap-3 max-[900px]:grid-cols-1">
        <article class="rounded-[16px] border border-white/95 bg-white/85 p-4 shadow-glass">
          <p class="m-0 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total Paid</p>
          <p class="mt-2 text-[30px] font-bold text-slate-900">Tk {{ totalPaid }}</p>
        </article>
        <article class="rounded-[16px] border border-white/95 bg-white/85 p-4 shadow-glass">
          <p class="m-0 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Refunded</p>
          <p class="mt-2 text-[30px] font-bold text-slate-900">Tk {{ totalRefunded }}</p>
        </article>
        <article class="rounded-[16px] border border-white/95 bg-white/85 p-4 shadow-glass">
          <p class="m-0 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Entries</p>
          <p class="mt-2 text-[30px] font-bold text-slate-900">{{ rows.length }}</p>
        </article>
      </section>

      <div v-if="!loading && rows.length === 0" class="mt-4 rounded-[16px] border border-white/95 bg-white/85 p-4 text-slate-600 shadow-glass">
        No transactions found yet.
      </div>

      <section v-else class="mt-4 flex flex-col gap-3">
        <article
          v-for="row in rows"
          :key="row.id"
          class="rounded-[16px] border border-white/95 bg-white/85 p-4 shadow-glass"
        >
          <div class="flex items-center justify-between gap-3 max-[760px]:flex-col max-[760px]:items-start">
            <div>
              <div class="flex items-center gap-2">
                <StatusBadge :label="row.type" :tone="row.tone" />
                <span class="text-sm text-slate-500">{{ row.reference }}</span>
              </div>
              <h3 class="mt-2 text-lg font-semibold text-slate-900">{{ row.title }}</h3>
              <p class="mt-1 text-sm text-slate-600">{{ row.description }}</p>
              <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500">
                <span><b>Method:</b> {{ row.method }}</span>
                <span><b>Status:</b> {{ row.status }}</span>
              </div>
            </div>

            <div class="text-right max-[760px]:text-left">
              <p class="text-[22px] font-bold" :class="row.amount > 0 ? 'text-slate-900' : row.amount < 0 ? 'text-rose-700' : 'text-amber-700'">
                {{ row.amount > 0 ? '+' : row.amount < 0 ? '-' : '' }}Tk {{ Math.abs(row.amount).toFixed(2) }}
              </p>
              <p class="mt-1 text-sm text-slate-500">{{ row.time }}</p>
            </div>
          </div>
        </article>
      </section>
      </main>
    </div>
  </div>
</template>
