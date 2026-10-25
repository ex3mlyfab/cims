import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import type { RoleOption } from '@/types/user';

/**
 * CIMA-042: shared create/edit user form (Inertia Form + Wayfinder actions).
 * `user` is undefined for create. Admin-created users get a password-set
 * link by email — no admin-chosen passwords.
 */
export default function UserForm({
    title,
    description,
    action,
    user,
    roleOptions,
}: {
    title: string;
    description: string;
    action: {
        action: string;
        method: 'post';
    };
    user?: { name: string; email: string; roles: string[] };
    roleOptions: RoleOption[];
}) {
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
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <div className="space-y-6 rounded-lg border border-border bg-card p-4">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Full name</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block"
                                    defaultValue={user?.name ?? ''}
                                    name="name"
                                    required
                                    maxLength={100}
                                    placeholder="e.g. Amara Okafor"
                                    data-test="user-name"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block"
                                    defaultValue={user?.email ?? ''}
                                    name="email"
                                    required
                                    maxLength={255}
                                    placeholder="staff@hospital.example"
                                    data-test="user-email"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.email}
                                />
                            </div>

                            <Separator />

                            <div className="space-y-3">
                                <div>
                                    <Label>Roles</Label>

                                    <p className="text-xs text-muted-foreground">
                                        {user
                                            ? 'The user holds every checked role.'
                                            : 'The user receives a link to set their own password after creation.'}
                                    </p>
                                </div>

                                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    {roleOptions.map((option) => (
                                        <label
                                            key={option.id}
                                            className="flex items-center gap-2 text-sm has-[:focus-visible]:rounded-md has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring"
                                            data-test={`user-role-${option.name}`}
                                        >
                                            <input
                                                type="checkbox"
                                                name="roles[]"
                                                value={option.name}
                                                defaultChecked={
                                                    user?.roles.includes(
                                                        option.name,
                                                    ) ?? false
                                                }
                                                className="size-4 accent-primary"
                                            />
                                            <span className="font-mono text-xs">
                                                {option.name}
                                            </span>
                                        </label>
                                    ))}
                                </div>

                                <InputError
                                    className="mt-2"
                                    message={
                                        errors['roles.0'] ?? errors.roles
                                    }
                                />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="save-user"
                                >
                                    {user ? 'Save user' : 'Create user'}
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </div>
        </>
    );
}
