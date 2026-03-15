<template>
  <div class="min-h-screen w-full box-border bg-[linear-gradient(135deg,#f8fafc_0%,#eef2ff_50%,#f3f4f6_100%)] p-2.5 font-poppins text-[#111827]">
    <AppTopbar
      wrapper-class="mb-3 w-full px-4 py-3.5 max-md:flex-wrap"
      left-class="gap-2.5 max-md:flex-wrap"
      right-class="gap-1.5 max-md:flex-wrap"
    >
      <template #left>
        <GlassButton class="whitespace-nowrap" @click="switchToUserDashboard">Switch User Dashboard</GlassButton>
      </template>
      <template #right>
        <UserDashboardProfileMenu
          :user-name="ownerName"
          @profile="openProfileMenu"
          @settings="openSettingsMenu"
          @logout="logout"
        />
      </template>
    </AppTopbar>

    <div class="grid min-h-[calc(100vh-108px)] grid-cols-[248px_minmax(0,1fr)] items-start gap-3.5 max-[900px]:grid-cols-1">
      <aside class="sticky top-2 rounded-[20px] border border-transparent bg-white/80 p-3 shadow-glass backdrop-blur-[14px] max-[900px]:static">
        <div class="mb-3 rounded-[16px] border border-transparent bg-white/85 px-3.5 py-3 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
          <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Owner Panel</p>
          <h2 class="mt-1 text-lg font-bold text-slate-900">{{ ownerName }}</h2>
        </div>

        <nav class="flex flex-col gap-2.5">
          <button
            v-for="item in menuItems"
            :key="item.key"
            type="button"
            class="flex items-center gap-3 rounded-[14px] border border-transparent px-3.5 py-3 text-left font-medium text-slate-900 backdrop-blur-[14px] shadow-glass transition duration-200"
            :class="activeTab === item.key ? 'scale-[1.02] bg-slate-900 text-white shadow-[0_16px_24px_rgba(15,23,42,0.18)]' : 'bg-white/75 hover:-translate-y-0.5 hover:scale-[1.02] hover:bg-white/95 hover:font-semibold hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]'"
            @click="activeTab = item.key"
          >
            <img
              :src="ownerMenuIcons[item.key]"
              :alt="`${item.label} icon`"
              class="h-6 w-6 shrink-0 object-contain"
              :class="activeTab === item.key ? 'brightness-0 invert' : ''"
            />
            <span>{{ item.label }}</span>
          </button>
        </nav>
      </aside>

      <main class="min-w-0 rounded-[20px] border border-transparent bg-white/80 p-3.5 shadow-glass backdrop-blur-[14px]">
        <header class="mb-3.5 border-b border-slate-900/10 pb-3.5">
          <h1 class="m-0 text-[40px] font-bold text-slate-900 max-md:text-[22px]">{{ panelTitle }}</h1>
        </header>

        <transition name="page-fade" mode="out-in">
          <OwnerOverviewSection
            v-if="activeTab === 'dashboard'"
            key="owner-dashboard"
            :owner-finance="ownerFinance"
            :format-money="formatMoney"
          />

          <OwnerAddTurfsSection
            v-else-if="activeTab === 'myTurfs'"
            key="owner-add-turfs"
            :form="form"
            :selected-image-name="selectedImageName"
            :image-preview-url="imagePreviewUrl"
            :loading="loading"
            :message="message"
            :message-type="messageType"
            :last-created-turf-id="lastCreatedTurfId"
            :file-input-key="fileInputKey"
            @update:form="form = $event"
            @image-change="handleImageChange"
            @clear-image="clearSelectedImage"
            @reset="resetForm"
            @submit="submitTurf"
          />

          <OwnerMyTurfsSection
            v-else-if="activeTab === 'ownerTurfs'"
            key="owner-my-turfs"
            :owner-id="ownerIdFromSession"
          />

          <OwnerRevenueSection
            v-else-if="activeTab === 'revenue'"
            key="owner-revenue"
            :owner-finance="ownerFinance"
            :owner-transactions="ownerTransactions"
            :finance-loading="financeLoading"
            :format-money="formatMoney"
            @refresh="loadOwnerFinance"
          />

          <OwnerBookingsSection
            v-else-if="activeTab === 'bookings'"
            key="owner-bookings"
            :owner-id="ownerIdFromSession"
          />

          <OwnerSlotControlSection
            v-else-if="activeTab === 'slotControl'"
            key="owner-slot-control"
            :owner-id="ownerIdFromSession"
          />

          <OwnerPromoCodesSection
            v-else-if="activeTab === 'promoCodes'"
            key="owner-promo-codes"
            :owner-id="ownerIdFromSession"
            :owner-name="ownerName"
          />

          <OwnerPlaceholderSection
            v-else
            key="owner-placeholder"
            :panel-title="panelTitle"
          />
        </transition>
      </main>
    </div>
  </div>
</template>

<script>
import AppTopbar from '../components/AppTopbar.vue'
import GlassButton from '../components/GlassButton.vue'
import OwnerAddTurfsSection from '../components/OwnerAddTurfsSection.vue'
import OwnerBookingsSection from '../components/OwnerBookingsSection.vue'
import OwnerMyTurfsSection from '../components/OwnerMyTurfsSection.vue'
import OwnerOverviewSection from '../components/OwnerOverviewSection.vue'
import OwnerPlaceholderSection from '../components/OwnerPlaceholderSection.vue'
import OwnerPromoCodesSection from '../components/OwnerPromoCodesSection.vue'
import OwnerRevenueSection from '../components/OwnerRevenueSection.vue'
import OwnerSlotControlSection from '../components/OwnerSlotControlSection.vue'
import UserDashboardProfileMenu from '../components/UserDashboardProfileMenu.vue'
import addTurfsIcon from '../assets/add-turfs-icon.svg'
import ownerBookingsIcon from '../assets/owner-bookings-icon.svg'
import ownerDashboardIcon from '../assets/owner-dashboard-icon.svg'
import myTurfsIcon from '../assets/my-turfs-icon.svg'
import ownerReviewsIcon from '../assets/owner-reviews-icon.svg'
import promoCodesIcon from '../assets/promo-codes-icon.svg'
import revenueIcon from '../assets/revenue-icon.svg'
import slotControlIcon from '../assets/slot-control-icon.svg'
import {
  createOwnerFinanceSummary,
  createOwnerForm,
  getOwnerSessionUser,
  ownerDashboardMethods,
  OWNER_MENU_ITEMS
} from '../support/ownerDashboardSupport'

export default {
  name: 'OwnerDashboard',
  components: {
    AppTopbar,
    GlassButton,
    OwnerAddTurfsSection,
    OwnerBookingsSection,
    OwnerMyTurfsSection,
    OwnerOverviewSection,
    OwnerPlaceholderSection,
    OwnerPromoCodesSection,
    OwnerRevenueSection,
    OwnerSlotControlSection,
    UserDashboardProfileMenu
  },
  data() {
    const sessionUser = getOwnerSessionUser()
    return {
      activeTab: 'dashboard',
      menuItems: OWNER_MENU_ITEMS,
      loading: false,
      message: '',
      messageType: '',
      lastCreatedTurfId: null,
      selectedImageFile: null,
      selectedImageName: '',
      imagePreviewUrl: '',
      fileInputKey: 0,
      ownerName: localStorage.getItem('user_name') || sessionUser.full_name || 'Owner',
      ownerEmail: localStorage.getItem('user_email') || sessionUser.email || '',
      ownerIdFromSession: Number(localStorage.getItem('owner_id') || sessionUser.owner_id || 0),
      financeLoading: false,
      ownerFinance: createOwnerFinanceSummary(),
      ownerTransactions: [],
      form: createOwnerForm()
    }
  },
  computed: {
    ownerMenuIcons() {
      return {
        dashboard: ownerDashboardIcon,
        myTurfs: addTurfsIcon,
        ownerTurfs: myTurfsIcon,
        bookings: ownerBookingsIcon,
        revenue: revenueIcon,
        slotControl: slotControlIcon,
        promoCodes: promoCodesIcon,
        reviews: ownerReviewsIcon
      }
    },
    panelTitle() {
      const current = this.menuItems.find((item) => item.key === this.activeTab)
      if (!current || current.key === 'dashboard') return 'Owner Dashboard Overview'
      return current.label
    }
  },
  async mounted() {
    await this.loadOwnerFinance()
  },
  methods: {
    ...ownerDashboardMethods,
    openProfileMenu() {
      this.activeTab = 'dashboard'
      window.scrollTo({ top: 0, behavior: 'smooth' })
    },
    openSettingsMenu() {
      // Demo placeholder for future settings action.
    },
    logout() {
      localStorage.clear()
      this.$router.push('/login')
    }
  },
  beforeUnmount() {
    if (this.imagePreviewUrl) {
      URL.revokeObjectURL(this.imagePreviewUrl)
    }
  }
}
</script>
