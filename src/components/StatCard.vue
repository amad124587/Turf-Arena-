<script>
export default {
  name: 'StatCard',
  inheritAttrs: false,
  props: {
    tag: {
      type: String,
      default: 'button'
    },
    title: {
      type: String,
      default: ''
    },
    value: {
      type: [String, Number],
      default: ''
    },
    customClass: {
      type: [String, Array, Object],
      default: ''
    }
  }
}
</script>

<template>
  <component
    :is="tag"
    :type="tag === 'button' ? 'button' : undefined"
    class="appearance-none rounded-[14px] border border-transparent bg-white/80 p-3.5 text-left text-slate-900 backdrop-blur-[14px] shadow-glass outline-none transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]"
    :class="customClass"
    v-bind="$attrs"
  >
    <h3 class="m-0 text-[24px] font-semibold tracking-[-0.03em] text-slate-900">{{ title }}</h3>
    <p class="mt-2 text-[52px] font-bold leading-none tracking-[-0.04em] text-slate-900">{{ value }}</p>
    <slot />
  </component>
</template>
