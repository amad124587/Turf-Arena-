import axios from 'axios'

const API_PROTOCOL = typeof window !== 'undefined' && window.location.protocol.startsWith('http')
  ? window.location.protocol
  : 'http:'
const API_HOST = typeof window !== 'undefined' ? window.location.hostname : '127.0.0.1'
const API_BASE = `${API_PROTOCOL}//${API_HOST}/turfbooking/backend`

export const ADMIN_MENU_ITEMS = [
  { key: 'overview', label: 'Overview' },
  { key: 'verifyTurfs', label: 'Verify Turfs' },
  { key: 'bookingRequests', label: 'Booking Requests' },
  { key: 'refunds', label: 'Refund Requests' },
  { key: 'usersOwners', label: 'Users & Owners' },
  { key: 'analytics', label: 'Analytics' }
]

export function getAdminSessionUser() {
  try {
    const raw = localStorage.getItem('user')
    return raw ? JSON.parse(raw) : {}
  } catch (error) {
    return {}
  }
}

export function createAdminStats() {
  return {
    users_active: 0,
    users_banned: 0,
    owners_verified: 0,
    owners_suspended: 0,
    owners_pending: 0,
    pending_turfs: 0,
    pending_bookings: 0,
    pending_refunds: 0,
    today_revenue: 0
  }
}

export function createAdminAnalytics() {
  return {
    top_rated_turfs: [],
    most_active_users: [],
    revenue_trend: [],
    booking_growth: []
  }
}

export const adminDashboardMethods = {
  switchToUserDashboard() {
    localStorage.setItem('mode', 'user')
    this.$router.push('/dashboard')
  },
  goBack() {
    if (window.history.length > 1) {
      this.$router.back()
      return
    }
    this.$router.push('/dashboard')
  },
  setMessage(type, text) {
    this.messageType = type
    this.message = text
  },
  getApiErrorMessage(error, fallback) {
    const responseData = error?.response?.data
    if (typeof responseData === 'string' && responseData.trim()) {
      const compact = responseData.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()
      return compact || fallback
    }
    if (responseData?.message) return responseData.message
    if (responseData?.status) return responseData.status
    if (error?.message) return error.message
    return fallback
  },
  keyFor(type, id) {
    return `${type}:${id}`
  },
  isActionLoading(type, id) {
    return !!this.actionLoadingMap[this.keyFor(type, id)]
  },
  setActionLoading(type, id, value) {
    this.actionLoadingMap = {
      ...this.actionLoadingMap,
      [this.keyFor(type, id)]: value
    }
  },
  formatMoney(v) {
    const n = Number(v || 0)
    return Number.isFinite(n) ? n.toFixed(2) : '0.00'
  },
  formatDate(v) {
    if (!v) return 'N/A'
    const d = new Date(v)
    if (Number.isNaN(d.getTime())) return v
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
  },
  formatTime(value) {
    if (!value) return 'N/A'
    const txt = String(value)
    const parts = txt.split(':')
    if (parts.length < 2) return txt
    let hour = Number(parts[0])
    const minute = parts[1]
    if (Number.isNaN(hour)) return txt
    const ampm = hour >= 12 ? 'PM' : 'AM'
    hour %= 12
    if (hour === 0) hour = 12
    return `${hour}:${minute} ${ampm}`
  },
  statusTone(value) {
    const status = String(value || '').toLowerCase()
    if (['active', 'verified', 'confirmed', 'approved'].includes(status)) return 'success'
    if (status === 'pending') return 'warning'
    if (['banned', 'suspended', 'cancelled', 'rejected'].includes(status)) return 'danger'
    return 'neutral'
  },
  async loadDashboard() {
    if (!this.adminId) {
      this.setMessage('error', 'Admin session not found. Please login again.')
      return
    }

    this.setMessage('info', 'Loading...')

    try {
      const response = await axios.get(`${API_BASE}/admin_dashboard.php`, {
        params: { admin_id: this.adminId },
        timeout: 10000
      })

      if (!response.data?.success) {
        this.setMessage('error', response.data?.message || 'Failed to load dashboard.')
        return
      }

      this.stats = response.data.stats || this.stats
      this.monitorUsers = Array.isArray(response.data.monitor_users) ? response.data.monitor_users : []
      this.monitorOwners = Array.isArray(response.data.monitor_owners) ? response.data.monitor_owners : []
      this.pendingTurfs = Array.isArray(response.data.pending_turfs) ? response.data.pending_turfs : []
      this.pendingBookings = Array.isArray(response.data.pending_bookings) ? response.data.pending_bookings : []
      this.pendingRefunds = Array.isArray(response.data.pending_refunds) ? response.data.pending_refunds : []
      this.analytics = response.data.analytics || this.analytics

      this.setMessage('success', 'Refreshed')
    } catch (error) {
      this.setMessage('error', this.getApiErrorMessage(error, 'Server connection failed.'))
    }
  },
  async reviewTurf(item, action) {
    this.setActionLoading('turf', item.turf_id, true)
    try {
      const response = await axios.post(`${API_BASE}/admin_turf_action.php`, {
        admin_id: this.adminId,
        turf_id: item.turf_id,
        action,
        note: this.turfNotes[item.turf_id] || ''
      }, {
        headers: { 'Content-Type': 'application/json' },
        timeout: 10000
      })

      if (response.data?.success) {
        this.turfNotes = { ...this.turfNotes, [item.turf_id]: '' }
        this.setMessage('success', `Turf #${item.turf_id} ${action.replace('_', ' ')} successfully.`)
        await this.loadDashboard()
      } else {
        this.setMessage('error', response.data?.message || 'Turf action failed.')
      }
    } catch (error) {
      this.setMessage('error', this.getApiErrorMessage(error, 'Turf action failed.'))
    } finally {
      this.setActionLoading('turf', item.turf_id, false)
    }
  },
  async reviewBooking(item, action) {
    this.setActionLoading('booking', item.booking_id, true)
    try {
      const response = await axios.post(`${API_BASE}/admin_booking_action.php`, {
        admin_id: this.adminId,
        booking_id: item.booking_id,
        action
      }, {
        headers: { 'Content-Type': 'application/json' },
        timeout: 10000
      })

      if (response.data?.success) {
        const plus = Number(response.data.points_awarded || 0)
        this.setMessage('success', `Booking #${item.booking_id} ${response.data.status}.${plus > 0 ? ` +${plus} points` : ''}`)
        await this.loadDashboard()
      } else {
        this.setMessage('error', response.data?.message || 'Booking action failed.')
      }
    } catch (error) {
      this.setMessage('error', this.getApiErrorMessage(error, 'Booking action failed.'))
    } finally {
      this.setActionLoading('booking', item.booking_id, false)
    }
  },
  async reviewRefund(item, action) {
    this.setActionLoading('refund', item.refund_id, true)
    try {
      const response = await axios.post(`${API_BASE}/admin_refund_action.php`, {
        admin_id: this.adminId,
        refund_id: item.refund_id,
        action,
        admin_note: this.refundNotes[item.refund_id] || ''
      }, {
        headers: { 'Content-Type': 'application/json' },
        timeout: 10000
      })

      if (response.data?.success) {
        this.refundNotes = { ...this.refundNotes, [item.refund_id]: '' }
        this.setMessage('success', `Refund #${item.refund_id} updated to ${response.data.status}.`)
        await this.loadDashboard()
      } else {
        this.setMessage('error', response.data?.message || 'Refund action failed.')
      }
    } catch (error) {
      this.setMessage('error', this.getApiErrorMessage(error, 'Refund action failed.'))
    } finally {
      this.setActionLoading('refund', item.refund_id, false)
    }
  },
  async toggleUserStatus(type, id, action) {
    this.setActionLoading(type, id, true)
    try {
      const response = await axios.post(`${API_BASE}/admin_user_action.php`, {
        admin_id: this.adminId,
        target_type: type,
        target_id: id,
        action
      }, {
        headers: { 'Content-Type': 'application/json' },
        timeout: 10000
      })

      if (response.data?.success) {
        this.setMessage('success', `${type} #${id} status updated to ${response.data.status}.`)
        await this.loadDashboard()
      } else {
        this.setMessage('error', response.data?.message || 'Status update failed.')
      }
    } catch (error) {
      this.setMessage('error', this.getApiErrorMessage(error, 'Status update failed.'))
    } finally {
      this.setActionLoading(type, id, false)
    }
  }
}
