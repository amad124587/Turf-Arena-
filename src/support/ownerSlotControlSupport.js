import axios from 'axios'

const API_PROTOCOL = typeof window !== 'undefined' && window.location.protocol.startsWith('http')
  ? window.location.protocol
  : 'http:'
const API_HOST = typeof window !== 'undefined' ? window.location.hostname : '127.0.0.1'
const OWNER_SLOT_CONTROL_ENDPOINT = `${API_PROTOCOL}//${API_HOST}/turfbooking/backend/owner_slot_control.php`

export function createOwnerSlotControlState() {
  return {
    turfId: '',
    slotDate: new Date().toISOString().slice(0, 10)
  }
}

export function formatOwnerSlotDate(value) {
  if (!value) return 'N/A'
  const date = new Date(`${value}T00:00:00`)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

export function formatOwnerSlotTime(value) {
  if (!value) return 'N/A'
  const date = new Date(`1970-01-01T${value}`)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit'
  })
}

export function formatOwnerSlotMoney(value) {
  const amount = Number(value || 0)
  return Number.isFinite(amount) ? amount.toFixed(2) : '0.00'
}

export function getOwnerSlotStatusTone(slot) {
  if (slot.is_booked) return 'bg-rose-100 text-rose-700'
  if (slot.is_enabled) return 'bg-emerald-100 text-emerald-800'
  return 'bg-slate-200 text-slate-700'
}

export async function fetchOwnerSlotControl(ownerId, turfId, slotDate) {
  const response = await axios.get(OWNER_SLOT_CONTROL_ENDPOINT, {
    params: {
      owner_id: ownerId,
      turf_id: turfId || undefined,
      slot_date: slotDate
    },
    timeout: 12000
  })
  return response.data
}

export async function updateOwnerSlotAvailability(ownerId, turfId, slotDate, slotIds, isEnabled) {
  const response = await axios.post(OWNER_SLOT_CONTROL_ENDPOINT, {
    owner_id: ownerId,
    turf_id: turfId,
    slot_date: slotDate,
    slot_ids: slotIds,
    is_enabled: isEnabled
  }, {
    timeout: 12000
  })
  return response.data
}
