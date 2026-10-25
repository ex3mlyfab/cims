<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Http\Requests\Facility\TeamFormRequest;
use App\Models\Team;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CIMA-043: team CRUD (Facility Management group). Authorization:
 * `permission:teams.*` route middleware + module policy.
 */
class TeamController extends Controller
{
    public function index(): Response
    {
        $teams = Team::query()
            ->orderBy('name')
            ->get()
            ->map(static fn (Team $team): array => [
                'id' => $team->id,
                'name' => $team->name,
                'status' => $team->status,
            ]);

        return Inertia::render('teams/index', ['teams' => $teams]);
    }

    public function create(): Response
    {
        return Inertia::render('teams/create');
    }

    public function store(TeamFormRequest $request, TeamService $service): RedirectResponse
    {
        $this->authorize('create', Team::class);

        $service->create($request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team added.')]);

        return to_route('teams.index');
    }

    public function edit(Team $team): Response
    {
        $this->authorize('update', $team);

        return Inertia::render('teams/edit', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'status' => $team->status,
            ],
        ]);
    }

    public function update(TeamFormRequest $request, Team $team, TeamService $service): RedirectResponse
    {
        $this->authorize('update', $team);

        $service->update($request->user(), $team, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team updated.')]);

        return to_route('teams.index');
    }

    public function destroy(Request $request, Team $team, TeamService $service): RedirectResponse
    {
        $this->authorize('delete', $team);

        $service->destroy($request->user(), $team);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Team deleted.')]);

        return to_route('teams.index');
    }
}
