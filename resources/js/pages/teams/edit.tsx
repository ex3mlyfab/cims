import TeamForm from '@/components/team-form';
import { index } from '@/routes/teams';
import type { TeamRecord } from '@/types/team';

/**
 * CIMA-043: edit a team (Facility Management).
 */
export default function EditTeam({ team }: { team: TeamRecord }) {
    return (
        <TeamForm
            title={`Edit team: ${team.name}`}
            description="Update the team name or lifecycle status"
            team={team}
        />
    );
}

EditTeam.layout = {
    breadcrumbs: [
        {
            title: 'Teams',
            href: index(),
        },
    ],
};
