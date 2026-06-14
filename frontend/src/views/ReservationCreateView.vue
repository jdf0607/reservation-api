<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../composables/useApi'
import { useToast } from '../composables/useToast'

const { api } = useApi()
const { showToast } = useToast()
const router = useRouter()

const form = ref({
  guest_name: '',
  guest_email: '',
  property_name: '',
  check_in: '',
  check_out: '',
  amount: '',
  notes: '',
})

const errors = ref({})
const loading = ref(false)

// Validación en cliente antes de enviar
function validate() {
  const e = {}
  if (!form.value.guest_name) e.guest_name = 'El nombre es obligatorio.'
  if (!form.value.guest_email) e.guest_email = 'El email es obligatorio.'
  if (!form.value.property_name) e.property_name = 'La propiedad es obligatoria.'
  if (!form.value.check_in) e.check_in = 'La fecha de entrada es obligatoria.'
  if (!form.value.check_out) e.check_out = 'La fecha de salida es obligatoria.'
  if (form.value.check_in && form.value.check_out && form.value.check_out <= form.value.check_in) {
    e.check_out = 'La salida debe ser posterior a la entrada.'
  }
  if (!form.value.amount || form.value.amount < 0) e.amount = 'El importe debe ser positivo.'
  errors.value = e
  return Object.keys(e).length === 0
}

async function submit() {
  if (!validate()) return

  loading.value = true
  errors.value = {}
  try {
    await api.post('/reservations', form.value)
    showToast('Reserva creada correctamente.', 'success')
    router.push({ name: 'reservations' })
  } catch (err) {
    if (err.response?.status === 422) {
      // Errores de validación del servidor
      const serverErrors = err.response.data.errors
      for (const field in serverErrors) {
        errors.value[field] = serverErrors[field][0]
      }
      showToast('Revisa los errores del formulario.', 'error')
    } else {
      showToast('No se pudo crear la reserva.', 'error')
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 p-6">
    <div class="max-w-xl mx-auto">
      <button @click="router.push({ name: 'reservations' })"
        class="text-blue-600 hover:underline mb-4">← Volver al listado</button>

      <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Nueva reserva</h1>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del huésped</label>
            <input v-model="form.guest_name" type="text"
              class="w-full border border-gray-300 rounded-md px-3 py-2" />
            <p v-if="errors.guest_name" class="text-red-600 text-xs mt-1">{{ errors.guest_name }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input v-model="form.guest_email" type="email"
              class="w-full border border-gray-300 rounded-md px-3 py-2" />
            <p v-if="errors.guest_email" class="text-red-600 text-xs mt-1">{{ errors.guest_email }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Propiedad</label>
            <input v-model="form.property_name" type="text"
              class="w-full border border-gray-300 rounded-md px-3 py-2" />
            <p v-if="errors.property_name" class="text-red-600 text-xs mt-1">{{ errors.property_name }}</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Entrada</label>
              <input v-model="form.check_in" type="date"
                class="w-full border border-gray-300 rounded-md px-3 py-2" />
              <p v-if="errors.check_in" class="text-red-600 text-xs mt-1">{{ errors.check_in }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Salida</label>
              <input v-model="form.check_out" type="date"
                class="w-full border border-gray-300 rounded-md px-3 py-2" />
              <p v-if="errors.check_out" class="text-red-600 text-xs mt-1">{{ errors.check_out }}</p>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Importe (€)</label>
            <input v-model="form.amount" type="number" step="0.01"
              class="w-full border border-gray-300 rounded-md px-3 py-2" />
            <p v-if="errors.amount" class="text-red-600 text-xs mt-1">{{ errors.amount }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notas (opcional)</label>
            <textarea v-model="form.notes" rows="2"
              class="w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
          </div>

          <button type="submit" :disabled="loading"
            class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 disabled:opacity-50">
            {{ loading ? 'Creando...' : 'Crear reserva' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
