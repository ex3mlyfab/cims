import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import UserForm from '@/components/user-form';
import { index } from '@/routes/users';
import type { RoleOption, WardOption } from '@/types/user';

/**
 * CIMA-042: edit user (Administration module). Only name/username/email/
 * roles/ward assignments are editable here — passwords flow through the
 * reset-link/broker.
 */
export default function EditUser({
    user,
    roleOptions,
    wardOptions,
}: {
    user: {
        id: string;
        name: string;
        username: string;
        email: string;
        roles: string[];
        wards: string[];
    };
    roleOptions: RoleOption[];
    wardOptions: WardOption[];
}) {
    return (
        <UserForm
            title={`Edit user: ${user.name}`}
            description="Change the account details and the roles it holds"
            action={UserController.update.form({ user: user.id })}
            user={user}
            roleOptions={roleOptions}
            wardOptions={wardOptions}
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
