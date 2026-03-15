import axios from 'axios'

const API_PROTOCOL = typeof window !== 'undefined' && window.location.protocol.startsWith('http')
  ? window.location.protocol
  : 'http:'
const API_HOST = typeof window !== 'undefined' ? window.location.hostname : '127.0.0.1'
const ADD_TURF_ENDPOINT = `${API_PROTOCOL}//${API_HOST}/turfbooking/backend/add_turf.php`
const OWNER_FINANCE_ENDPOINT = `${API_PROTOCOL}//${API_HOST}/turfbooking/backend/owner_finance.php`

export const OWNER_MENU_ITEMS = [
  { key: 'dashboard', label: 'Dashboard' },
  { key: 'myTurfs', label: 'Add Turfs' },
  { key: 'ownerTurfs', label: 'My Turfs' },
  { key: 'bookings', label: 'Bookings' },
  { key: 'revenue', label: 'Revenue' },
  { key: 'slotControl', label: 'Slot Control' },
  { key: 'promoCodes', label: 'Promo Codes' },
  { key: 'reviews', label: 'Reviews' }
]

export function getOwnerSessionUser() {
  try {
    const raw = localStorage.getItem('user')
    return raw ? JSON.parse(raw) : {}
  } catch (error) {
    return {}
  }
}

export function createOwnerFinanceSummary() {
  return {
    wallet_balance: 0,
    total_cancellation_earnings: 0,
    pending_refund_requests: 0,
    cancelled_bookings: 0,
    active_turfs: 0
  }
}

export function createOwnerForm() {
  return {
    turf_name: '',
    sport_type: 'football',
    address: '',
    area: '',
    city: '',
    location: '',
    latitude: '',
    longitude: '',
    price_per_hour: '',
    description: '',
    is_featured: false,
    status: 'pending',
    cancel_before_hours: 24,
    refund_percent: 80
  }
}

function toPositiveNumber(value) {
  const n = Number(value)
  return Number.isFinite(n) && n > 0 ? n : null
}

export const ownerDashboardMethods = {
  formatMoney(value) {
    const n = Number(value || 0)
    return Number.isFinite(n) ? n.toFixed(2) : '0.00'
  },
  async loadOwnerFinance() {
    if (!this.ownerIdFromSession) return
    this.financeLoading = true
    try {
      const response = await axios.get(OWNER_FINANCE_ENDPOINT, {
        params: { owner_id: this.ownerIdFromSession },
        timeout: 10000
      })
      if (response.data?.success) {
        this.ownerFinance = { ...this.ownerFinance, ...(response.data.summary || {}) }
        this.ownerTransactions = Array.isArray(response.data.transactions) ? response.data.transactions : []
      }
    } catch (error) {
      // keep old stats if request fails
    } finally {
      this.financeLoading = false
    }
  },
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
  resetForm() {
    this.form = createOwnerForm()
    this.message = ''
    this.messageType = ''
    this.lastCreatedTurfId = null
    this.clearSelectedImage()
  },
  buildPayload() {
    const payload = new FormData()
    payload.append('owner_id', String(this.ownerIdFromSession || 0))
    payload.append('owner_email', this.ownerEmail || '')
    payload.append('turf_name', this.form.turf_name)
    payload.append('sport_type', this.form.sport_type)
    payload.append('address', this.form.address)
    payload.append('area', this.form.area)
    payload.append('city', this.form.city)
    payload.append('location', this.form.location)
    payload.append('latitude', this.form.latitude)
    payload.append('longitude', this.form.longitude)
    payload.append('price_per_hour', String(this.form.price_per_hour))
    payload.append('description', this.form.description)
    payload.append('is_featured', this.form.is_featured ? '1' : '0')
    payload.append('status', this.form.status)
    payload.append('cancel_before_hours', String(this.form.cancel_before_hours))
    payload.append('refund_percent', String(this.form.refund_percent))
    if (this.selectedImageFile) {
      payload.append('turf_image', this.selectedImageFile)
    }
    return payload
  },
  handleImageChange(event) {
    const files = event?.target?.files
    const file = files && files[0] ? files[0] : null

    if (!file) {
      this.clearSelectedImage()
      return
    }

    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp']
    if (!allowedTypes.includes(file.type)) {
      this.messageType = 'error'
      this.message = 'Only JPG, PNG, and WEBP images are allowed.'
      this.clearSelectedImage()
      return
    }

    const maxSize = 5 * 1024 * 1024
    if (file.size > maxSize) {
      this.messageType = 'error'
      this.message = 'Image size must be 5MB or less.'
      this.clearSelectedImage()
      return
    }

    if (this.imagePreviewUrl) {
      URL.revokeObjectURL(this.imagePreviewUrl)
    }

    this.selectedImageFile = file
    this.selectedImageName = file.name
    this.imagePreviewUrl = URL.createObjectURL(file)
  },
  clearSelectedImage() {
    if (this.imagePreviewUrl) {
      URL.revokeObjectURL(this.imagePreviewUrl)
    }
    this.imagePreviewUrl = ''
    this.selectedImageFile = null
    this.selectedImageName = ''
    this.fileInputKey += 1
  },
  validateTurfForm() {
    if (!this.form.turf_name.trim()) {
      return 'Turf name is required.'
    }

    if (!this.form.address.trim()) {
      return 'Address is required.'
    }

    if (toPositiveNumber(this.form.price_per_hour) === null) {
      return 'Price per hour must be a positive number.'
    }

    if (!this.selectedImageFile) {
      return 'Please choose a turf image (JPG, PNG, or WEBP) before saving.'
    }

    return ''
  },
  async submitTurf() {
    if (this.loading) return
    const validationError = this.validateTurfForm()
    if (validationError) {
      this.messageType = 'error'
      this.message = validationError
      return
    }

    this.loading = true
    this.messageType = 'info'
    this.message = 'Saving turf...'
    this.lastCreatedTurfId = null

    try {
      const response = await axios.post(ADD_TURF_ENDPOINT, this.buildPayload(), {
        timeout: 12000
      })

      if (response.data?.success) {
        this.messageType = 'success'
        this.message = response.data.status || 'Turf added successfully.'
        this.lastCreatedTurfId = response.data.turf_id || null
        await this.loadOwnerFinance()
        this.form = {
          ...createOwnerForm(),
          city: this.form.city,
          sport_type: this.form.sport_type
        }
        this.clearSelectedImage()
        return
      }

      this.messageType = 'error'
      this.message = response.data?.status || 'Failed to add turf.'
    } catch (error) {
      this.messageType = 'error'
      if (error.response?.data?.status) {
        this.message = error.response.data.status
      } else if (error.code === 'ECONNABORTED') {
        this.message = 'Server timeout. Please try again.'
      } else {
        this.message = 'Server connection failed.'
      }
    } finally {
      this.loading = false
    }
  }
}
