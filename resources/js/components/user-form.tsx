import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import type { RoleOption, WardOption } from '@/types/user';

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
    wardOptions,
}: {
    title: string;
    description: string;
    action: {
        action: string;
        method: 'post';
    };
    user?: {
        name: string;
        username: string;
        email: string | null;
        roles: string[];
        wards: string[];
    };
    roleOptions: RoleOption[];
    wardOptions: WardOption[];
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
                                <Label htmlFor="username">Username</Label>

                                <Input
                                    id="username"
                                    className="mt-1 block"
                                    defaultValue={user?.username ?? ''}
                                    name="username"
                                    required
                                    maxLength={50}
                                    placeholder="e.g. a.okafor"
                                    pattern="[A-Za-z0-9._\-]+"
                                    autoComplete="username"
                                    autoCapitalize="none"
                                    spellCheck={false}
                                    data-test="user-username"
                                />

                                <p className="text-xs text-muted-foreground">
                                    Case-insensitive. Letters, numbers, dots,
                                    dashes and underscores; stored in lowercase.
                                </p>

                                <InputError
                                    className="mt-2"
                                    message={errors.username}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    Email address{' '}
                                    <span className="text-xs font-normal text-muted-foreground">
                                        (optional)
                                    </span>
                                </Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block"
                                    defaultValue={user?.email ?? ''}
                                    name="email"
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

                            <div className="grid gap-2">
                                <Label htmlFor="password">
                                    {user ? 'New password' : 'Password'}
                                </Label>

                                <PasswordInput
                                    id="password"
                                    className="mt-1 block"
                                    name="password"
                                    autoComplete="new-password"
                                    placeholder={
                                        user
                                            ? 'Leave blank to keep the current password'
                                            : 'Set an initial password'
                                    }
                                    data-test="user-password"
                                />

                                <p className="text-xs text-muted-foreground">
                                    {user
                                        ? 'Leave blank to keep the current password.'
                                        : 'Leave blank to email a password-set link instead — an email address is then required.'}
                                </p>

                                <InputError
                                    className="mt-2"
                                    message={errors.password}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>

                                <PasswordInput
                                    id="password_confirmation"
                                    className="mt-1 block"
                                    name="password_confirmation"
                                    autoComplete="new-password"
                                    placeholder="Repeat the password"
                                    data-test="user-password-confirmation"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Separator />

                            <div className="space-y-3">
                                <div>
                                    <Label>Roles</Label>

                                    <p className="text-xs text-muted-foreground">
                                        {user
                                            ? 'The user holds every checked role.'
                                            : 'A password-set link is emailed when no password is entered.'}
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
                                    message={errors['roles.0'] ?? errors.roles}
                                />
                            </div>

                            <Separator />

                            <div className="space-y-3">
                                <div>
                                    <Label>Assigned wards</Label>

                                    <p className="text-xs text-muted-foreground">
                                        Ward visibility and admissions are
                                        limited to the checked wards. Super
                                        admin and hospital administrator
                                        accounts always see every ward.
                                    </p>
                                </div>

                                {wardOptions.length === 0 ? (
                                    <p
                                        className="text-xs text-muted-foreground"
                                        data-test="user-wards-empty"
                                    >
                                        No wards exist yet — create them under
                                        Facility management.
                                    </p>
                                ) : (
                                    <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        {wardOptions.map((option) => (
                                            <label
                                                key={option.id}
                                                className="flex items-center gap-2 text-sm has-[:focus-visible]:rounded-md has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring"
                                                data-test={`user-ward-${option.code}`}
                                            >
                                                <input
                                                    type="checkbox"
                                                    name="wards[]"
                                                    value={option.id}
                                                    defaultChecked={
                                                        user?.wards.includes(
                                                            option.id,
                                                        ) ?? false
                                                    }
                                                    className="size-4 accent-primary"
                                                />
                                                <span className="flex items-baseline gap-2">
                                                    <span className="font-mono text-xs">
                                                        {option.code}
                                                    </span>
                                                    <span className="truncate">
                                                        {option.name}
                                                    </span>
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                )}

                                <InputError
                                    className="mt-2"
                                    message={errors['wards.0'] ?? errors.wards}
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
