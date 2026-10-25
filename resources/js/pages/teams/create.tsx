import TeamController from '@/actions/App/Http/Controllers/Facility/TeamController';
import TeamForm from '@/components/team-form';
import { create } from '@/routes/teams';

/**
 * CIMA-043: add a team (Facility Management).
 */
export default function CreateTeam() {
    return (
        <TeamForm
            title="Add team"
            description="Create a clinical or operational team record"
        />
    );
}

CreateTeam.layout = {
    breadcrumbs: [
        {
            title: 'Add team',
            href: create(),
        },
    ],
};
