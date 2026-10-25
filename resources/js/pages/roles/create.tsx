import RoleController from '@/actions/App/Http/Controllers/Admin/RoleController';
import RoleForm from '@/components/role-form';
import { create as createRole } from '@/routes/roles';
import type { GroupedPermissions } from '@/types/role';

/**
 * CIMA-041: create role (Administration module).
 */
export default function CreateRole({
    groupedPermissions,
}: {
    groupedPermissions: GroupedPermissions;
}) {
    return (
        <RoleForm
            title="New role"
            description="Create a role and pick the permissions it grants"
            action={RoleController.store.form()}
            groupedPermissions={groupedPermissions}
        />
    );
}

CreateRole.layout = {
    breadcrumbs: [
        {
            title: 'New role',
            href: createRole(),
        },
    ],
};
