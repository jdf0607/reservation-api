<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApi } from '../composables/useApi'
import { useToast } from '../composables/useToast'

const { api } = useApi()
const { showToast } = useToast()
const route = useRoute()
const router = useRouter()

const reservation = ref(null)
const loading = ref(false)
const updating = ref(false)
const error = ''

function formatDate(value) {
  if (!value) return ''
  return new Date(value).toLocaleDateString('es-ES')
}

// Botones visibles según el estado actual (refleja la máquina de estados del backend)
const canConfirm = computed(() => reservation.value?.status === 'pending')
const canCancel = computed(() =>
  reservation.value && reservation.value.status !== 'cancelled'
)

async function fetchReservation() {
  loading.value = true
  try {
    const { data } = await api.get(`/reservations/${route.params.id}`)
    reservation.value = data.data
  } catch (e) {
    showToast('No se pudo cargar la reserva.', 'error')
  } finally {
    loading.value = false
  }
}

async function changeStatus(newStatus) {
  updating.value = true
  try {
    await api.patch(`/reservations/${route.params.id}/status`, { status: newStatus })
    showToast(
      newStatus === 'confirmed' ? 'Reserva confirmada.' : 'Reserva cancelada.',
      'success'
    )
    // Recargamos para ver el nuevo estado y el evento añadido al timeline
    await fetchReservation()
  } catch (e) {
    showToast('No se pudo cambiar el estado.', 'error')
  } finally {
    updating.value = false
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

      <div v-else-if="reservation" class="space-y-6">
        <!-- Datos de la reserva -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex justify-between items-start mb-4">
            <h1 class="text-2xl font-bold text-gray-800">{{ reservation.guest_name }}</h1>
            <span
              :class="{
                'bg-yellow-100 text-yellow-800': reservation.status === 'pending',
                'bg-green-100 text-green-800': reservation.status === 'confirmed',
                'bg-red-100 text-red-800': reservation.status === 'cancelled',
              }"
              class="px-3 py-1 rounded-full text-xs font-medium">
              {{ reservation.status }}
            </span>
          </div>

          <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-500">Email:</span> {{ reservation.guest_email }}</div>
            <div><span class="text-gray-500">Propiedad:</span> {{ reservation.property_name }}</div>
            <div><span class="text-gray-500">Entrada:</span> {{ formatDate(reservation.check_in) }}</div>
            <div><span class="text-gray-500">Salida:</span> {{ formatDate(reservation.check_out) }}</div>
            <div><span class="text-gray-500">Importe:</span> {{ reservation.amount }} €</div>
          </div>
          <p v-if="reservation.notes" class="mt-4 text-sm text-gray-600">
            <span class="text-gray-500">Notas:</span> {{ reservation.notes }}
          </p>

          <!-- Acciones de estado -->
          <div v-if="canConfirm || canCancel" class="mt-6 flex gap-3">
            <button v-if="canConfirm" @click="changeStatus('confirmed')" :disabled="updating"
              class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 disabled:opacity-50 text-sm">
              {{ updating ? 'Procesando...' : 'Confirmar' }}
            </button>
            <button v-if="canCancel" @click="changeStatus('cancelled')" :disabled="updating"
              class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 disabled:opacity-50 text-sm">
              {{ updating ? 'Procesando...' : 'Cancelar' }}
            </button>
          </div>
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
