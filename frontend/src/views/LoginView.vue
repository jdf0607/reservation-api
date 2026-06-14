<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../composables/useApi'

const { api } = useApi()
const router = useRouter()

const email = ref('admin@misterplan.com')
const password = ref('password')
const error = ref('')
const loading = ref(false)

async function login() {
  error.value = ''
  loading.value = true

  try {
    const { data } = await api.post('/login', {
      email: email.value,
      password: password.value,
    })

    // Guardamos el token para las siguientes peticiones
    localStorage.setItem('token', data.token)
    router.push({ name: 'reservations' })
  } catch (e) {
    error.value = e.response?.data?.message || 'Error al iniciar sesión.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-sm">
      <h1 class="text-2xl font-bold mb-6 text-center text-gray-800">
        Reservas MisterPlan
      </h1>

      <form @submit.prevent="login" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input
            v-model="email"
            type="email"
            required
            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
          <input
            v-model="password"
            type="password"
            required
            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <p v-if="error" class="text-red-600 text-sm">{{ error }}</p>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 disabled:opacity-50 transition"
        >
          {{ loading ? 'Entrando...' : 'Entrar' }}
        </button>
      </form>
    </div>
  </div>
</template>