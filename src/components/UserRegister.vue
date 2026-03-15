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
      <h2>Create Account</h2>

      <form @submit.prevent="register">
        <input
          v-model.trim="full_name"
          class="my-2 w-full box-border border border-gray-500 px-2.5 py-2.5"
          placeholder="Full Name"
          autocomplete="name"
        />
        <input
          v-model.trim="email"
          type="email"
          class="my-2 w-full box-border border border-gray-500 px-2.5 py-2.5"
          placeholder="Email"
          autocomplete="email"
        />
        <input
          v-model.trim="phone"
          type="tel"
          class="my-2 w-full box-border border border-gray-500 px-2.5 py-2.5"
          placeholder="Phone"
          autocomplete="tel"
        />

        <div class="relative">
          <input
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            class="my-2 w-full box-border border border-gray-500 px-2.5 py-2.5 pr-12"
            placeholder="Password"
            autocomplete="new-password"
          />
          <span
            class="absolute right-3 top-1/2 -translate-y-1/2 cursor-pointer select-none text-xs font-semibold text-gray-800"
            @click="showPassword = !showPassword"
          >
            {{ showPassword ? 'Hide' : 'Show' }}
          </span>
        </div>

        <button
          type="submit"
          class="mt-2 inline-flex w-full items-center justify-center rounded-md bg-[#2f428f] px-3 py-2.5 font-bold text-white transition-colors hover:bg-[#5a7ee8] focus-visible:bg-[#5a7ee8] disabled:cursor-wait disabled:opacity-85"
          :disabled="loading"
        >
          <span
            v-if="loading"
            class="mr-2 inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"
          ></span>
          {{ loading ? 'Registering...' : 'Register' }}
        </button>
      </form>

      <p class="mt-4 text-center">
        Already have an account?
        <button class="ml-1 w-auto border-none bg-transparent p-0 font-bold text-blue-900 underline" @click="goToLogin">
          Login
        </button>
      </p>

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

<script>
import axios from 'axios'

const API_PROTOCOL = typeof window !== 'undefined' && window.location.protocol.startsWith('http')
  ? window.location.protocol
  : 'http:'
const API_HOST = typeof window !== 'undefined' ? window.location.hostname : '127.0.0.1'
const REGISTER_ENDPOINT = `${API_PROTOCOL}//${API_HOST}/turfbooking/backend/register.php`

export default {
  name: 'UserRegister',
  data() {
    return {
      full_name: '',
      email: '',
      phone: '',
      password: '',
      message: '',
      messageType: '',
      loading: false,
      showPassword: false
    }
  },
  computed: {
    canSubmit() {
      return (
        this.full_name.length > 1 &&
        this.email.includes('@') &&
        this.phone.length >= 6 &&
        this.password.length >= 6
      )
    }
  },
  methods: {
    async register() {
      if (this.loading) return

      if (!this.canSubmit) {
        this.messageType = 'error'
        this.message = 'Please fill all fields correctly.'
        return
      }

      this.loading = true
      this.messageType = 'info'
      this.message = 'Processing...'

      try {
        const payload = new URLSearchParams()
        payload.append('full_name', this.full_name)
        payload.append('email', this.email)
        payload.append('phone', this.phone)
        payload.append('password', this.password)

        const response = await axios.post(REGISTER_ENDPOINT, payload, {
          timeout: 4000,
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        })

        this.messageType = response.data?.success === false ? 'error' : 'success'
        this.message = response.data?.status || 'Registration completed.'

        if (response.data?.success !== false) {
          localStorage.setItem('last_registered_email', this.email)

          this.full_name = ''
          this.email = ''
          this.phone = ''
          this.password = ''

          setTimeout(() => {
            this.$router.push('/login')
          }, 1500)
        }
      } catch (error) {
        console.error(error)
        this.messageType = 'error'
        if (error.response?.data?.status) {
          this.message = error.response.data.status
        } else {
          this.message =
            error.code === 'ECONNABORTED'
              ? 'Server response timeout.'
              : 'Server connection failed.'
        }
      }

      this.loading = false
    },

    goToLogin() {
      this.$router.push('/login')
    },
    goBack() {
      if (window.history.length > 1) {
        this.$router.back()
        return
      }
      this.$router.push('/login')
    }
  }
}
</script>
