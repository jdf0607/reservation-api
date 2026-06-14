<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '../composables/useApi'

const { api } = useApi()
const route = useRoute()
const router = useRouter()

const reservation = ref(null)
const loading = ref(false)
const error = ref('')

function formatDate(value) {
  if (!value) return ''
  return new Date(value).toLocaleDateString('es-ES')
}

async function fetchReservation() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get(`/reservations/${route.params.id}`)
    reservation.value = data.data
  } catch (e) {
    error.value = 'No se pudo cargar la reserva.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchReservation)
</script>

<template>
  <div class="min-h-screen bg-gray-100 p-6">
    <div class="max-w-3xl mx-auto">
      <button @click="router.push({ name: 'reservations' })"
        class="text-blue-600 hover:underline mb-4">← Volver al listado</button>

      <p v-if="loading" class="text-center text-gray-500 py-8">Cargando...</p>
      <p v-else-if="error" class="text-center text-red-600 py-8">{{ error }}</p>

      <div v-else-if="reservation" class="space-y-6">
        <!-- Datos de la reserva -->
        <div class="bg-white rounded-lg shadow p-6">
          <h1 class="text-2xl font-bold text-gray-800 mb-4">{{ reservation.guest_name }}</h1>
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-500">Email:</span> {{ reservation.guest_email }}</div>
            <div><span class="text-gray-500">Propiedad:</span> {{ reservation.property_name }}</div>
            <div><span class="text-gray-500">Entrada:</span> {{ formatDate(reservation.check_in) }}</div>
            <div><span class="text-gray-500">Salida:</span> {{ formatDate(reservation.check_out) }}</div>
            <div><span class="text-gray-500">Importe:</span> {{ reservation.amount }} €</div>
            <div><span class="text-gray-500">Estado:</span> {{ reservation.status }}</div>
          </div>
          <p v-if="reservation.notes" class="mt-4 text-sm text-gray-600">
            <span class="text-gray-500">Notas:</span> {{ reservation.notes }}
          </p>
        </div>

        <!-- Timeline de eventos -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-semibold text-gray-800 mb-4">Historial</h2>
          <ol class="relative border-l border-gray-200 ml-3">
            <li v-for="event in reservation.events" :key="event.id" class="mb-6 ml-6">
              <span class="absolute -left-1.5 w-3 h-3 bg-blue-500 rounded-full"></span>
              <p class="font-medium text-gray-800">{{ event.type }}</p>
              <p class="text-sm text-gray-600">{{ event.description }}</p>
              <p class="text-xs text-gray-400">{{ formatDate(event.created_at) }}</p>
            </li>
          </ol>
        </div>
      </div>
    </div>
  </div>
</template>
