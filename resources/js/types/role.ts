/** CIMA-041: role registry records for the roles list UI. */
export type RoleRecord = {
    id: string;
    name: string;
    permissionsCount: number;
    usersCount: number;
    superAdmin: boolean;
};

/** A single registry permission shown in role forms. */
export type PermissionOption = {
    id: string;
    name: string;
};

/** Registry permissions grouped by module prefix (e.g. `beds.*`). */
export type GroupedPermissions = Record<string, PermissionOption[]>;
