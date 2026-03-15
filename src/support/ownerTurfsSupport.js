import axios from 'axios'

const API_PROTOCOL = typeof window !== 'undefined' && window.location.protocol.startsWith('http')
  ? window.location.protocol
  : 'http:'
const API_HOST = typeof window !== 'undefined' ? window.location.hostname : '127.0.0.1'
const OWNER_TURFS_ENDPOINT = `${API_PROTOCOL}//${API_HOST}/turfbooking/backend/owner_my_turfs.php`

export function formatOwnerTurfMoney(value) {
  const amount = Number(value || 0)
  return Number.isFinite(amount) ? amount.toFixed(2) : '0.00'
}

export function formatOwnerTurfDate(value) {
  if (!value) return 'Unknown'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

export function getOwnerTurfStatusTone(status) {
  const value = String(status || '').toLowerCase()
  if (value === 'active') return 'bg-emerald-100 text-emerald-800'
  if (value === 'inactive') return 'bg-slate-200 text-slate-700'
  if (value === 'pending') return 'bg-amber-100 text-amber-800'
  if (value === 'rejected') return 'bg-rose-100 text-rose-800'
  return 'bg-slate-200 text-slate-700'
}

export function buildOwnerTurfSummary(summary) {
  return [
    { label: 'Total Turfs', value: summary.total || 0 },
    { label: 'Active', value: summary.active || 0 },
    { label: 'Inactive', value: summary.inactive || 0 },
    { label: 'Pending', value: summary.pending || 0 }
  ]
}

export async function fetchOwnerTurfs(ownerId) {
  const response = await axios.get(OWNER_TURFS_ENDPOINT, {
    params: { owner_id: ownerId },
    timeout: 12000
  })
  return response.data
}

export async function toggleOwnerTurfStatus(ownerId, turfId, status) {
  const response = await axios.post(OWNER_TURFS_ENDPOINT, {
    owner_id: ownerId,
    turf_id: turfId,
    status
  }, {
    timeout: 12000
  })
  return response.data
}
