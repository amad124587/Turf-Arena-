import axios from 'axios'

const API_PROTOCOL = typeof window !== 'undefined' && window.location.protocol.startsWith('http')
  ? window.location.protocol
  : 'http:'
const API_HOST = typeof window !== 'undefined' ? window.location.hostname : '127.0.0.1'
const API_BASE = `${API_PROTOCOL}//${API_HOST}/turfbooking/backend`
const VALIDATE_PROMO_ENDPOINT = `${API_BASE}/validate_promo_code.php`

function toMinutes(timeText) {
  const value = String(timeText || '')
  const parts = value.split(':')
  if (parts.length < 2) return 0
  const h = Number(parts[0] || 0)
  const m = Number(parts[1] || 0)
  return (h * 60) + m
}

function getDhakaDateParts(date = new Date()) {
  const parts = new Intl.DateTimeFormat('en-US', {
    timeZone: 'Asia/Dhaka',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  }).formatToParts(date)

  const map = {}
  parts.forEach((part) => {
    map[part.type] = part.value
  })

  return {
    year: map.year || '1970',
    month: map.month || '01',
    day: map.day || '01'
  }
}

export function getDhakaTodayDate() {
  const d = getDhakaDateParts()
  return `${d.year}-${d.month}-${d.day}`
}

function getDhakaCurrentTime() {
  const parts = new Intl.DateTimeFormat('en-US', {
    timeZone: 'Asia/Dhaka',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hourCycle: 'h23'
  }).formatToParts(new Date())

  const map = {}
  parts.forEach((part) => {
    map[part.type] = part.value
  })

  return `${map.hour || '00'}:${map.minute || '00'}:${map.second || '00'}`
}

function buildImageUrl(imageUrl) {
  if (!imageUrl) return ''
  if (String(imageUrl).startsWith('http')) return imageUrl
  return `${API_BASE}/${String(imageUrl).replace(/^\/+/, '')}`
}

export function getAvailableSports(turfs) {
  const sports = new Set()
  turfs.forEach((turf) => {
    if (turf.sport_type) sports.add(String(turf.sport_type))
  })
  return Array.from(sports).sort((a, b) => a.localeCompare(b))
}

export function getFilteredTurfs(turfs, filters) {
  const search = String(filters.searchText || '').toLowerCase()
  const location = String(filters.locationFilter || '').toLowerCase()
  const minPrice = filters.minPrice === '' ? null : Number(filters.minPrice)
  const maxPrice = filters.maxPrice === '' ? null : Number(filters.maxPrice)

  let rows = turfs.filter((turf) => {
    const turfPrice = Number(turf.price_per_hour || 0)
    const matchSearch = !search || String(turf.turf_name || '').toLowerCase().includes(search)
    const matchSport = filters.sportFilter === 'all' || String(turf.sport_type || '') === filters.sportFilter
    const composedLocation = `${turf.city || ''} ${turf.area || ''} ${turf.location || ''} ${turf.address || ''}`.toLowerCase()
    const matchLocation = !location || composedLocation.includes(location)
    const matchMin = minPrice === null || turfPrice >= minPrice
    const matchMax = maxPrice === null || turfPrice <= maxPrice
    return matchSearch && matchSport && matchLocation && matchMin && matchMax
  })

  rows = [...rows]
  if (filters.sortBy === 'price_low') {
    rows.sort((a, b) => Number(a.price_per_hour || 0) - Number(b.price_per_hour || 0))
  } else if (filters.sortBy === 'price_high') {
    rows.sort((a, b) => Number(b.price_per_hour || 0) - Number(a.price_per_hour || 0))
  } else if (filters.sortBy === 'name_az') {
    rows.sort((a, b) => String(a.turf_name || '').localeCompare(String(b.turf_name || '')))
  } else {
    rows.sort((a, b) => String(b.created_at || '').localeCompare(String(a.created_at || '')))
  }

  return rows
}

export const userBrowseTurfsMethods = {
  updateTurfField(turf, field, value) {
    if (!turf || typeof field !== 'string') return
    turf[field] = value
    if (field === 'promo_code') {
      turf.applied_promo = null
    }
  },
  statusTextClass(type) {
    if (type === 'success') return 'text-green-700'
    if (type === 'error') return 'text-red-700'
    return 'text-blue-700'
  },
  normalizeTurfRows(rawTurfs) {
    return rawTurfs.map((turf) => ({
      ...turf,
      image_full_url: buildImageUrl(turf.image_url),
      booking_date: '',
      available_slots: [],
      selected_slot_id: 0,
      promo_code: '',
      applied_promo: null,
      payment_method: 'cash',
      message: '',
      messageType: ''
    }))
  },
  setTurfMessage(turf, text, type = 'info') {
    turf.message = text
    turf.messageType = type
  },
  formatMoney(value) {
    return Number(value || 0).toFixed(2)
  },
  formatTime(timeText) {
    const value = String(timeText || '')
    if (!value) return '--:--'

    const parts = value.split(':')
    if (parts.length < 2) return value.slice(0, 5)

    let hour = Number(parts[0])
    const minute = String(parts[1] || '00').padStart(2, '0')
    if (Number.isNaN(hour)) return value.slice(0, 5)

    if (hour >= 24) hour %= 24
    const ampm = hour >= 12 ? 'PM' : 'AM'
    let displayHour = hour % 12
    if (displayHour === 0) displayHour = 12

    return `${displayHour}:${minute} ${ampm}`
  },
  formatSlotLabel(slot) {
    return `${this.formatTime(slot.start_time)} - ${this.formatTime(slot.end_time)} (BDT)`
  },
  formatDuration(hours) {
    const value = Number(hours || 0)
    if (value <= 0) return '0h'
    return `${value.toFixed(2)}h`
  },
  isValidDateInput(dateText) {
    return /^\d{4}-\d{2}-\d{2}$/.test(String(dateText || ''))
  },
  isPastDate(dateText) {
    if (!this.isValidDateInput(dateText)) return false
    return String(dateText) < this.todayDate
  },
  getDateYear(dateText) {
    if (!this.isValidDateInput(dateText)) return 0
    return Number(String(dateText).slice(0, 4))
  },
  isPastSlotDateTime(slotDate, startTime) {
    if (!slotDate || !startTime) return false
    if (String(slotDate) < this.todayDate) return true
    if (String(slotDate) > this.todayDate) return false
    const nowTime = getDhakaCurrentTime()
    const slotStart = String(startTime).slice(0, 8)
    return slotStart <= nowTime
  },
  getCurrentUserId() {
    try {
      const direct = Number(localStorage.getItem('user_id') || 0)
      if (direct > 0) return direct
      const raw = localStorage.getItem('user')
      if (!raw) return 0
      const parsed = JSON.parse(raw)
      return Number(parsed?.user_id || 0)
    } catch (error) {
      return 0
    }
  },
  getSelectedSlot(turf) {
    if (!turf || !Array.isArray(turf.available_slots)) return null
    return turf.available_slots.find((slot) => Number(slot.slot_id) === Number(turf.selected_slot_id)) || null
  },
  onSlotChange(turf) {
    turf.applied_promo = null
    if (!Number(turf.selected_slot_id || 0)) {
      this.setTurfMessage(turf, 'Please select a slot.', 'info')
      return
    }
    this.setTurfMessage(turf, 'Slot selected. Review and confirm booking.', 'info')
  },
  calculateDurationHours(startTime, endTime) {
    const startMinutes = toMinutes(startTime)
    const endMinutes = toMinutes(endTime)
    if (endMinutes <= startMinutes) return 0
    return (endMinutes - startMinutes) / 60
  },
  getPriceBreakdown(turf) {
    const selected = this.getSelectedSlot(turf)
    if (!selected) return null

    const durationHours = this.calculateDurationHours(selected.start_time, selected.end_time)
    const rate = Number(turf.price_per_hour || 0)

    let subtotal = durationHours > 0 ? durationHours * rate : 0
    if (subtotal <= 0) {
      subtotal = Number(selected.base_price || 0)
    }

    return {
      durationHours,
      subtotal,
      promoCode: turf.applied_promo?.code || '',
      discountAmount: Number(turf.applied_promo?.discount_amount || 0),
      serviceFee: 0,
      total: Math.max(0, Number(turf.applied_promo?.final_total ?? subtotal))
    }
  },
  async loadTurfs() {
    this.loading = true
    this.errorText = ''
    this.statusText = 'Loading turfs...'

    try {
      const response = await axios.get(`${API_BASE}/get_turfs.php`, { timeout: 8000 })
      if (!response.data?.success) {
        this.errorText = response.data?.status || 'Failed to load turfs.'
        this.statusText = ''
        return
      }

      const rows = Array.isArray(response.data.turfs) ? response.data.turfs : []
      this.turfs = this.normalizeTurfRows(rows)
      this.statusText = this.turfs.length ? '' : 'No turfs found.'
    } catch (error) {
      this.errorText = error.response?.data?.status || 'Server connection failed while loading turfs.'
      this.statusText = ''
    } finally {
      this.loading = false
    }
  },
  onDateChange(turf) {
    turf.available_slots = []
    turf.selected_slot_id = 0
    turf.applied_promo = null

    const turfId = Number(turf.turf_id || 0)
    const existingTimer = this.dateInputDebounceMap[turfId]
    if (existingTimer) {
      clearTimeout(existingTimer)
    }

    if (!turf.booking_date) {
      this.setTurfMessage(turf, 'Please pick a date first.', 'info')
      return
    }
    if (!this.isValidDateInput(turf.booking_date)) {
      this.setTurfMessage(turf, '', 'info')
      return
    }

    const year = this.getDateYear(turf.booking_date)
    if (year > 0 && year < 2000) {
      this.setTurfMessage(turf, '', 'info')
      return
    }

    const timer = setTimeout(() => {
      if (this.isPastDate(turf.booking_date)) {
        this.setTurfMessage(turf, 'Select valid date', 'error')
        return
      }
      this.loadTurfSlots(turf)
    }, 350)

    this.dateInputDebounceMap = {
      ...this.dateInputDebounceMap,
      [turfId]: timer
    }
  },
  async loadTurfSlots(turf) {
    if (!turf.booking_date) {
      this.setTurfMessage(turf, 'Please select a date to check slots.', 'error')
      return
    }
    if (!this.isValidDateInput(turf.booking_date)) {
      this.setTurfMessage(turf, '', 'info')
      return
    }
    if (this.isPastDate(turf.booking_date)) {
      this.setTurfMessage(turf, 'Select valid date', 'error')
      return
    }

    this.slotLoadingMap = { ...this.slotLoadingMap, [turf.turf_id]: true }
    turf.available_slots = []
    turf.selected_slot_id = 0
    turf.applied_promo = null
    this.setTurfMessage(turf, 'Checking available slots...', 'info')

    try {
      const response = await axios.get(`${API_BASE}/get_available_slots.php`, {
        timeout: 8000,
        params: { turf_id: turf.turf_id, slot_date: turf.booking_date }
      })

      if (!response.data?.success) {
        this.setTurfMessage(turf, response.data?.status || 'Could not load slots.', 'error')
        return
      }

      const rows = Array.isArray(response.data.slots) ? response.data.slots : []
      turf.available_slots = rows.map((slot) => ({
        slot_id: Number(slot.slot_id || 0),
        slot_date: slot.slot_date,
        start_time: slot.start_time,
        end_time: slot.end_time,
        base_price: Number(slot.base_price || 0)
      }))

      if (!turf.available_slots.length) {
        this.setTurfMessage(turf, 'No available slots for this date.', 'info')
        return
      }

      this.setTurfMessage(turf, '', 'success')
    } catch (error) {
      const message = error.response?.data?.status || 'Server connection failed while checking slots.'
      this.setTurfMessage(turf, message, 'error')
    } finally {
      this.slotLoadingMap = { ...this.slotLoadingMap, [turf.turf_id]: false }
    }
  },
  async applyPromoCode(turf) {
    const selected = this.getSelectedSlot(turf)
    if (!selected) {
      this.setTurfMessage(turf, 'Please select a slot before applying a promo code.', 'error')
      return
    }

    const promoCode = String(turf.promo_code || '').trim().toUpperCase()
    if (!promoCode) {
      turf.applied_promo = null
      this.setTurfMessage(turf, 'Enter a promo code first.', 'error')
      return
    }

    this.promoLoadingMap = { ...this.promoLoadingMap, [turf.turf_id]: true }
    this.setTurfMessage(turf, 'Checking promo code...', 'info')

    try {
      const response = await axios.post(VALIDATE_PROMO_ENDPOINT, {
        slot_id: selected.slot_id,
        promo_code: promoCode
      }, {
        timeout: 8000,
        headers: { 'Content-Type': 'application/json' }
      })

      if (!response.data?.success) {
        turf.applied_promo = null
        this.setTurfMessage(turf, response.data?.message || 'Promo code is not valid.', 'error')
        return
      }

      turf.promo_code = String(response.data?.promo?.code || promoCode)
      turf.applied_promo = response.data?.promo || null
      this.setTurfMessage(turf, response.data?.message || 'Promo code applied successfully.', 'success')
    } catch (error) {
      turf.applied_promo = null
      const message = error.response?.data?.message || 'Server connection failed while applying promo code.'
      this.setTurfMessage(turf, message, 'error')
    } finally {
      this.promoLoadingMap = { ...this.promoLoadingMap, [turf.turf_id]: false }
    }
  },
  async createBooking(userId, slotId, bookedPrice, promoCode = '') {
    const payload = {
      user_id: userId,
      slot_id: slotId,
      booked_price: bookedPrice,
      promo_code: String(promoCode || '').trim().toUpperCase()
    }
    const response = await axios.post(`${API_BASE}/book_slot.php`, payload, {
      timeout: 8000,
      headers: { 'Content-Type': 'application/json' }
    })
    return response.data
  },
  async bookTurf(turf) {
    const userId = this.getCurrentUserId()
    if (!userId) {
      this.$router.push('/login')
      return
    }
    if (this.isPastDate(turf.booking_date)) {
      this.setTurfMessage(turf, 'Select valid date', 'error')
      return
    }

    const selected = this.getSelectedSlot(turf)
    if (!selected) {
      this.setTurfMessage(turf, 'Please select an available slot first.', 'error')
      return
    }
    if (this.isPastSlotDateTime(selected.slot_date || turf.booking_date, selected.start_time)) {
      this.setTurfMessage(turf, 'Past time slot cannot be booked (Bangladesh time).', 'error')
      return
    }

    const breakdown = this.getPriceBreakdown(turf)
    if (!breakdown || breakdown.total <= 0) {
      this.setTurfMessage(turf, 'Invalid booking amount. Please reselect slot.', 'error')
      return
    }

    this.bookingMap = { ...this.bookingMap, [turf.turf_id]: true }
    this.setTurfMessage(turf, 'Sending booking request...', 'info')

    try {
      const bookingResult = await this.createBooking(userId, selected.slot_id, breakdown.total, turf.applied_promo?.code || '')
      if (bookingResult?.success) {
        this.setTurfMessage(turf, bookingResult.message || 'Booking request sent successfully.', 'success')
        turf.available_slots = turf.available_slots.filter((slot) => Number(slot.slot_id) !== Number(selected.slot_id))
        turf.selected_slot_id = 0
        turf.promo_code = ''
        turf.applied_promo = null
        return
      }
      this.setTurfMessage(turf, bookingResult?.message || 'Booking failed.', 'error')
    } catch (error) {
      const message = error.response?.data?.message || error.response?.data?.status || 'Server connection failed.'
      this.setTurfMessage(turf, message, 'error')
    } finally {
      this.bookingMap = { ...this.bookingMap, [turf.turf_id]: false }
    }
  }
}
