<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Http\Requests\Facility\BedFormRequest;
use App\Models\Bed;
use App\Models\BedEvent;
use App\Models\Ward;
use App\Services\BedService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CIMA-045: bed CRUD (Facility Management group). Authorization:
 * `permission:beds.*` route middleware + module policy. Ward managers and
 * bed managers edit; ED staff only get the allocation workflow later
 * (CIMA-060), so `beds.manage` gates every mutation here.
 */
class BedController extends Controller
{
    public function index(): Response
    {
        $beds = Bed::query()
            ->with('ward')
            ->get()
            ->sortBy(static fn (Bed $bed): array => [$bed->ward->name, $bed->number], SORT_NATURAL | SORT_REGULAR)
            ->values()
            ->map(static fn (Bed $bed): array => [
                'id' => $bed->id,
                'number' => $bed->number,
                'status' => $bed->status->value,
                'status_label' => $bed->status->label(),
                'ward' => [
                    'id' => $bed->ward->id,
                    'code' => $bed->ward->code,
                    'name' => $bed->ward->name,
                ],
            ]);

        return Inertia::render('beds/index', ['beds' => $beds]);
    }

    public function create(): Response
    {
        return Inertia::render('beds/create', ['wards' => $this->wardOptions()]);
    }

    public function store(BedFormRequest $request, BedService $service): RedirectResponse
    {
        $this->authorize('create', Bed::class);

        $validated = $request->validated();
        $ward = Ward::query()->findOrFail((string) $validated['ward_id']);

        $service->create($request->user(), $ward, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bed added.')]);

        return to_route('beds.index');
    }

    public function edit(Bed $bed): Response
    {
        $this->authorize('update', $bed);

        return Inertia::render('beds/edit', [
            'bed' => [
                'id' => $bed->id,
                'number' => $bed->number,
                'status' => $bed->status->value,
                'ward' => [
                    'id' => $bed->ward->id,
                    'code' => $bed->ward->code,
                    'name' => $bed->ward->name,
                ],
            ],
        ]);
    }

    /**
     * CIMA-047: dedicated bed history page (append-only bed_events),
     * newest first. Read-only projection over the bed_events ledger.
     */
    public function show(Bed $bed): Response
    {
        $this->authorize('view', $bed);

        return Inertia::render('beds/show', [
            'bed' => [
                'id' => $bed->id,
                'number' => $bed->number,
                'status' => $bed->status->value,
                'ward' => [
                    'id' => $bed->ward->id,
                    'code' => $bed->ward->code,
                    'name' => $bed->ward->name,
                ],
            ],
            'history' => $this->history($bed),
        ]);
    }

    /**
     * Append-only bed_events for the history page (CIMA-046/047).
     * Oldest-first rendering would clobber long histories; newest first,
     * capped.
     *
     * @return array<int, array{id: string, action: string, occurred_at: string, actor_name: string|null, details: array<string, mixed>|null}>
     */
    protected function history(Bed $bed): array
    {
        return $bed->events()
            ->with('actor')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(static fn (BedEvent $event): array => [
                'id' => $event->id,
                'action' => $event->action,
                'occurred_at' => $event->occurred_at->format(DATE_ATOM),
                'actor_name' => $event->actor?->name,
                'details' => $event->details,
            ])
            ->all();
    }

    public function update(BedFormRequest $request, Bed $bed, BedService $service): RedirectResponse
    {
        $this->authorize('update', $bed);

        $service->update($request->user(), $bed, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bed updated.')]);

        return to_route('beds.index');
    }

    public function destroy(Request $request, Bed $bed, BedService $service): RedirectResponse
    {
        $this->authorize('delete', $bed);

        $service->destroy($request->user(), $bed);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bed deleted.')]);

        return to_route('beds.index');
    }

    /**
     * Ward picker options for the create form. Any bed manager may place a
     * bed in any existing ward, so this is independent of `wards.view`.
     *
     * @return array<int, array{id: string, code: string, name: string}>
     */
    protected function wardOptions(): array
    {
        return Ward::query()
            ->orderBy('name')
            ->get()
            ->map(static fn (Ward $ward): array => [
                'id' => $ward->id,
                'code' => $ward->code,
                'name' => $ward->name,
            ])
            ->all();
    }
}
