<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Http\Requests\Facility\WardFormRequest;
use App\Models\Ward;
use App\Services\WardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CIMA-044: ward CRUD (Facility Management group). Authorization:
 * `permission:wards.*` route middleware + module policy.
 */
class WardController extends Controller
{
    public function index(): Response
    {
        $wards = Ward::query()
            ->orderBy('name')
            ->get()
            ->map(static fn (Ward $ward): array => [
                'id' => $ward->id,
                'code' => $ward->code,
                'name' => $ward->name,
                'capacity' => $ward->capacity,
            ]);

        return Inertia::render('wards/index', ['wards' => $wards]);
    }

    public function create(): Response
    {
        return Inertia::render('wards/create');
    }

    public function store(WardFormRequest $request, WardService $service): RedirectResponse
    {
        $this->authorize('create', Ward::class);

        $service->create($request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ward added.')]);

        return to_route('wards.index');
    }

    public function edit(Ward $ward): Response
    {
        $this->authorize('update', $ward);

        return Inertia::render('wards/edit', [
            'ward' => [
                'id' => $ward->id,
                'code' => $ward->code,
                'name' => $ward->name,
                'capacity' => $ward->capacity,
            ],
        ]);
    }

    public function update(WardFormRequest $request, Ward $ward, WardService $service): RedirectResponse
    {
        $this->authorize('update', $ward);

        $service->update($request->user(), $ward, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ward updated.')]);

        return to_route('wards.index');
    }

    public function destroy(Request $request, Ward $ward, WardService $service): RedirectResponse
    {
        $this->authorize('delete', $ward);

        $service->destroy($request->user(), $ward);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ward deleted.')]);

        return to_route('wards.index');
    }
}
