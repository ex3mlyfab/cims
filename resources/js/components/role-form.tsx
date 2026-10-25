import { Form, Head } from '@inertiajs/react';
import { useMemo } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import type { GroupedPermissions } from '@/types/role';

/**
 * CIMA-041: shared create/edit role form (Inertia Form + Wayfinder actions).
 * `role` is undefined for create. Permission names are validated server-side;
 * unknown or tampered values are rejected by `PermissionNames`.
 */
export default function RoleForm({
    title,
    description,
    action,
    role,
    groupedPermissions,
}: {
    title: string;
    description: string;
    action: {
        action: string;
        method: 'post';
    };
    role?: { name: string; permissions: string[] };
    groupedPermissions: GroupedPermissions;
}) {
    const moduleLabels = useMemo(
        () => Object.keys(groupedPermissions).sort(),
        [groupedPermissions],
    );

    const has = (permission: string): boolean =>
        role?.permissions.includes(permission) ?? false;

    return (
        <>
            <Head title={title} />

            <h1 className="sr-only">{title}</h1>

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading title={title} description={description} />

                <Form
                    {...action}
                    options={{
                        preserveScroll: true,
                    }}
                    className="max-w-3xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <div className="space-y-6 rounded-lg border border-border bg-card p-4">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Role name</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block max-w-sm"
                                    defaultValue={role?.name ?? ''}
                                    name="name"
                                    required
                                    maxLength={30}
                                    placeholder="e.g. immunization officer"
                                    data-test="role-name"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.name}
                                />
                            </div>

                            <Separator />

                            <div className="space-y-4">
                                <div>
                                    <Label>Permissions</Label>

                                    <p className="text-xs text-muted-foreground">
                                        Choose the smallest permission set
                                        that lets the role do its work. Names
                                        are validated against the permission
                                        registry server-side.
                                    </p>
                                </div>

                                {moduleLabels.map((module) => (
                                    <fieldset
                                        key={module}
                                        className="rounded-md border border-border/60 p-3"
                                    >
                                        <legend className="px-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                            {module}
                                        </legend>

                                        <div className="grid gap-2 sm:grid-cols-2">
                                            {groupedPermissions[
                                                module
                                            ].map((permission) => (
                                                <label
                                                    key={permission.id}
                                                    className="flex items-center gap-2 text-sm has-[:focus-visible]:rounded-md has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring"
                                                    data-test={`permission-${permission.name}`}
                                                >
                                                    <input
                                                        type="checkbox"
                                                        name="permissions[]"
                                                        value={permission.name}
                                                        defaultChecked={has(
                                                            permission.name,
                                                        )}
                                                        className="size-4 accent-primary"
                                                    />
                                                    <span className="font-mono text-xs">
                                                        {permission.name}
                                                    </span>
                                                </label>
                                            ))}
                                        </div>
                                    </fieldset>
                                ))}

                                <InputError
                                    className="mt-2"
                                    message={
                                        errors['permissions.0'] ??
                                        errors.permissions
                                    }
                                />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="save-role"
                                >
                                    {role ? 'Save role' : 'Create role'}
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </div>
        </>
    );
}
