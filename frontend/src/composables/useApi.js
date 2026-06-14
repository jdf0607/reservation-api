import axios from 'axios'
import { useRouter } from 'vue-router'

// Instancia de axios apuntando a la API de Laravel
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

// Interceptor: añade el token a cada petición automáticamente
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export function useApi() {
  const router = useRouter()

  // Interceptor de respuesta: si el token caduca (401), echa al login
  api.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error.response?.status === 401) {
        localStorage.removeItem('token')
        router.push({ name: 'login' })
      }
      return Promise.reject(error)
    }
  )

  return { api }
}