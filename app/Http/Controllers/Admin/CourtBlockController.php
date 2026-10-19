<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourtBlockRequest;
use App\Models\AuditLog;
use App\Models\Court;
use App\Models\CourtBlock;
use App\Services\Availability\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourtBlockController extends Controller
{
    public function __construct(private readonly AvailabilityService $availability) {}

    public function index(): View
    {
        return view('admin.court-blocks.index', [
            'blocks' => CourtBlock::query()->with(['court:id,name', 'creator:id,name'])->orderBy('starts_at')->paginate(15),
            'courts' => Court::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreCourtBlockRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $court = Court::findOrFail($data['court_id']);
        $timezone = config('reservation.timezone');
        $start = CarbonImmutable::parse($data['starts_at'], $timezone);
        $end = CarbonImmutable::parse($data['ends_at'], $timezone);
        $availability = $this->availability->checkInterval($court, $start, $end);

        if ($availability['available']) {
            // A closure is valid only for a bookable interval; it cannot silently
            // overlap an active customer reservation or another block.
            $block = CourtBlock::create([
                'court_id' => $court->id, 'starts_at' => $start, 'ends_at' => $end,
                'reason' => $data['reason'], 'created_by' => $request->user()->id,
            ]);
            AuditLog::create(['user_id' => $request->user()->id, 'action' => 'court_block.created', 'entity_type' => $block->getMorphClass(), 'entity_id' => $block->id, 'details' => ['court' => $court->name, 'reason' => $block->reason], 'ip_address' => $request->ip()]);

            return back()->with('status', 'Schedule block created. The time is no longer bookable.');
        }

        return back()->withInput()->withErrors(['starts_at' => $availability['reason']]);
    }

    public function destroy(Request $request, CourtBlock $courtBlock): RedirectResponse
    {
        $this->authorize('delete', $courtBlock);
        AuditLog::create(['user_id' => $request->user()?->id, 'action' => 'court_block.deleted', 'entity_type' => $courtBlock->getMorphClass(), 'entity_id' => $courtBlock->id, 'details' => ['reason' => $courtBlock->reason], 'ip_address' => $request->ip()]);
        $courtBlock->delete();

        return back()->with('status', 'Schedule block removed.');
    }
}
