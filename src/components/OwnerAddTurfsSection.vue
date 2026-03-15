<template>
  <section class="min-h-[calc(100vh-190px)] rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)] max-[900px]:min-h-0">
    <div class="mb-2.5">
      <h3 class="m-0 text-[22px] text-slate-900">Add Turf</h3>
      <p class="mt-2 text-slate-600">Form fields map directly to your `turfs` table schema.</p>
    </div>

    <form class="mt-1.5" @submit.prevent="$emit('submit')">
      <div class="grid grid-cols-3 gap-2.5 max-[1200px]:grid-cols-2 max-[900px]:grid-cols-1">
        <label class="flex flex-col gap-1.5 text-[13px] text-slate-900">
          Turf Name *
          <input :value="form.turf_name" type="text" maxlength="150" required class="box-border w-full rounded-lg border border-white/95 bg-white/90 px-2.5 py-2.5 text-slate-900 shadow-[0_6px_14px_rgba(15,23,42,0.05)] outline-none focus:border-blue-500/55 focus:ring-4 focus:ring-blue-500/15" @input="update('turf_name', $event.target.value)" />
        </label>
        <label class="flex flex-col gap-1.5 text-[13px] text-slate-900">
          Sport Type *
          <select :value="form.sport_type" required class="box-border w-full rounded-lg border border-white/95 bg-white/90 px-2.5 py-2.5 text-slate-900 shadow-[0_6px_14px_rgba(15,23,42,0.05)] outline-none focus:border-blue-500/55 focus:ring-4 focus:ring-blue-500/15" @change="update('sport_type', $event.target.value)">
            <option value="football">Football</option>
            <option value="cricket">Cricket</option>
            <option value="badminton">Badminton</option>
            <option value="basketball">Basketball</option>
            <option value="tennis">Tennis</option>
            <option value="other">Other</option>
          </select>
        </label>
        <label class="flex flex-col gap-1.5 text-[13px] text-slate-900">
          Price Per Hour *
          <input :value="form.price_per_hour" type="number" min="1" step="0.01" required class="box-border w-full rounded-lg border border-white/95 bg-white/90 px-2.5 py-2.5 text-slate-900 shadow-[0_6px_14px_rgba(15,23,42,0.05)] outline-none focus:border-blue-500/55 focus:ring-4 focus:ring-blue-500/15" @input="update('price_per_hour', $event.target.value)" />
        </label>
        <label class="flex flex-col gap-1.5 text-[13px] text-slate-900">
          Address *
          <input :value="form.address" type="text" maxlength="255" required class="box-border w-full rounded-lg border border-white/95 bg-white/90 px-2.5 py-2.5 text-slate-900 shadow-[0_6px_14px_rgba(15,23,42,0.05)] outline-none focus:border-blue-500/55 focus:ring-4 focus:ring-blue-500/15" @input="update('address', $event.target.value)" />
        </label>
        <label class="flex flex-col gap-1.5 text-[13px] text-slate-900">
          Area
          <input :value="form.area" type="text" maxlength="120" class="box-border w-full rounded-lg border border-white/95 bg-white/90 px-2.5 py-2.5 text-slate-900 shadow-[0_6px_14px_rgba(15,23,42,0.05)] outline-none focus:border-blue-500/55 focus:ring-4 focus:ring-blue-500/15" @input="update('area', $event.target.value)" />
        </label>
        <label class="flex flex-col gap-1.5 text-[13px] text-slate-900">
          City
          <input :value="form.city" type="text" maxlength="120" class="box-border w-full rounded-lg border border-white/95 bg-white/90 px-2.5 py-2.5 text-slate-900 shadow-[0_6px_14px_rgba(15,23,42,0.05)] outline-none focus:border-blue-500/55 focus:ring-4 focus:ring-blue-500/15" @input="update('city', $event.target.value)" />
        </label>
        <label class="flex flex-col gap-1.5 text-[13px] text-slate-900">
          Location Text
          <input :value="form.location" type="text" maxlength="255" class="box-border w-full rounded-lg border border-white/95 bg-white/90 px-2.5 py-2.5 text-slate-900 shadow-[0_6px_14px_rgba(15,23,42,0.05)] outline-none focus:border-blue-500/55 focus:ring-4 focus:ring-blue-500/15" @input="update('location', $event.target.value)" />
        </label>
        <label class="flex flex-col gap-1.5 text-[13px] text-slate-900">
          Latitude
          <input :value="form.latitude" type="number" step="0.0000001" min="-90" max="90" class="box-border w-full rounded-lg border border-white/95 bg-white/90 px-2.5 py-2.5 text-slate-900 shadow-[0_6px_14px_rgba(15,23,42,0.05)] outline-none focus:border-blue-500/55 focus:ring-4 focus:ring-blue-500/15" @input="update('latitude', $event.target.value)" />
        </label>
        <label class="flex flex-col gap-1.5 text-[13px] text-slate-900">
          Longitude
          <input :value="form.longitude" type="number" step="0.0000001" min="-180" max="180" class="box-border w-full rounded-lg border border-white/95 bg-white/90 px-2.5 py-2.5 text-slate-900 shadow-[0_6px_14px_rgba(15,23,42,0.05)] outline-none focus:border-blue-500/55 focus:ring-4 focus:ring-blue-500/15" @input="update('longitude', $event.target.value)" />
        </label>
        <label class="flex flex-col gap-1.5 text-[13px] text-slate-900">
          Status
          <select :value="form.status" class="box-border w-full rounded-lg border border-white/95 bg-white/90 px-2.5 py-2.5 text-slate-900 shadow-[0_6px_14px_rgba(15,23,42,0.05)] outline-none focus:border-blue-500/55 focus:ring-4 focus:ring-blue-500/15" @change="update('status', $event.target.value)">
            <option value="pending">Pending</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="rejected">Rejected</option>
          </select>
        </label>
        <label class="flex flex-col gap-1.5 text-[13px] text-slate-900">
          Cancel Before Hours
          <input :value="form.cancel_before_hours" type="number" min="0" step="1" class="box-border w-full rounded-lg border border-white/95 bg-white/90 px-2.5 py-2.5 text-slate-900 shadow-[0_6px_14px_rgba(15,23,42,0.05)] outline-none focus:border-blue-500/55 focus:ring-4 focus:ring-blue-500/15" @input="updateNumber('cancel_before_hours', $event.target.value)" />
        </label>
        <label class="flex flex-col gap-1.5 text-[13px] text-slate-900">
          Refund Percent
          <input :value="form.refund_percent" type="number" min="0" max="100" step="1" class="box-border w-full rounded-lg border border-white/95 bg-white/90 px-2.5 py-2.5 text-slate-900 shadow-[0_6px_14px_rgba(15,23,42,0.05)] outline-none focus:border-blue-500/55 focus:ring-4 focus:ring-blue-500/15" @input="updateNumber('refund_percent', $event.target.value)" />
        </label>
        <label class="col-span-3 flex flex-col gap-1.5 text-[13px] text-slate-900 max-[1200px]:col-span-2 max-[900px]:col-span-1">
          Turf Image *
          <input
            :key="fileInputKey"
            type="file"
            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
            class="box-border w-full rounded-lg border border-white/95 bg-white/90 px-2.5 py-2.5 text-slate-900 shadow-[0_6px_14px_rgba(15,23,42,0.05)] outline-none"
            @change="$emit('image-change', $event)"
          />
          <small class="text-xs text-slate-600">Allowed: JPG, PNG, WEBP (max 5MB)</small>
          <span v-if="selectedImageName" class="text-xs font-semibold text-blue-900">{{ selectedImageName }}</span>
        </label>
      </div>

      <label class="mt-2.5 flex flex-col gap-1.5 text-[13px] text-slate-900">
        Description
        <textarea
          :value="form.description"
          rows="4"
          class="box-border w-full rounded-lg border border-white/95 bg-white/90 px-2.5 py-2.5 text-slate-900 shadow-[0_6px_14px_rgba(15,23,42,0.05)] outline-none focus:border-blue-500/55 focus:ring-4 focus:ring-blue-500/15"
          placeholder="Facilities, parking, lighting, and notes"
          @input="update('description', $event.target.value)"
        ></textarea>
      </label>

      <label class="mt-2.5 flex items-center gap-2 text-[13px] text-slate-900">
        <input :checked="form.is_featured" type="checkbox" class="h-4 w-4" @change="update('is_featured', $event.target.checked)" />
        Featured Turf
      </label>

      <div v-if="imagePreviewUrl" class="mt-3 flex flex-wrap items-center gap-3">
        <img :src="imagePreviewUrl" alt="Turf preview" class="h-[100px] w-[130px] rounded-[10px] border border-white/95 object-cover shadow-glass" />
        <button type="button" class="rounded-[10px] border border-transparent bg-white/80 px-3 py-2 font-semibold text-slate-900 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]" @click="$emit('clear-image')">
          Remove Image
        </button>
      </div>

      <div class="mt-3 flex justify-end gap-2">
        <button type="button" class="rounded-[10px] border border-transparent bg-white/80 px-3.5 py-2.5 font-semibold text-slate-900 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]" @click="$emit('reset')">Reset</button>
        <button type="submit" class="rounded-[10px] border border-transparent bg-[#3361d8] px-3.5 py-2.5 font-semibold text-white shadow-[0_10px_20px_rgba(51,97,216,0.3)] transition duration-200 hover:-translate-y-0.5 hover:bg-[#2f57c2] hover:shadow-[0_14px_20px_rgba(51,97,216,0.28)] disabled:cursor-not-allowed disabled:opacity-60" :disabled="loading">
          {{ loading ? 'Saving...' : 'Save Turf' }}
        </button>
      </div>
    </form>

    <p v-if="message" class="mt-2.5 font-semibold" :class="{ 'text-green-700': messageType === 'success', 'text-red-700': messageType === 'error', 'text-blue-700': messageType === 'info' }">{{ message }}</p>
    <p v-if="lastCreatedTurfId" class="mt-2.5 font-semibold text-green-700">
      Turf created successfully. ID: <b>#{{ lastCreatedTurfId }}</b>
    </p>

    <div class="mt-4 border-t border-slate-900/10 pt-3">
      <h4 class="m-0 text-base text-slate-900">Future Buttons Zone</h4>
      <div class="mt-4 grid grid-cols-3 gap-2.5 max-[1200px]:grid-cols-2 max-[900px]:grid-cols-1">
        <button type="button" class="min-h-11 rounded-[10px] border border-dashed border-blue-500/45 bg-white/80 p-[10px_12px] font-semibold text-slate-700 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:border-blue-500/70 hover:text-blue-900 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">+ Add My Turf Action</button>
        <button type="button" class="min-h-11 rounded-[10px] border border-dashed border-blue-500/45 bg-white/80 p-[10px_12px] font-semibold text-slate-700 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:border-blue-500/70 hover:text-blue-900 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">+ Add Bulk Action</button>
        <button type="button" class="min-h-11 rounded-[10px] border border-dashed border-blue-500/45 bg-white/80 p-[10px_12px] font-semibold text-slate-700 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:border-blue-500/70 hover:text-blue-900 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">+ Add Analytics</button>
      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: 'OwnerAddTurfsSection',
  props: {
    form: { type: Object, required: true },
    selectedImageName: { type: String, default: '' },
    imagePreviewUrl: { type: String, default: '' },
    loading: { type: Boolean, default: false },
    message: { type: String, default: '' },
    messageType: { type: String, default: '' },
    lastCreatedTurfId: { type: [Number, null], default: null },
    fileInputKey: { type: Number, default: 0 }
  },
  emits: ['update:form', 'image-change', 'clear-image', 'reset', 'submit'],
  methods: {
    update(field, value) {
      this.$emit('update:form', { ...this.form, [field]: value })
    },
    updateNumber(field, value) {
      this.$emit('update:form', { ...this.form, [field]: Number(value) })
    }
  }
}
</script>
