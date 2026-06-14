<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../composables/useApi'

const { api } = useApi()
const router = useRouter()

const reservations = ref([])
const loading = ref(false)
const error = ref('')

function formatDate(value) {
  if (!value) return ''
  return new Date(value).toLocaleDateString('es-ES')
}

// Filtros
const filters = ref({ status: '', from: '', to: '', guest: '' })

async function fetchReservations() {
  loading.value = true
  error.value = ''
  try {
    // Enviamos solo los filtros que tienen valor
    const params = {}
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.from) params.from = filters.value.from
    if (filters.value.to) params.to = filters.value.to
    if (filters.value.guest) params.guest = filters.value.guest

    const { data } = await api.get('/reservations', { params })
    reservations.value = data.data
  } catch (e) {
    error.value = 'No se pudieron cargar las reservas.'
  } finally {
    loading.value = false
  }
}

function resetFilters() {
  filters.value = { status: '', from: '', to: '', guest: '' }
  fetchReservations()
}

function logout() {
  localStorage.removeItem('token')
  router.push({ name: 'login' })
}

function statusColor(status) {
  return {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
  }[status] || 'bg-gray-100 text-gray-800'
}

onMounted(fetchReservations)
</script>

<template>
  <div class="min-h-screen bg-gray-100 p-6">
    <div class="max-w-5xl mx-auto">
      <div class="flex items-center justify-between px-6 py-4 mb-6">
  <router-link
    :to="{ name: 'reservation-create' }"
    class="bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-md hover:bg-blue-700 transition"
  >
    + Nueva reserva
  </router-link>

  <h1 class="text-2xl font-bold text-gray-800">
    Reservas
  </h1>

  <button
    @click="logout"
    class="text-sm text-gray-500 hover:text-red-600 transition"
  >
    Cerrar sesión
  </button>
</div>

      <!-- Filtros -->
      <div class="bg-white p-4 rounded-lg shadow mb-6 grid grid-cols-1 md:grid-cols-5 gap-3">
        <select v-model="filters.status" class="border border-gray-300 rounded-md px-3 py-2">
          <option value="">Todos los estados</option>
          <option value="pending">Pendiente</option>
          <option value="confirmed">Confirmada</option>
          <option value="cancelled">Cancelada</option>
        </select>
        <input v-model="filters.from" type="date" class="border border-gray-300 rounded-md px-3 py-2" />
        <input v-model="filters.to" type="date" class="border border-gray-300 rounded-md px-3 py-2" />
        <input v-model="filters.guest" type="text" placeholder="Buscar huésped..."
          class="border border-gray-300 rounded-md px-3 py-2" />
        <div class="flex gap-2">
          <button @click="fetchReservations"
            class="flex-1 bg-blue-600 text-white rounded-md hover:bg-blue-700">Filtrar</button>
          <button @click="resetFilters"
            class="px-3 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Limpiar</button>
        </div>
      </div>

      <!-- Estados -->
      <p v-if="loading" class="text-center text-gray-500 py-8">Cargando...</p>
      <p v-else-if="error" class="text-center text-red-600 py-8">{{ error }}</p>
      <p v-else-if="reservations.length === 0" class="text-center text-gray-500 py-8">
        No hay reservas con esos filtros.
      </p>

      <!-- Tabla -->
      <div v-else class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-left text-gray-600">
            <tr>
              <th class="px-4 py-3">Huésped</th>
              <th class="px-4 py-3">Propiedad</th>
              <th class="px-4 py-3">Entrada</th>
              <th class="px-4 py-3">Salida</th>
              <th class="px-4 py-3">Estado</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in reservations" :key="r.id" class="border-t border-gray-100 hover:bg-gray-50">
              <td class="px-4 py-3">{{ r.guest_name }}</td>
              <td class="px-4 py-3">{{ r.property_name }}</td>
              <td class="px-4 py-3">{{ formatDate(r.check_in) }}</td>
              <td class="px-4 py-3">{{ formatDate(r.check_out) }}</td>
              <td class="px-4 py-3">
                <span :class="statusColor(r.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                  {{ r.status }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <router-link :to="{ name: 'reservation-detail', params: { id: r.id } }"
                  class="text-blue-600 hover:underline">Ver</router-link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
