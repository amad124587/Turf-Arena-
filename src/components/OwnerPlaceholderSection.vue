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
    <p class="mt-2 text-slate-600">This section is ready for your custom logic and action buttons.</p>
    <div class="mt-4 grid grid-cols-3 gap-2.5 max-[1200px]:grid-cols-2 max-[900px]:grid-cols-1">
      <button type="button" class="min-h-11 rounded-[10px] border border-dashed border-blue-500/45 bg-white/80 p-[10px_12px] font-semibold text-slate-700 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:border-blue-500/70 hover:text-blue-900 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">+ Add New Button</button>
      <button type="button" class="min-h-11 rounded-[10px] border border-dashed border-blue-500/45 bg-white/80 p-[10px_12px] font-semibold text-slate-700 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:border-blue-500/70 hover:text-blue-900 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">+ Add Table</button>
      <button type="button" class="min-h-11 rounded-[10px] border border-dashed border-blue-500/45 bg-white/80 p-[10px_12px] font-semibold text-slate-700 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:border-blue-500/70 hover:text-blue-900 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">+ Add Action Panel</button>
    </div>
  </section>
</template>
