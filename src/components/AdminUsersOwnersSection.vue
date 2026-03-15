<template>
  <section class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
    <div>
      <h3 class="m-0 text-[18px] font-bold text-slate-900">Monitor Users and Turf Owners</h3>
      <p class="mt-2 text-slate-600">Ban or unban in case of violation.</p>
    </div>

    <div class="mt-3 grid grid-cols-2 gap-3 max-[1200px]:grid-cols-1">
      <article class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
        <h4 class="m-0 text-[18px] font-bold text-slate-900">Users</h4>
        <div class="mt-2 overflow-auto">
          <table class="min-w-[420px] w-full border-collapse">
            <thead>
              <tr>
                <th class="border-b border-slate-200/80 px-2 py-2 text-left text-sm font-semibold text-slate-900">User</th>
                <th class="border-b border-slate-200/80 px-2 py-2 text-left text-sm font-semibold text-slate-900">Status</th>
                <th class="border-b border-slate-200/80 px-2 py-2 text-left text-sm font-semibold text-slate-900">Bookings</th>
                <th class="border-b border-slate-200/80 px-2 py-2 text-left text-sm font-semibold text-slate-900">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="u in monitorUsers" :key="u.user_id">
                <td class="border-b border-slate-200/70 px-2 py-2 text-sm text-slate-700">
                  <div class="font-semibold text-slate-900">{{ u.full_name }}</div>
                  <small>{{ u.email }}</small>
                </td>
                <td class="border-b border-slate-200/70 px-2 py-2"><StatusBadge :label="u.status" :tone="statusTone(u.status)" :bordered="false" /></td>
                <td class="border-b border-slate-200/70 px-2 py-2 text-sm text-slate-700">{{ u.total_bookings }}</td>
                <td class="border-b border-slate-200/70 px-2 py-2">
                  <button
                    v-if="u.status !== 'banned'"
                    type="button"
                    class="rounded-[10px] border border-transparent bg-white/80 px-3 py-1.5 font-semibold text-[#991b1b] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)] disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="isActionLoading('user', u.user_id)"
                    @click="$emit('toggle', { type: 'user', id: u.user_id, action: 'ban' })"
                  >
                    Ban
                  </button>
                  <button
                    v-else
                    type="button"
                    class="rounded-[10px] border border-transparent bg-white/80 px-3 py-1.5 font-semibold text-slate-900 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)] disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="isActionLoading('user', u.user_id)"
                    @click="$emit('toggle', { type: 'user', id: u.user_id, action: 'unban' })"
                  >
                    Unban
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="rounded-[14px] border border-transparent bg-white/80 p-3.5 backdrop-blur-[14px] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)]">
        <h4 class="m-0 text-[18px] font-bold text-slate-900">Turf Owners</h4>
        <div class="mt-2 overflow-auto">
          <table class="min-w-[420px] w-full border-collapse">
            <thead>
              <tr>
                <th class="border-b border-slate-200/80 px-2 py-2 text-left text-sm font-semibold text-slate-900">Owner</th>
                <th class="border-b border-slate-200/80 px-2 py-2 text-left text-sm font-semibold text-slate-900">Status</th>
                <th class="border-b border-slate-200/80 px-2 py-2 text-left text-sm font-semibold text-slate-900">Turfs</th>
                <th class="border-b border-slate-200/80 px-2 py-2 text-left text-sm font-semibold text-slate-900">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="o in monitorOwners" :key="o.owner_id">
                <td class="border-b border-slate-200/70 px-2 py-2 text-sm text-slate-700">
                  <div class="font-semibold text-slate-900">{{ o.owner_name }}</div>
                  <small>{{ o.email }}</small>
                </td>
                <td class="border-b border-slate-200/70 px-2 py-2"><StatusBadge :label="o.status" :tone="statusTone(o.status)" :bordered="false" /></td>
                <td class="border-b border-slate-200/70 px-2 py-2 text-sm text-slate-700">{{ o.total_turfs }}</td>
                <td class="border-b border-slate-200/70 px-2 py-2">
                  <button
                    v-if="o.status !== 'suspended'"
                    type="button"
                    class="rounded-[10px] border border-transparent bg-white/80 px-3 py-1.5 font-semibold text-[#991b1b] shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)] disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="isActionLoading('owner', o.owner_id)"
                    @click="$emit('toggle', { type: 'owner', id: o.owner_id, action: 'ban' })"
                  >
                    Suspend
                  </button>
                  <button
                    v-else
                    type="button"
                    class="rounded-[10px] border border-transparent bg-white/80 px-3 py-1.5 font-semibold text-slate-900 shadow-glass transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_14px_20px_rgba(20,32,89,0.18)] disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="isActionLoading('owner', o.owner_id)"
                    @click="$emit('toggle', { type: 'owner', id: o.owner_id, action: 'unban' })"
                  >
                    Activate
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </section>
</template>

<script>
import StatusBadge from './StatusBadge.vue'

export default {
  name: 'AdminUsersOwnersSection',
  components: {
    StatusBadge
  },
  props: {
    monitorUsers: { type: Array, default: () => [] },
    monitorOwners: { type: Array, default: () => [] },
    statusTone: { type: Function, required: true },
    isActionLoading: { type: Function, required: true }
  },
  emits: ['toggle']
}
</script>
