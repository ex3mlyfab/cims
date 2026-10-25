import { Form, Head, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import TeamController from '@/actions/App/Http/Controllers/Facility/TeamController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { create, edit, index } from '@/routes/teams';
import type { TeamRecord } from '@/types/team';

/**
 * CIMA-043: teams (Facility Management group) — CRUD over name + status.
 */
export default function TeamsIndex({ teams }: { teams: TeamRecord[] }) {
    const { permissions = [] } = usePage().props;

    const canManage = permissions.includes('teams.manage');

    return (
        <>
            <Head title="Teams" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Teams"
                        description="Clinical and operational teams with a lifecycle status"
                    />
                    {canManage && (
                        <Button asChild data-test="create-team">
                            <Link href={create()}>Add team</Link>
                        </Button>
                    )}
                </div>

                <div className="rounded-lg border border-border bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead className="w-32">Status</TableHead>
                                <TableHead className="w-48 text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {teams.map((team) => (
                                <TableRow key={team.id}>
                                    <TableCell className="font-medium">
                                        {team.name}
                                    </TableCell>
                                    <TableCell>
                                        {team.status === 'active' ? (
                                            <Badge data-test="team-status-active">
                                                active
                                            </Badge>
                                        ) : (
                                            <Badge
                                                variant="outline"
                                                className="text-muted-foreground"
                                                data-test="team-status-inactive"
                                            >
                                                inactive
                                            </Badge>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {canManage ? (
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={edit(team.id)}
                                                        data-test={`edit-team-${team.id}`}
                                                    >
                                                        Edit
                                                    </Link>
                                                </Button>
                                                <Form
                                                    {...TeamController.destroy.form(
                                                        team.id,
                                                    )}
                                                    options={{
                                                        preserveScroll: true,
                                                    }}
                                                    data-test={`delete-team-${team.id}`}
                                                >
                                                    {({ processing }) => (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            disabled={
                                                                processing
                                                            }
                                                            className="text-destructive hover:text-destructive"
                                                        >
                                                            Delete
                                                        </Button>
                                                    )}
                                                </Form>
                                            </div>
                                        ) : (
                                            <span className="text-xs text-muted-foreground">
                                                —
                                            </span>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}

TeamsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Teams',
            href: index(),
        },
    ],
};
