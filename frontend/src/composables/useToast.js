import { ref } from 'vue'

// Estado compartido entre todos los componentes (fuera de la función)
const toasts = ref([])
let nextId = 0

export function useToast() {
  function showToast(message, type = 'success') {
    const id = nextId++
    toasts.value.push({ id, message, type })

    // Se elimina solo a los 3 segundos
    setTimeout(() => {
      toasts.value = toasts.value.filter((t) => t.id !== id)
    }, 3000)
  }

  return { toasts, showToast }
}
