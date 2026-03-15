import axios from 'axios'

const API_PROTOCOL = typeof window !== 'undefined' && window.location.protocol.startsWith('http')
  ? window.location.protocol
  : 'http:'
const API_HOST = typeof window !== 'undefined' ? window.location.hostname : '127.0.0.1'
const OWNER_PROMO_CODES_ENDPOINT = `${API_PROTOCOL}//${API_HOST}/turfbooking/backend/owner_promo_codes.php`

export function createOwnerPromoForm() {
  return {
    code: '',
    discount_type: 'percent',
    discount_value: '',
    min_booking_amount: '',
    start_date: '',
    end_date: ''
  }
}

export function formatOwnerPromoMoney(value) {
  const amount = Number(value || 0)
  return Number.isFinite(amount) ? amount.toFixed(2) : '0.00'
}

export function formatOwnerPromoDate(value) {
  if (!value) return 'No limit'
  const date = new Date(`${value}T00:00:00`)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

export function buildOwnerPromoSummary(summary) {
  return [
    { label: 'Total Codes', value: summary.total_codes || 0 },
    { label: 'Active Codes', value: summary.active_codes || 0 },
    { label: 'Inactive Codes', value: summary.inactive_codes || 0 }
  ]
}

export async function fetchOwnerPromoCodes(ownerId) {
  const response = await axios.get(OWNER_PROMO_CODES_ENDPOINT, {
    params: { owner_id: ownerId },
    timeout: 12000
  })
  return response.data
}

export async function createOwnerPromoCode(ownerId, form) {
  const response = await axios.post(OWNER_PROMO_CODES_ENDPOINT, {
    owner_id: ownerId,
    action: 'create',
    ...form
  }, {
    timeout: 12000
  })
  return response.data
}

export async function toggleOwnerPromoCode(ownerId, promoId, isActive) {
  const response = await axios.post(OWNER_PROMO_CODES_ENDPOINT, {
    owner_id: ownerId,
    action: 'toggle',
    promo_id: promoId,
    is_active: isActive ? 1 : 0
  }, {
    timeout: 12000
  })
  return response.data
}
