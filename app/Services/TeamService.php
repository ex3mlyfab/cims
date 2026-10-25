<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * CIMA-043: team lifecycle. Mutations run in transactions and are audited
 * (ADR-004). Authorization is enforced by route middleware + policy.
 */
class TeamService
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data  shape: {name: string, status: string}
     */
    public function create(User $actor, array $data): Team
    {
        return DB::transaction(function () use ($data): Team {
            $team = new Team;
            $team->name = (string) $data['name'];
            $team->status = (string) $data['status'];
            $team->save();

            $this->auditLogger->record('team.created', $team, after: ['name' => $team->name, 'status' => $team->status]);

            return $team;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, Team $team, array $data): void
    {
        DB::transaction(function () use ($team, $data): void {
            $before = $this->summary($team);

            if (isset($data['name']) && (string) $data['name'] !== $team->name) {
                $team->name = $data['name'];
            }

            if (isset($data['status']) && (string) $data['status'] !== $team->status) {
                $team->status = $data['status'];
            }

            $team->save();

            $this->auditLogger->record('team.updated', $team, before: $before, after: $this->summary($team));
        });
    }

    public function destroy(User $actor, Team $team): void
    {
        DB::transaction(function () use ($team): void {
            $deleted = $this->summary($team);

            $team->delete();

            // The audit row deliberately keeps record_type/record_id without a
            // FK constraint, so the trail survives the record (ADR-004).
            $this->auditLogger->record('team.deleted', $team, before: $deleted, after: null);
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function summary(Team $team): array
    {
        return [
            'name' => $team->name,
            'status' => $team->status,
        ];
    }
}
