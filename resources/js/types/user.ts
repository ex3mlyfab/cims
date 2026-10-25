/** CIMA-042: user registry records for the users list UI. */
export type UserRecord = {
    id: string;
    name: string;
    email: string;
    roles: string[];
    deactivated: boolean;
};

/** Assignable role options in user forms. */
export type RoleOption = {
    id: string;
    name: string;
};
