<script setup>
import { useToast } from '../composables/useToast'
const { toasts } = useToast()
</script>

<template>
  <div class="fixed top-4 right-4 z-50 space-y-2">
    <div v-for="toast in toasts" :key="toast.id"
      :class="toast.type === 'error' ? 'bg-red-600' : 'bg-green-600'"
      class="text-white px-4 py-3 rounded-lg shadow-lg text-sm min-w-[200px]">
      {{ toast.message }}
    </div>
  </div>
</template>
