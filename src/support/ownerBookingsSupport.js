import axios from 'axios'

const API_PROTOCOL = typeof window !== 'undefined' && window.location.protocol.startsWith('http')
  ? window.location.protocol
  : 'http:'
const API_HOST = typeof window !== 'undefined' ? window.location.hostname : '127.0.0.1'
const OWNER_BOOKING_CLIENTS_ENDPOINT = `${API_PROTOCOL}//${API_HOST}/turfbooking/backend/owner_booking_clients.php`

export function formatOwnerClientDate(value) {
  if (!value) return 'N/A'
  const date = new Date(`${value}T00:00:00`)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

export function formatOwnerClientTime(value) {
  if (!value) return 'N/A'
  const date = new Date(`1970-01-01T${value}`)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit'
  })
}

export function formatOwnerClientMoney(value) {
  const amount = Number(value || 0)
  return Number.isFinite(amount) ? amount.toFixed(2) : '0.00'
}

export function getOwnerClientStatusTone(status) {
  const value = String(status || '').toLowerCase()
  if (value === 'completed') return 'bg-emerald-100 text-emerald-800'
  if (value === 'cancelled') return 'bg-rose-100 text-rose-700'
  if (value === 'confirmed') return 'bg-sky-100 text-sky-800'
  return 'bg-amber-100 text-amber-800'
}

export function filterOwnerClientBookings(bookings, search) {
  const query = String(search || '').trim().toLowerCase()
  if (!query) return bookings

  return bookings.filter((booking) => {
    const haystack = [
      booking.booking_id,
      booking.turf_name,
      booking.sport_type,
      booking.client?.full_name,
      booking.client?.email,
      booking.client?.phone,
      booking.booking_status
    ].join(' ').toLowerCase()

    return haystack.includes(query)
  })
}

export async function fetchOwnerBookingClients(ownerId) {
  const response = await axios.get(OWNER_BOOKING_CLIENTS_ENDPOINT, {
    params: { owner_id: ownerId },
    timeout: 12000
  })
  return response.data
}
