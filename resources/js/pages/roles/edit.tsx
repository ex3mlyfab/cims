import RoleController from '@/actions/App/Http/Controllers/Admin/RoleController';
import RoleForm from '@/components/role-form';
import { index } from '@/routes/roles';
import type { GroupedPermissions } from '@/types/role';

/**
 * CIMA-041: edit role (Administration module). The Super Admin role is
 * policy-protected and never reaches this page.
 */
export default function EditRole({
    role,
    groupedPermissions,
}: {
    role: { id: string; name: string; permissions: string[] };
    groupedPermissions: GroupedPermissions;
}) {
    return (
        <RoleForm
            title={`Edit role: ${role.name}`}
            description="Change the role name and the permissions it grants"
            action={RoleController.update.form({ role: role.id })}
            role={role}
            groupedPermissions={groupedPermissions}
        />
    );
}

EditRole.layout = {
    breadcrumbs: [
        {
            title: 'Roles & permissions',
            href: index(),
        },
    ],
};
