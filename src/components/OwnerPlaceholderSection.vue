<script>
export default {
  name: 'OwnerPlaceholderSection',
  props: {
    panelTitle: {
      type: String,
      required: true
    }
  }
}
</script>

<template>
  <section class="min-h-[calc(100vh-190px)] rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)] max-[900px]:min-h-0">
    <h3 class="m-0 text-[22px] text-slate-900">{{ panelTitle }}</h3>
    <p class="mt-2 text-slate-600">This is a demo section for now. You can build the full {{ panelTitle.toLowerCase() }} workflow here later.</p>
  </section>
</template>
