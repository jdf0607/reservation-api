# MisterPlan — API de Gestión de Reservas

Microservicio de gestión de reservas de alojamiento: **API REST en Laravel 13** con autenticación por tokens, más un **frontend Vue 3** que la consume.

## Enlaces

- **Backend desplegado:** https://reservation-api-production-ebdf.up.railway.app
- **Frontend desplegado:** https://reservation-api-gold.vercel.app
- **Repositorio:** https://github.com/jdf0607/reservation-api

## Stack

PHP 8.3 · Laravel 13 · SQLite · Laravel Sanctum · Pest PHP · Vue 3 (Composition API) · Tailwind CSS · Vite

## Estructura

```
/            → Backend Laravel (API REST)
/frontend    → Frontend Vue 3 (SPA)
```

## Puesta en marcha

### Backend

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

API disponible en `http://127.0.0.1:8000/api`. 
Para procesar jobs en cola: `php artisan queue:work`.

El resumen diario está programado a medianoche vía el scheduler de Laravel. En producción se activa con una entrada de cron del sistema que ejecuta el scheduler cada minuto:

```bash
* * * * * cd /ruta/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

Para probarlo manualmente sin esperar: `php artisan schedule:test.`

Usuarios de prueba creados por el seeder:

| Email | Contraseña | Rol |
|-------|-----------|-----|
| `admin@misterplan.com` | `password` | Dueño de las 30 reservas |
| `otro@misterplan.com` | `password` | Sin reservas (para probar autorización) |

### Frontend

```bash
cd frontend
npm install
echo "VITE_API_URL=http://127.0.0.1:8000/api" > .env
npm run dev
```

### Tests

```bash
php artisan test
```

---

## Endpoints

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/api/login` | Autenticación; devuelve un token |
| `GET` | `/api/reservations` | Lista (filtros: `status`, `from`, `to`, `guest`) |
| `POST` | `/api/reservations` | Crea una reserva |
| `GET` | `/api/reservations/{id}` | Detalle con historial de eventos |
| `PATCH` | `/api/reservations/{id}/status` | Modifica el estado |
| `DELETE` | `/api/reservations/{id}` | Cancela (no borra) |

Colección de Postman incluida en `reservation-api.postman_collection.json`.

---

## Decisiones de arquitectura

**Service Layer.** La lógica de negocio vive en `ReservationService`, no en los controllers. El controller recibe la petición, delega y devuelve la respuesta. Así la lógica es testeable de forma aislada y reutilizable desde cualquier punto de entrada (API, consola, jobs), no solo desde HTTP. El controller inyecta el service por el constructor, que Laravel resuelve automáticamente.

**Enum con reglas de transición.** El estado (`pending`/`confirmed`/`cancelled`) se modela con un enum de PHP que encapsula qué transiciones son válidas (`canTransitionTo`): una reserva `pending` puede confirmarse o cancelarse, una `confirmed` solo cancelarse, y una `cancelled` ya no cambia (estado terminal). La regla de negocio vive en un único sitio y se valida en el service, no esparcida por el código.

**Validación en dos capas.** Los Form Requests validan el formato de entrada (fechas coherentes con `check_out` posterior a `check_in`, email válido, importe no negativo). El estado inicial `pending` lo fija el service, no el cliente. La validación del enum (`Rule::enum`) comprueba que el valor exista; que la *transición* sea legal lo decide el service. Validación de formato y regla de negocio, cada una en su sitio.

**Eventos para desacoplar.** Al confirmar una reserva, el service dispara `ReservationConfirmed` en lugar de notificar directamente. Un listener reacciona registrando la notificación. Añadir nuevas reacciones (email, SMS, métricas) no requiere tocar el service: solo anuncia el hecho, y quién reacciona es problema de los listeners.

**Jobs en cola.** `GenerateDailySummary` es trabajo diferido pensado para segundo plano (driver `database`). . La distinción clave: un evento reacciona a algo que acaba de pasar y se procesa enseguida; un job se encola y procesa aparte, sin bloquear la respuesta al usuario. El job está programado para ejecutarse automáticamente cada día a medianoche mediante el scheduler de Laravel (routes/console.php); su salida (conteos por estado) se registra en el log, como corresponde a una tarea de fondo sin interacción de usuario.

**API Resources.** Las respuestas no exponen el modelo crudo. Las fechas se formatean (`2026-08-07` en vez del timestamp completo), el enum se convierte a string, y los eventos solo se incluyen cuando se cargan (`whenLoaded`), evitando el problema N+1: en el listado no se cargan (serían cientos de queries), en el detalle sí.

**Autenticación y autorización.** Sanctum gestiona los tokens. Una Policy (`ReservationPolicy`) garantiza que cada usuario solo accede a sus propias reservas (403 en caso contrario).

**Transacciones.** Crear o cambiar el estado de una reserva implica también registrar su evento; ambas operaciones van en una `DB::transaction` para que no queden datos a medias si algo falla.

**Decisiones de modelo de datos.** El importe es `decimal(10,2)`, nunca `float` (los flotantes acumulan errores de redondeo con dinero). Hay índices en `status` y en el rango de fechas, justo las columnas por las que se filtra. `ReservationEvent` solo tiene `created_at` (un evento es un registro histórico inmutable). La relación reserva→eventos es uno-a-muchos con `cascadeOnDelete` para no dejar eventos huérfanos.

**Frontend desacoplado.** SPA en Vue independiente que consume la API. Un composable `useApi` centraliza Axios con interceptores: uno añade el token a cada petición automáticamente, otro redirige al login si el token caduca (401). Otro composable `useToast` gestiona el feedback visual con estado compartido. El formulario de creación valida en cliente (feedback instantáneo) y mapea los errores 422 del servidor a cada campo (doble red de validación).

---

## Qué haría diferente con más tiempo

- **Extraer helpers compartidos** en el frontend: la función `formatDate` está duplicada en varias vistas; iría a un módulo común.
- **Más cobertura de tests**: casos de transición de estado inválida vía API, expiración de tokens, filtros combinados, y tests del frontend.
- **Roles** (un admin que vea todas las reservas vs. usuario que solo vea las suyas), apoyándome en la Policy ya existente.
- **Soft deletes** en reservas para conservar histórico en lugar de depender solo del cambio de estado.
- **Notificaciones reales** (email) en el listener, hoy simuladas con log.
- **Paginación y filtros más ricos** en el listado (ordenación, búsqueda por rango de importe).

---

## Escalabilidad: ¿y si llegan 1000 reservas/hora?

Mil reservas/hora (~1 cada 3,6 s) no es un volumen extremo, pero el diseño ya contempla el crecimiento:

- **Base de datos:** migrar de SQLite a **PostgreSQL o MySQL**, que soportan escritura concurrente real. Los índices ya definidos (`status`, rango de fechas) mantienen los filtros eficientes.
- **Trabajo asíncrono:** mover notificaciones y resúmenes a **jobs en cola** procesados por workers dedicados, con **Redis** como driver en vez de `database`. Así la creación de la reserva responde rápido y el trabajo pesado ocurre fuera de la petición.
- **Lectura:** cachear los listados más consultados y, si crece mucho, usar réplicas de lectura.
- **API stateless:** al autenticar por token (sin sesión en servidor), la app escala horizontalmente — basta poner más instancias detrás de un balanceador.
- **Rate limiting** ya presente (60 req/min) para absorber picos y proteger de abuso.

El cuello de botella real a vigilar sería la escritura concurrente y los efectos secundarios por reserva; ambos se mitigan con cola + base de datos transaccional.

---

## Integración con otros servicios

El sistema de **eventos** es el punto de extensión natural. Hoy `ReservationConfirmed` lo escucha un listener que registra un log. Para integrar un **servicio de notificaciones externo** bastaría con:

1. Añadir un listener que, al confirmarse una reserva, despache un job a la cola.
2. Ese job llama a la API del servicio de notificaciones (email/SMS/push) con reintentos automáticos si falla.

El service que crea la reserva no se entera ni se modifica: solo anuncia el hecho. Para integraciones entrantes (p. ej. un PMS externo que cree reservas), la propia API REST sirve de contrato; bastaría emitir tokens específicos por servicio y, si hiciera falta, **webhooks** salientes para notificar cambios de estado a terceros.