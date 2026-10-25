import { Form, Head, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
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
import { create as createUser, edit as editUser, index } from '@/routes/users';
import type { UserRecord } from '@/types/user';

/**
 * CIMA-042: user management list. Users are never hard-deleted —
 * deactivation preserves audit history (ADR-004, security rule 10).
 */
export default function UsersIndex({ users }: { users: UserRecord[] }) {
    const { permissions = [] } = usePage().props;

    const can = {
        update: permissions.includes('users.update'),
        deactivate: permissions.includes('users.deactivate'),
        resetPassword: permissions.includes('users.reset-password'),
    };

    return (
        <>
            <Head title="User management" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="User management"
                        description="Create accounts, assign roles, deactivate without losing audit history"
                    />
                    <Button asChild data-test="create-user">
                        <Link href={createUser()}>New user</Link>
                    </Button>
                </div>

                <div className="rounded-lg border border-border bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead className="w-48">Roles</TableHead>
                                <TableHead className="w-28">Status</TableHead>
                                <TableHead className="w-56 text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {users.map((user) => (
                                <TableRow key={user.id}>
                                    <TableCell className="max-w-48 truncate font-medium">
                                        {user.name}
                                    </TableCell>
                                    <TableCell className="max-w-56 truncate">
                                        {user.email}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex flex-wrap gap-1">
                                            {user.roles.map((role) => (
                                                <Badge
                                                    key={role}
                                                    variant="secondary"
                                                    className="font-mono text-[10px]"
                                                >
                                                    {role}
                                                </Badge>
                                            ))}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        {user.deactivated ? (
                                            <Badge
                                                variant="outline"
                                                className="text-muted-foreground"
                                                data-test="user-status-deactivated"
                                            >
                                                deactivated
                                            </Badge>
                                        ) : (
                                            <Badge
                                                variant="default"
                                                data-test="user-status-active"
                                            >
                                                active
                                            </Badge>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-1">
                                            {can.update && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={editUser(user.id)}
                                                        data-test={`edit-user-${user.email}`}
                                                    >
                                                        Edit
                                                    </Link>
                                                </Button>
                                            )}
                                            {can.resetPassword && (
                                                <Form
                                                    {...UserController.sendResetLink.form(
                                                        { user: user.id },
                                                    )}
                                                    options={{
                                                        preserveScroll: true,
                                                    }}
                                                    data-test={`reset-user-${user.email}`}
                                                >
                                                    {({ processing }) => (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            disabled={
                                                                processing
                                                            }
                                                        >
                                                            Reset link
                                                        </Button>
                                                    )}
                                                </Form>
                                            )}
                                            {can.deactivate &&
                                                (user.deactivated ? (
                                                    <Form
                                                        {...UserController.reactivate.form(
                                                            { user: user.id },
                                                        )}
                                                        options={{
                                                            preserveScroll:
                                                                true,
                                                        }}
                                                        data-test={`reactivate-user-${user.email}`}
                                                    >
                                                        {({ processing }) => (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                disabled={
                                                                    processing
                                                                }
                                                            >
                                                                Reactivate
                                                            </Button>
                                                        )}
                                                    </Form>
                                                ) : (
                                                    <Form
                                                        {...UserController.deactivate.form(
                                                            { user: user.id },
                                                        )}
                                                        options={{
                                                            preserveScroll:
                                                                true,
                                                        }}
                                                        data-test={`deactivate-user-${user.email}`}
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
                                                                Deactivate
                                                            </Button>
                                                        )}
                                                    </Form>
                                                ))}
                                        </div>
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

UsersIndex.layout = {
    breadcrumbs: [
        {
            title: 'User management',
            href: index(),
        },
    ],
};


