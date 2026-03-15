<script>
export default {
  name: 'AppTopbar',
  props: {
    brand: {
      type: String,
      default: 'Turf Arena'
    },
    wrapperClass: {
      type: [String, Array, Object],
      default: ''
    },
    leftClass: {
      type: [String, Array, Object],
      default: ''
    },
    rightClass: {
      type: [String, Array, Object],
      default: ''
    }
  }
}
</script>

<template>
  <header class="relative z-[60] flex items-center justify-between gap-3 rounded-[18px] border border-white/95 bg-white/80 px-[18px] py-3 backdrop-blur-[14px] shadow-glass" :class="wrapperClass">
    <div class="flex items-center gap-2.5" :class="leftClass">
      <slot name="left" />
      <div class="text-[30px] font-bold tracking-[0.2px] text-slate-900 max-md:text-2xl">{{ brand }}</div>
    </div>

    <div class="flex items-center gap-2.5" :class="rightClass">
      <slot name="right" />
    </div>
  </header>
</template>
