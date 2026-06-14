<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationStatusRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReservationController extends Controller
{
    use AuthorizesRequests;
    
    public function __construct(
        private readonly ReservationService $service
    ) {}

    /**
     * Listado con filtros combinables: estado, rango de fechas, búsqueda por huésped.
     */
    public function index(Request $request)
    {
        $query = Reservation::query();

        // Filtro por estado
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        // Filtro por rango de fechas (reservas que entran a partir de / hasta)
        if ($request->filled('from')) {
            $query->where('check_in', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->where('check_in', '<=', $request->date('to'));
        }

        // Búsqueda por nombre o email del huésped
        if ($request->filled('guest')) {
            $term = $request->string('guest');
            $query->where(function ($q) use ($term) {
                $q->where('guest_name', 'like', "%{$term}%")
                  ->orWhere('guest_email', 'like', "%{$term}%");
            });
        }

        $reservations = $query->latest()->paginate(15);

        return ReservationResource::collection($reservations);
    }

    /**
     * Crear una reserva.
     */
    public function store(StoreReservationRequest $request)
    {
        $reservation = $this->service->create($request->validated(), $request->user());

        return (new ReservationResource($reservation))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Ver el detalle de una reserva con su historial de eventos.
     */
    public function show(Reservation $reservation)
    {
        $this->authorize('view', $reservation);
        return new ReservationResource($reservation->load('events'));
    }

    /**
     * Modificar el estado de una reserva.
     */
    public function updateStatus(UpdateReservationStatusRequest $request, Reservation $reservation)
    {
        $this->authorize('update', $reservation);

        $newStatus = ReservationStatus::from($request->validated()['status']);
        $reservation = $this->service->changeStatus($reservation, $newStatus);

        return new ReservationResource($reservation);
    }

    /**
     * Cancelar una reserva.
     */
    public function destroy(Reservation $reservation)
    {
        $this->authorize('delete', $reservation);

        $this->service->cancel($reservation);

        return response()->json([
            'message' => 'Reserva cancelada correctamente.',
        ]);
    }
}