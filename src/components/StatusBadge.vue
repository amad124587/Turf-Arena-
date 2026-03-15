<template>
  <span :class="badgeClass">
    <slot>{{ label }}</slot>
  </span>
</template>

<script>
export default {
  name: 'StatusBadge',
  props: {
    label: {
      type: String,
      default: ''
    },
    tone: {
      type: String,
      default: 'neutral'
    },
    bordered: {
      type: Boolean,
      default: true
    }
  },
  computed: {
    badgeClass() {
      const toneMap = {
        success: 'bg-green-200/90 text-green-800',
        warning: 'bg-yellow-100/90 text-yellow-800',
        danger: 'bg-red-100/90 text-red-800',
        info: 'bg-blue-200/90 text-blue-800',
        neutral: 'bg-slate-100/90 text-slate-700'
      }

      return [
        'inline-block rounded-full px-2.5 py-1 text-xs font-bold capitalize',
        this.bordered ? 'border border-white/75' : '',
        toneMap[this.tone] || toneMap.neutral
      ]
    }
  }
}
</script>
