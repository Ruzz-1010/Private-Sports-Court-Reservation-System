<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Services\Availability\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AvailabilityController extends Controller
{
    public function __construct(private readonly AvailabilityService $availability) {}

    /**
     * Availability calendar for one court on one date.
     */
    public function index(Request $request, Court $court): View
    {
        $court = $this->resolveCourt($request, $court);
        $date = $this->resolveDate($request->string('date')->toString());
        $slots = $this->availability->slotsFor($court, $date);

        return view('customer.availability.index', [
            'courts' => Court::query()->active()->orderBy('name')->get(),
            'court' => $court,
            'date' => $date,
            'dateLabel' => $date->format('l, F j, Y'),
            'slots' => $slots,
            'isOpenDay' => $slots !== [],
            'slotCount' => count($slots),
            'previousDate' => $date->subDay(),
            'nextDate' => $date->addDay(),
            'incrementMinutes' => (int) config('reservation.increment_minutes'),
            'minDuration' => (int) config('reservation.min_duration_minutes'),
            'maxDuration' => (int) config('reservation.max_duration_minutes'),
        ]);
    }

    /**
     * JSON slot feed so the calendar can be swapped without a page reload.
     */
    public function slots(Request $request, Court $court): JsonResponse
    {
        $court = $this->resolveCourt($request, $court);
        $date = $this->resolveDate($request->string('date')->toString());

        return response()->json([
            'court_id' => $court->id,
            'date' => $date->toDateString(),
            'slots' => $this->availability->slotsFor($court, $date),
        ]);
    }

    private function resolveDate(?string $date): CarbonImmutable
    {
        $parsed = is_string($date) && $date !== ''
            ? CarbonImmutable::createFromFormat('Y-m-d', $date, config('reservation.timezone'))
            : false;

        if ($parsed === false) {
            return CarbonImmutable::now(config('reservation.timezone'))->startOfDay();
        }

        return $parsed->startOfDay();
    }

    private function resolveCourt(Request $request, Court $routeCourt): Court
    {
        $courtId = $request->integer('court_id');

        if ($courtId > 0 && $courtId !== $routeCourt->getKey()) {
            return Court::query()->active()->findOrFail($courtId);
        }

        return $routeCourt;
    }
}
