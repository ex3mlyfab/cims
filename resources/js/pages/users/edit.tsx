import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import UserForm from '@/components/user-form';
import { index } from '@/routes/users';
import type { RoleOption } from '@/types/user';

/**
 * CIMA-042: edit user (Administration module). Only name/email/roles are
 * editable here — passwords flow through the reset-link/broker.
 */
export default function EditUser({
    user,
    roleOptions,
}: {
    user: { id: string; name: string; email: string; roles: string[] };
    roleOptions: RoleOption[];
}) {
    return (
        <UserForm
            title={`Edit user: ${user.name}`}
            description="Change the account details and the roles it holds"
            action={UserController.update.form({ user: user.id })}
            user={user}
            roleOptions={roleOptions}
        />
    );
}

EditUser.layout = {
    breadcrumbs: [
        {
            title: 'User management',
            href: index(),
        },
    ],
};
