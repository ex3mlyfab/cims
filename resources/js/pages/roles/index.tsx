import { Form, Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import RoleController from '@/actions/App/Http/Controllers/Admin/RoleController';
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
import {
    create as createRole,
    edit as editRole,
    index,
} from '@/routes/roles';
import type { RoleRecord } from '@/types/role';

/**
 * CIMA-041: roles & permissions list. The seeded Super Admin role is
 * protected: it shows a badge and offers no edit/delete actions (ADR-002).
 */
export default function RolesIndex({ roles }: { roles: RoleRecord[] }) {
    return (
        <>
            <Head title="Roles & permissions" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Roles & permissions"
                        description="Manage the role registry and which permissions each role holds"
                    />
                    <Button asChild data-test="create-role">
                        <Link href={createRole()}>New role</Link>
                    </Button>
                </div>

                <div className="rounded-lg border border-border bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead className="w-40">
                                    Permissions
                                </TableHead>
                                <TableHead className="w-32">Users</TableHead>
                                <TableHead className="w-40 text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {roles.map((role) => (
                                <TableRow key={role.id}>
                                    <TableCell>
                                        <div className="flex items-center gap-2 font-medium">
                                            {role.name}
                                            {role.superAdmin && (
                                                <Badge
                                                    variant="secondary"
                                                    data-test="super-admin-badge"
                                                >
                                                    protected
                                                </Badge>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        {role.superAdmin
                                            ? 'All permissions'
                                            : role.permissionsCount}
                                    </TableCell>
                                    <TableCell>{role.usersCount}</TableCell>
                                    <TableCell className="text-right">
                                        {role.superAdmin ? (
                                            <span className="text-xs text-muted-foreground">
                                                —
                                            </span>
                                        ) : (
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={editRole(role.id)}
                                                        data-test={`edit-role-${role.name}`}
                                                    >
                                                        Edit
                                                    </Link>
                                                </Button>
                                                <Form
                                                    {...RoleController.destroy.form(
                                                        { role: role.id },
                                                    )}
                                                    options={{
                                                        preserveScroll: true,
                                                    }}
                                                    data-test={`delete-role-${role.name}`}
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

RolesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Roles & permissions',
            href: index(),
        },
    ],
};
