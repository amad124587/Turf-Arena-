<script>
export default {
  name: 'UserDashboardSummaryCard',
  props: {
    title: { type: String, default: '' },
    value: { type: [String, Number], default: '' },
    helperText: { type: String, default: '' },
    helperClass: { type: String, default: 'text-slate-500' },
    iconSrc: { type: String, required: true },
    iconAlt: { type: String, default: '' },
    iconShellClass: { type: String, default: 'bg-slate-100' }
  },
  emits: ['click']
}
</script>

<template>
  <button
    type="button"
    class="flex min-h-[154px] w-full flex-col items-start rounded-[22px] border border-white/95 bg-white/82 p-5 text-left text-slate-900 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_24px_rgba(20,32,89,0.16)]"
    @click="$emit('click')"
  >
    <div class="flex items-start gap-3">
      <div class="grid h-[44px] w-[44px] place-items-center rounded-[14px]" :class="iconShellClass">
        <img :src="iconSrc" :alt="iconAlt || title" class="h-6 w-6 object-contain" />
      </div>
      <div>
        <h3 class="m-0 text-[20px] font-semibold tracking-[-0.03em] text-[#16203b]">{{ title }}</h3>
        <p class="mt-3 text-[56px] font-bold leading-none tracking-[-0.05em] text-[#16203b]">{{ value }}</p>
      </div>
    </div>

    <p v-if="helperText" class="mt-auto pt-4 text-[14px] font-medium" :class="helperClass">{{ helperText }}</p>
  </button>
</template>
