<script>
import browseTurfsIcon from '../assets/browse-turfs-icon.svg'
import myBookingsIcon from '../assets/my-bookings-icon.svg'
import myWalletIcon from '../assets/my-wallet-icon.svg'
import rateATurfIcon from '../assets/rate-a-turf-icon.svg'
import supportIcon from '../assets/support-icon.svg'
import transactionHistoryIcon from '../assets/transaction-history-icon.svg'
import userDashboardIcon from '../assets/user-dashboard-icon.svg'

export default {
  name: 'UserSidebar',
  props: {
    userName: {
      type: String,
      default: ''
    },
    activeKey: {
      type: String,
      default: 'dashboard'
    }
  },
  emits: ['select'],
  computed: {
    sidebarItems() {
      return [
        { key: 'dashboard', label: 'User Dashboard', icon: userDashboardIcon },
        { key: 'browse', label: 'Browse Turfs', icon: browseTurfsIcon },
        { key: 'bookings', label: 'My Bookings', icon: myBookingsIcon },
        { key: 'wallet', label: 'My Wallet', icon: myWalletIcon },
        { key: 'review', label: 'Rate a Turf', icon: rateATurfIcon },
        { key: 'transactions', label: 'Transaction History', icon: transactionHistoryIcon },
        { key: 'support', label: 'Support', icon: supportIcon }
      ]
    }
  }
}
</script>

<template>
  <aside class="sticky top-2 rounded-[20px] border border-transparent bg-white/80 p-3 shadow-glass backdrop-blur-[14px] max-[900px]:static">
    <div class="mb-3 rounded-[16px] border border-transparent bg-white/85 px-3.5 py-3 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
      <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">User Panel</p>
      <h2 class="mt-1 text-lg font-bold text-slate-900">{{ userName || 'User' }}</h2>
    </div>

    <nav class="flex flex-col gap-2.5">
      <button
        v-for="item in sidebarItems"
        :key="item.key"
        type="button"
        class="flex items-center gap-3 rounded-[14px] border border-transparent px-3.5 py-3 text-left font-medium text-slate-900 backdrop-blur-[14px] shadow-glass transition duration-200"
        :class="item.key === activeKey
          ? 'scale-[1.02] bg-slate-900 text-white shadow-[0_16px_24px_rgba(15,23,42,0.18)]'
          : 'bg-white/75 hover:-translate-y-0.5 hover:scale-[1.02] hover:bg-white/95 hover:font-semibold hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]'"
        @click="$emit('select', item.key)"
      >
        <img
          :src="item.icon"
          :alt="`${item.label} icon`"
          class="h-6 w-6 shrink-0 object-contain"
          :class="item.key === activeKey ? 'brightness-0 invert' : ''"
        />
        <span>{{ item.label }}</span>
      </button>
    </nav>
  </aside>
</template>
