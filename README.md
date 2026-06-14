# MisterPlan — API de Gestión de Reservas

Microservicio de gestión de reservas de alojamiento. API REST construida con **Laravel 13** y autenticación por tokens, más un frontend ligero en **Vue 3** que la consume.

> Prueba técnica Full-Stack (Laravel + Vue.js) para MisterPlan.

## Enlaces

- **Backend desplegado:** <PEGA_AQUÍ_LA_URL_DEL_BACKEND>
- **Frontend desplegado:** _Pendiente de despliegue._
- **Repositorio:** https://github.com/<TU_USUARIO>/reservation-api

## Stack

- PHP 8.3 · Laravel 13
- Base de datos: SQLite (portable; misma config en local y producción)
- Autenticación: Laravel Sanctum (tokens)
- Tests: Pest PHP
- Frontend: Vue 3 (Composition API) + Tailwind CSS _(en desarrollo)_

---

## Puesta en marcha (local)

Requisitos: PHP 8.2+, Composer.

```bash
# 1. Clonar e instalar dependencias
git clone https://github.com/<TU_USUARIO>/reservation-api.git
cd reservation-api
composer install

# 2. Entorno
cp .env.example .env
php artisan key:generate

# 3. Base de datos (SQLite) y datos de ejemplo
touch database/database.sqlite
php artisan migrate --seed

# 4. Arrancar
php artisan serve
```

La API queda disponible en `http://127.0.0.1:8000/api`.

El seeder crea dos usuarios de prueba y 30 reservas:

| Email | Contraseña | Rol |
|-------|-----------|-----|
| `admin@misterplan.com` | `password` | Dueño de las 30 reservas |
| `otro@misterplan.com` | `password` | Sin reservas (para probar autorización) |

### Tests

```bash
php artisan test
```

### Cola (jobs en segundo plano)

El driver de cola es `database`. Para procesar los jobs encolados:

```bash
php artisan queue:work
```

---

## Uso de la API

Todas las rutas de reservas requieren autenticación. Primero se obtiene un token vía login y se envía en la cabecera `Authorization: Bearer <token>`.

```bash
# Login → devuelve un token
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Accept: application/json" \
  -d '{"email":"admin@misterplan.com","password":"password"}'

# Listar reservas (con el token)
curl http://127.0.0.1:8000/api/reservations \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <token>"
```

### Endpoints

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/api/login` | Autenticación; devuelve un token |
| `GET` | `/api/reservations` | Lista reservas (filtros: `status`, `from`, `to`, `guest`) |
| `POST` | `/api/reservations` | Crea una reserva |
| `GET` | `/api/reservations/{id}` | Detalle con historial de eventos |
| `PATCH` | `/api/reservations/{id}/status` | Modifica el estado |
| `DELETE` | `/api/reservations/{id}` | Cancela la reserva (no la borra) |

Se incluye una colección de Postman en `reservation-api.postman_collection.json`.

---

## Decisiones de arquitectura

**Service Layer.** Toda la lógica de negocio vive en `ReservationService`, no en los controllers. El controller solo recibe la petición, delega y devuelve la respuesta. Así la lógica es testeable de forma aislada y reutilizable desde cualquier punto de entrada (API, consola, jobs), no solo desde HTTP.

**Enum con reglas de transición.** El estado (`pending`/`confirmed`/`cancelled`) se modela con un enum de PHP que además encapsula qué transiciones son válidas (`canTransitionTo`). Una reserva cancelada no puede reconfirmarse: esa regla vive en un único sitio y se valida en el service, no esparcida por el código.

**Validación en Form Requests.** Las reglas de entrada (fechas coherentes, email válido, importe no negativo) están separadas del controller en clases dedicadas. El estado inicial `pending` lo fija el service, no el cliente.

**Eventos para desacoplar.** Al confirmar una reserva, el service dispara el evento `ReservationConfirmed` en lugar de notificar directamente. Un listener reacciona registrando la notificación. Añadir nuevas reacciones (email, SMS, métricas) no requiere tocar el service.

**Jobs en cola.** `GenerateDailySummary` es una tarea de resumen pensada para ejecutarse en segundo plano (driver `database`), ilustrando el procesamiento asíncrono frente al síncrono de los eventos.

**API Resources.** Las respuestas no exponen el modelo crudo: se transforman (fechas formateadas, enum a string, eventos cargados solo cuando se piden con `whenLoaded` para evitar el problema N+1).

**Autenticación y autorización.** Sanctum gestiona los tokens; una Policy (`ReservationPolicy`) garantiza que cada usuario solo accede a sus propias reservas (403 en caso contrario).

**Transacciones.** Crear o cambiar de estado una reserva implica también registrar su evento; ambas operaciones van en una transacción para que no queden datos a medias si algo falla.

---

## Qué haría diferente con más tiempo

- **Paginación y filtros más ricos** en el listado (ordenación, búsqueda por rango de importe).
- **Más cobertura de tests**: casos de transición de estado inválida vía API, expiración de tokens, filtros combinados.
- **Roles** (admin que vea todas las reservas vs. usuario que solo vea las suyas), apoyándome en la Policy ya existente.
- **Soft deletes** en reservas para conservar histórico en lugar de depender solo del cambio de estado.
- **Notificaciones reales** (email) en el listener, hoy simuladas con log.

---

## Escalabilidad: ¿y si llegan 1000 reservas/hora?

Mil reservas/hora (~1 cada 3,6 s) no es un volumen alto en sí, pero el diseño ya contempla el crecimiento:

- **Base de datos:** migrar de SQLite a **PostgreSQL/MySQL**, que soportan escritura concurrente real. Los índices ya definidos (`status`, rango de fechas) mantienen los filtros eficientes.
- **Trabajo asíncrono:** mover notificaciones, generación de eventos secundarios y resúmenes a **jobs en cola** procesados por workers dedicados (Redis como driver en vez de database), de modo que la creación de la reserva responda rápido y el trabajo pesado ocurra fuera de la petición.
- **Lectura:** cachear los listados más consultados y, si crece mucho, separar réplicas de lectura.
- **API stateless:** al autenticar por token (sin sesión en servidor), la app escala horizontalmente — basta poner más instancias detrás de un balanceador.
- **Rate limiting** ya presente para absorber picos y proteger de abuso.

El cuello de botella real a vigilar sería la escritura concurrente y los efectos secundarios por reserva; ambos se mitigan con cola + base de datos transaccional.

---

## Integración con otros servicios

El sistema de **eventos** es el punto de extensión natural. Hoy `ReservationConfirmed` lo escucha un listener que registra un log; para integrar un **servicio de notificaciones externo** bastaría con:

1. Añadir un listener que, al confirmarse una reserva, despache un job a la cola.
2. Ese job llama a la API del servicio de notificaciones (email/SMS/push) con reintentos automáticos si falla.

El service que crea la reserva no se entera ni se modifica: solo anuncia el hecho. Para integraciones entrantes (p. ej. un PMS externo que cree reservas), la misma API REST sirve de contrato; bastaría con emitir tokens específicos por servicio y, si hiciera falta, **webhooks** salientes para notificar cambios de estado a terceros.