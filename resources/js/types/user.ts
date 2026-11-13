/** CIMA-042: user registry records for the users list UI. */
export type UserRecord = {
    id: string;
    name: string;
    username: string;
    email: string | null;
    roles: string[];
    wards?: string[];
    deactivated: boolean;
};

/** Assignable role options in user forms. */
export type RoleOption = {
    id: string;
    name: string;
};

/** Assignable ward options in user forms. */
export type WardOption = {
    id: string;
    code: string;
    name: string;
};
