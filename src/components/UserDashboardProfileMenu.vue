<script>
import avatarSrc from '../assets/white avatar.svg'
import chevronIcon from '../assets/chevron.svg'
import profileIcon from '../assets/profile.svg'
import settingsIcon from '../assets/settings.svg'
import logoutIcon from '../assets/logout.svg'

export default {
  name: 'UserDashboardProfileMenu',
  props: {
    userName: {
      type: String,
      default: 'User'
    }
  },
  emits: ['profile', 'settings', 'logout'],
  data() {
    return {
      menuOpen: false,
      avatarSrc,
      chevronIcon,
      profileIcon,
      settingsIcon,
      logoutIcon
    }
  },
  mounted() {
    document.addEventListener('click', this.handleDocumentClick)
    document.addEventListener('keydown', this.handleEscape)
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleDocumentClick)
    document.removeEventListener('keydown', this.handleEscape)
  },
  methods: {
    toggleMenu() {
      this.menuOpen = !this.menuOpen
    },
    emitAction(type) {
      this.menuOpen = false
      this.$emit(type)
    },
    handleDocumentClick(event) {
      if (!this.menuOpen) return
      if (this.$refs.root?.contains(event.target)) return
      this.menuOpen = false
    },
    handleEscape(event) {
      if (event.key === 'Escape') {
        this.menuOpen = false
      }
    }
  }
}
</script>

<template>
  <div ref="root" class="relative z-[70]">
    <button
      type="button"
      class="group flex min-w-[190px] items-center justify-between gap-3 rounded-[14px] border border-transparent px-3.5 py-3 text-slate-900 backdrop-blur-[14px] shadow-glass transition duration-200"
      :class="menuOpen
        ? 'scale-[1.02] bg-white/95 shadow-[0_16px_24px_rgba(15,23,42,0.18)]'
        : 'bg-white/75 hover:-translate-y-0.5 hover:scale-[1.02] hover:bg-white/95 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]'"
      @click="toggleMenu"
    >
      <div class="flex items-center gap-3">
        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/85 shadow-[0_8px_16px_rgba(20,32,89,0.12)]">
          <img :src="avatarSrc" alt="Profile" class="h-8 w-8 rounded-full object-contain" />
        </span>
        <span class="text-[18px] font-semibold tracking-[-0.02em] text-[#1b2441]">{{ userName }}</span>
      </div>
      <img :src="chevronIcon" alt="" class="h-5 w-5 object-contain text-slate-500 transition duration-200 group-hover:text-slate-700" :class="menuOpen ? 'rotate-180' : ''" aria-hidden="true" />
    </button>

    <div
      v-if="menuOpen"
      class="absolute right-0 top-[calc(100%+12px)] z-[80] min-w-[220px] overflow-hidden rounded-[20px] border border-[#d8def0] bg-[linear-gradient(180deg,rgba(255,255,255,0.98),rgba(244,247,255,0.95))] p-2.5 shadow-[0_26px_46px_rgba(30,41,84,0.18)] backdrop-blur-[22px]"
    >
      <button
        type="button"
        class="group flex w-full items-center gap-3 rounded-[14px] border border-transparent bg-white/75 px-3.5 py-3 text-left text-[17px] font-medium text-[#22304f] backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:scale-[1.02] hover:bg-white/95 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]"
        @click="emitAction('profile')"
      >
        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/85 shadow-[0_8px_16px_rgba(20,32,89,0.12)] transition duration-200 group-hover:scale-105">
          <img :src="profileIcon" alt="" class="h-5 w-5 object-contain" aria-hidden="true" />
        </span>
        <span>Profile</span>
      </button>

      <button
        type="button"
        class="group mt-2 flex w-full items-center gap-3 rounded-[14px] border border-transparent bg-white/75 px-3.5 py-3 text-left text-[17px] font-medium text-[#22304f] backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:scale-[1.02] hover:bg-white/95 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]"
        @click="emitAction('settings')"
      >
        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white/85 shadow-[0_8px_16px_rgba(20,32,89,0.12)] transition duration-200 group-hover:scale-105">
          <img :src="settingsIcon" alt="" class="h-5 w-5 object-contain" aria-hidden="true" />
        </span>
        <span>Settings</span>
      </button>

      <button
        type="button"
        class="group mt-2 flex w-full items-center gap-3 rounded-[14px] border border-transparent bg-white/75 px-3.5 py-3 text-left text-[17px] font-semibold text-[#17203a] backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:scale-[1.02] hover:bg-white/95 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]"
        @click="emitAction('logout')"
      >
        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#f8e6e6] shadow-[0_8px_16px_rgba(176,72,72,0.12)] transition duration-200 group-hover:scale-105">
          <img :src="logoutIcon" alt="" class="h-5 w-5 object-contain" aria-hidden="true" />
        </span>
        <span class="tracking-[-0.01em] text-[#1d2440]">Logout</span>
      </button>
    </div>
  </div>
</template>
