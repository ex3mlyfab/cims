<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clinical\TreatmentStatusFormRequest;
use App\Models\TreatmentStatus;
use App\Services\TreatmentStatusService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CIMA-052: treatment-status registry (Clinical Operations group). The
 * configurable workflow labels used by the admission create/update flows
 * (CIMA-051/061) and the public display status column (Phase 7).
 */
class TreatmentStatusController extends Controller
{
    public function index(): Response
    {
        $statuses = TreatmentStatus::query()
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->map(static fn (TreatmentStatus $status): array => [
                'id' => $status->id,
                'slug' => $status->slug,
                'label' => $status->label,
                'sort_order' => $status->sort_order,
                'is_active' => $status->is_active,
            ]);

        return Inertia::render('treatment-statuses/index', ['statuses' => $statuses]);
    }

    public function create(): Response
    {
        return Inertia::render('treatment-statuses/create');
    }

    public function store(TreatmentStatusFormRequest $request, TreatmentStatusService $service): RedirectResponse
    {
        $this->authorize('create', TreatmentStatus::class);

        $service->create($request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Treatment status created.')]);

        return to_route('treatment-statuses.index');
    }

    public function edit(TreatmentStatus $status): Response
    {
        $this->authorize('update', $status);

        return Inertia::render('treatment-statuses/edit', [
            'status' => [
                'id' => $status->id,
                'slug' => $status->slug,
                'label' => $status->label,
                'sort_order' => $status->sort_order,
                'is_active' => $status->is_active,
            ],
        ]);
    }

    public function update(TreatmentStatusFormRequest $request, TreatmentStatus $status, TreatmentStatusService $service): RedirectResponse
    {
        $this->authorize('update', $status);

        $service->update($request->user(), $status, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Treatment status updated.')]);

        return to_route('treatment-statuses.index');
    }

    public function destroy(TreatmentStatus $status, TreatmentStatusService $service): RedirectResponse
    {
        $this->authorize('delete', $status);

        $service->destroy(auth()->user(), $status);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Treatment status deleted.')]);

        return to_route('treatment-statuses.index');
    }
}
