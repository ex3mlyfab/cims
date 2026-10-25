import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import UserForm from '@/components/user-form';
import { create as createUser } from '@/routes/users';
import type { RoleOption } from '@/types/user';

/**
 * CIMA-042: create user (Administration module). A password-set link is
 * emailed automatically; no admin-chosen passwords.
 */
export default function CreateUser({
    roleOptions,
}: {
    roleOptions: RoleOption[];
}) {
    return (
        <UserForm
            title="New user"
            description="Create a staff account and pick the roles it holds"
            action={UserController.store.form()}
            roleOptions={roleOptions}
        />
    );
}

CreateUser.layout = {
    breadcrumbs: [
        {
            title: 'New user',
            href: createUser(),
        },
    ],
};
