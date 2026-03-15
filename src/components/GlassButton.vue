<template>
  <component
    :is="tag"
    :type="resolvedType"
    :disabled="tag === 'button' ? disabled : undefined"
    :class="buttonClass"
    v-bind="$attrs"
  >
    <slot />
  </component>
</template>

<script>
export default {
  name: 'GlassButton',
  inheritAttrs: false,
  props: {
    tag: {
      type: String,
      default: 'button'
    },
    type: {
      type: String,
      default: 'button'
    },
    disabled: {
      type: Boolean,
      default: false
    },
    subtle: {
      type: Boolean,
      default: false
    },
    block: {
      type: Boolean,
      default: false
    },
    active: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    resolvedType() {
      return this.tag === 'button' ? this.type : undefined
    },
    buttonClass() {
      return [
        'rounded-full border border-transparent bg-white/80 px-3.5 py-2 font-semibold text-slate-900 backdrop-blur-[14px] shadow-glass',
        this.subtle ? '' : 'transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]',
        this.block ? 'w-full' : '',
        this.active ? 'ring-2 ring-blue-300' : '',
        this.disabled ? 'disabled:cursor-not-allowed disabled:opacity-65' : ''
      ]
    }
  }
}
</script>
