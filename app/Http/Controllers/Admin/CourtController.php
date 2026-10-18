<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourtRequest;
use App\Http\Requests\Admin\UpdateCourtRequest;
use App\Models\AuditLog;
use App\Models\Court;
use App\Services\Courts\DeleteCourt;
use App\Services\Courts\UploadCourtImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourtController extends Controller
{
    public function __construct(
        private readonly DeleteCourt $deleteCourt,
        private readonly UploadCourtImage $uploadCourtImage,
    ) {}

    /**
     * Summary list of every court, including archived ones.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->value();

        $courts = Court::query()
            ->withCount('reservations')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.courts.index', [
            'courts' => $courts,
            'search' => $search,
        ]);
    }

    /**
     * Individual detail view for a single court.
     */
    public function show(Court $court): View
    {
        $court->loadCount('reservations');

        return view('admin.courts.show', [
            'court' => $court,
            'reservations' => $court->reservations()
                ->with('user')
                ->whereIn('status', [
                    ReservationStatus::Pending->value,
                    ReservationStatus::Confirmed->value,
                    ReservationStatus::Completed->value,
                ])
                ->orderBy('starts_at', 'desc')
                ->limit(10)
                ->get(),
            'hasHistory' => $this->deleteCourt->hasHistory($court),
        ]);
    }

    public function create(): View
    {
        return view('admin.courts.create');
    }

    public function store(StoreCourtRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->uploadCourtImage->handle($request->file('image'));
        }

        $court = Court::create($data);

        $this->log($request, 'court.created', $court, [
            'name' => $court->name,
        ]);

        return redirect()
            ->route('admin.courts.show', $court)
            ->with('status', 'Court created successfully.');
    }

    /**
     * Prepopulated edit form.
     */
    public function edit(Court $court): View
    {
        return view('admin.courts.edit', [
            'court' => $court,
        ]);
    }

    public function update(UpdateCourtRequest $request, Court $court): RedirectResponse
    {
        $before = $court->only(['name', 'location', 'hourly_rate', 'is_active']);

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $previous = $court->image_path;
            $data['image_path'] = $this->uploadCourtImage->handle($request->file('image'));
            $court->fill($data)->save();
            $this->uploadCourtImage->remove($previous);
        } else {
            $court->update($data);
        }

        $this->log($request, 'court.updated', $court, [
            'before' => $before,
            'after' => $court->only(['name', 'location', 'hourly_rate', 'is_active']),
        ]);

        return redirect()
            ->route('admin.courts.show', $court)
            ->with('status', 'Court updated successfully.');
    }

    /**
     * Remove an unused court, or archive one that has booking history.
     */
    public function destroy(Request $request, Court $court): RedirectResponse
    {
        $result = $this->deleteCourt->handle($court);

        $this->log($request, $result['archived'] ? 'court.archived' : 'court.deleted', $court, [
            'name' => $court->name,
        ]);

        return redirect()
            ->route('admin.courts.index')
            ->with('status', $result['archived']
                ? 'Court archived because existing reservations reference it.'
                : 'Court deleted successfully.');
    }

    /**
     * Restore a previously archived court.
     */
    public function restore(Request $request, int $court): RedirectResponse
    {
        $model = Court::withTrashed()->findOrFail($court);
        $model->restore();

        $this->log($request, 'court.restored', $model, ['name' => $model->name]);

        return back()->with('status', 'Court restored.');
    }

    /**
     * Record an administrative action. Never stores secrets or payment data.
     */
    private function log(Request $request, string $action, Court $court, array $details): void
    {
        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => $court->getMorphClass(),
            'entity_id' => $court->getKey(),
            'details' => $details,
            'ip_address' => $request->ip(),
        ]);
    }
}
