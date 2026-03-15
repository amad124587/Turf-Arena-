<script>
import GlassButton from './GlassButton.vue'

export default {
  name: 'UserReviewModal',
  components: {
    GlassButton
  },
  props: {
    visible: {
      type: Boolean,
      default: false
    },
    pendingReview: {
      type: Object,
      default: null
    },
    rating: {
      type: Number,
      default: 0
    },
    comment: {
      type: String,
      default: ''
    },
    message: {
      type: String,
      default: ''
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close', 'submit', 'set-rating', 'update:comment']
}
</script>

<template>
  <div v-if="visible && pendingReview" class="fixed inset-0 z-50 grid place-items-center bg-slate-900/30 p-4" @click.self="$emit('close')">
    <div class="w-full max-w-[460px] rounded-[18px] border border-white/95 bg-white/80 p-[18px] text-slate-900 backdrop-blur-[14px] shadow-glass">
      <h3 class="m-0">Rate your last booking</h3>
      <p class="mt-2 text-[#1f2937]">{{ pendingReview.turf_name }}</p>

      <div class="my-3 flex gap-1.5">
        <button
          v-for="n in 5"
          :key="n"
          type="button"
          class="h-[38px] w-[38px] cursor-pointer rounded-[10px] border border-white/75 bg-white/80 text-lg text-slate-900"
          :class="{ 'bg-[#fde68a] text-[#3b2f03]': rating >= n }"
          @click="$emit('set-rating', n)"
        >
          *
        </button>
      </div>

      <textarea
        :value="comment"
        class="min-h-[80px] w-full resize-y rounded-xl border border-white/80 bg-white/70 p-2.5 text-slate-900"
        placeholder="Write a short comment (optional)"
        @input="$emit('update:comment', $event.target.value)"
      ></textarea>

      <p v-if="message" class="mt-2.5 text-sm text-[#111827]">{{ message }}</p>

      <div class="mt-3 flex justify-end gap-2.5">
        <GlassButton @click="$emit('submit')" :disabled="loading">
          {{ loading ? 'Submitting...' : 'Submit Review' }}
        </GlassButton>
        <GlassButton @click="$emit('close')">Close</GlassButton>
      </div>
    </div>
  </div>
</template>
