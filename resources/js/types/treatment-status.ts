/** CIMA-052: configurable treatment workflow statuses. */
export type TreatmentStatusRecord = {
    id: string;
    slug: string;
    label: string;
    sort_order: number;
    is_active: boolean;
};
