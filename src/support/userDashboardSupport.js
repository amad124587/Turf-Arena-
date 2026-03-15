import axios from 'axios'

const API_PROTOCOL = typeof window !== 'undefined' && window.location.protocol.startsWith('http')
  ? window.location.protocol
  : 'http:'
const API_HOST = typeof window !== 'undefined' ? window.location.hostname : '127.0.0.1'
const API_BASE = `${API_PROTOCOL}//${API_HOST}/turfbooking/backend`

export function getUserSession() {
  try {
    const raw = localStorage.getItem('user')
    return raw ? JSON.parse(raw) : null
  } catch (error) {
    return null
  }
}

export function createUserDashboardStats() {
  return { total: 0, upcoming: 0, cancelled: 0 }
}

export function createUserPointsBreakdown() {
  return { booking: 0, review: 0, resell: 0, referral: 0 }
}

function getScrollableElement(refValue) {
  if (!refValue) return null
  if (typeof refValue.scrollIntoView === 'function') return refValue
  if (refValue.$el && typeof refValue.$el.scrollIntoView === 'function') return refValue.$el
  return null
}

export const userDashboardMethods = {
  safeNavigate(path) {
    const resolved = this.$router.resolve(path)
    if (resolved.matched && resolved.matched.length > 0) {
      this.$router.push(path)
    }
  },
  switchToOwnerDashboard() {
    if (!this.isOwner) return
    localStorage.setItem('mode', 'owner')
    this.$router.push('/owner-dashboard')
  },
  switchToAdminDashboard() {
    if (!this.isAdmin) return
    localStorage.setItem('mode', 'admin')
    this.$router.push('/admin-dashboard')
  },
  goToBookings() {
    this.safeNavigate('/bookings')
  },
  goToTransactions() {
    this.safeNavigate('/transactions')
  },
  goToTurfs() {
    this.safeNavigate('/browse')
  },
  scrollToUpcoming() {
    getScrollableElement(this.$refs.upcomingCard)?.scrollIntoView({ behavior: 'smooth', block: 'center' })
  },
  openReview() {
    if (!this.pendingReview) return
    this.showReviewModal = true
    this.reviewMsg = ''
  },
  closeReview() {
    this.showReviewModal = false
  },
  statusClass(status) {
    return String(status || 'pending').toLowerCase()
  },
  statusBadgeTone(status) {
    const value = this.statusClass(status)
    if (value === 'confirmed') return 'success'
    if (value === 'pending') return 'warning'
    if (value === 'cancelled') return 'danger'
    return 'info'
  },
  formatDate(value) {
    if (!value) return 'N/A'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return value
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
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
  formatDateTime(value) {
    if (!value) return 'Unknown time'
    const date = new Date(String(value).replace(' ', 'T'))
    if (Number.isNaN(date.getTime())) return value
    return date.toLocaleString('en-US', {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  },
  formatMoney(value) {
    const amount = Number(value || 0)
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00'
  },
  async loadDashboard() {
    const response = await axios.get(`${API_BASE}/user_dashboard.php?user_id=${this.userId}`)
    if (!response.data?.success) return
    this.stats = response.data.stats || this.stats
    this.points = Number(response.data.points || 0)
    this.pointsBreakdown = response.data.points_breakdown || this.pointsBreakdown
    this.nextBooking = response.data.next_booking || null
    this.activities = Array.isArray(response.data.activities) ? response.data.activities : []
  },
  async loadPendingReview() {
    const response = await axios.get(`${API_BASE}/review_pending.php?user_id=${this.userId}`)
    if (response.data?.success) {
      this.pendingReview = response.data.pending
    }
  },
  async loadAll() {
    try {
      await Promise.all([this.loadDashboard(), this.loadPendingReview()])
    } catch (error) {
      console.error(error)
    }
  },
  async cancelUpcomingBooking() {
    if (!this.nextBooking?.booking_id || this.cancelUpcomingLoading) return

    this.cancelUpcomingLoading = true
    this.upcomingActionMsg = ''

    try {
      const response = await axios.post(`${API_BASE}/cancel_booking.php`, {
        user_id: Number(this.userId || 0),
        booking_id: Number(this.nextBooking.booking_id)
      }, {
        headers: { 'Content-Type': 'application/json' },
        timeout: 10000
      })

      if (response.data?.success) {
        const refund = this.formatMoney(response.data.refund_amount)
        this.upcomingActionMsg = `Cancellation request sent. After admin approval: Refund Tk ${refund} (80%)`
        await this.loadAll()
      } else {
        this.upcomingActionMsg = response.data?.message || 'Could not cancel booking.'
      }
    } catch (error) {
      this.upcomingActionMsg = error.response?.data?.message || 'Cancellation failed.'
    } finally {
      this.cancelUpcomingLoading = false
    }
  },
  async submitReview() {
    if (!this.pendingReview) return
    if (this.rating < 1) {
      this.reviewMsg = 'Please select a rating (1-5).'
      return
    }

    this.reviewLoading = true
    this.reviewMsg = ''

    const payload = new URLSearchParams()
    payload.append('user_id', this.userId)
    payload.append('booking_id', this.pendingReview.booking_id)
    payload.append('turf_id', this.pendingReview.turf_id)
    payload.append('rating', this.rating)
    payload.append('comment', this.comment)

    try {
      const response = await axios.post(`${API_BASE}/review_submit.php`, payload, {
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
      })

      this.reviewMsg = response.data?.message || 'Done'
      if (response.data?.success) {
        this.pendingReview = null
        this.rating = 0
        this.comment = ''
        this.showReviewModal = false
        await this.loadAll()
      }
    } catch (error) {
      console.error(error)
      this.reviewMsg = 'Review submit failed.'
    }

    this.reviewLoading = false
  },
  logout() {
    localStorage.clear()
    this.$router.push('/login')
  }
}



