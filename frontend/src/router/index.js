import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import ReservationListView from '../views/ReservationListView.vue'
import ReservationDetailView from '../views/ReservationDetailView.vue'
import ReservationCreateView from '../views/ReservationCreateView.vue'

const routes = [
  { path: '/login', name: 'login', component: LoginView },
  { path: '/', name: 'reservations', component: ReservationListView },
  { path: '/reservations/new', name: 'reservation-create', component: ReservationCreateView },
  { path: '/reservations/:id', name: 'reservation-detail', component: ReservationDetailView, props: true },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Guard: si no hay token, redirige al login (salvo que ya vaya al login)
router.beforeEach((to) => {
  const token = localStorage.getItem('token')
  if (!token && to.name !== 'login') {
    return { name: 'login' }
  }
})

export default router