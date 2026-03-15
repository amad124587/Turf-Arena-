<script>
import axios from 'axios'

const API_PROTOCOL = typeof window !== 'undefined' && window.location.protocol.startsWith('http')
  ? window.location.protocol
  : 'http:'
const API_HOST = typeof window !== 'undefined' ? window.location.hostname : '127.0.0.1'
const LOGIN_ENDPOINT = `${API_PROTOCOL}//${API_HOST}/turfbooking/backend/login.php`

export default {
  name: 'UserLogin',
  data() {
    return {
      email: '',
      password: '',
      message: '',
      messageType: '',
      loading: false
    }
  },
  mounted() {
    if (!this.email) {
      const savedEmail = localStorage.getItem('last_registered_email')
      if (savedEmail) {
        this.email = savedEmail
      }
    }
  },
  computed: {
    canSubmit() {
      return this.email.includes('@') && this.password.length > 0
    }
  },
  methods: {
    saveUserSession(user) {
      localStorage.setItem('user_id', String(user.user_id || ''))
      localStorage.setItem('user_name', user.full_name || 'User')
      localStorage.setItem('user_email', user.email || this.email)

      if (user.owner_id) {
        localStorage.setItem('owner_id', String(user.owner_id))
      } else {
        localStorage.removeItem('owner_id')
      }

      const resolvedAdminId = Number(user.admin_id || 0) > 0
        ? Number(user.admin_id)
        : (user.role === 'admin' ? Number(user.user_id || 0) : 0)

      if (resolvedAdminId > 0) {
        localStorage.setItem('admin_id', String(resolvedAdminId))
      } else {
        localStorage.removeItem('admin_id')
      }

      localStorage.setItem('user', JSON.stringify(user))
      localStorage.setItem('mode', user.role === 'admin' ? 'admin' : 'user')
    },
    redirectAfterLogin(user) {
      if (user?.role === 'admin') {
        this.$router.push('/admin-dashboard')
        return
      }

      this.$router.push('/dashboard')
    },
    goBack() {
      if (window.history.length > 1) {
        this.$router.back()
        return
      }
      this.$router.push('/')
    },
    async login() {
      if (this.loading) return
      if (!this.canSubmit) {
        this.messageType = 'error'
        this.message = 'Please provide valid email and password.'
        return
      }

      this.loading = true
      this.messageType = 'info'
      this.message = 'Checking credentials...'

      const payload = new URLSearchParams()
      payload.append('email', this.email)
      payload.append('password', this.password)

      try {
        const response = await axios.post(LOGIN_ENDPOINT, payload, {
          timeout: 5000,
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          }
        })

        if (response.data?.success) {
          const user = response.data.user || {}
          this.saveUserSession(user)
          this.messageType = 'success'
          this.message = response.data.status || 'Login successful.'
          this.redirectAfterLogin(user)
          return
        }

        this.messageType = 'error'
        this.message = response.data?.status || 'Invalid email or password.'
      } catch (error) {
        console.error(error)
        this.messageType = 'error'
        if (error.response?.data?.status) {
          this.message = error.response.data.status
        } else {
          this.message = error.code === 'ECONNABORTED'
            ? 'Server response timeout.'
            : 'Server connection failed.'
        }
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<template>
  <div class="relative flex min-h-screen items-center justify-center bg-gray-100 px-4 font-poppins">
    <button
      type="button"
      class="absolute right-4 top-4 rounded-full border border-white/95 bg-white/80 px-4 py-2 font-semibold text-slate-900 backdrop-blur-[14px] shadow-glass transition hover:bg-white"
      @click="goBack"
    >
      Back
    </button>
    <div class="w-full max-w-[350px] rounded-xl bg-white p-[30px] shadow-[0_10px_25px_rgba(0,0,0,0.1)]">
      <h2>Login</h2>

      <form @submit.prevent="login">
        <input
          v-model.trim="email"
          type="email"
          class="my-2 w-full box-border border border-gray-500 px-2.5 py-2.5"
          placeholder="Email"
          autocomplete="email"
        />
        <input
          v-model="password"
          type="password"
          class="my-2 w-full box-border border border-gray-500 px-2.5 py-2.5"
          placeholder="Password"
          autocomplete="current-password"
        />

        <button
          type="submit"
          class="mt-2 w-full rounded-md bg-[#2f428f] px-3 py-2.5 font-bold text-white transition-colors hover:bg-[#5a7ee8] focus-visible:bg-[#5a7ee8] disabled:cursor-not-allowed disabled:opacity-85"
          :disabled="loading || !canSubmit"
        >
          {{ loading ? 'Checking...' : 'Login' }}
        </button>
      </form>

      <p
        v-if="message"
        class="mt-2.5 text-center"
        :class="{
          'text-green-700': messageType === 'success',
          'text-red-700': messageType === 'error',
          'text-blue-800': messageType === 'info'
        }"
      >
        {{ message }}
      </p>
    </div>
  </div>
</template>
