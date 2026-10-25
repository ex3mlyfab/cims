/** CIMA-045: beds (Facility Management). */
export type BedStatus = 'available' | 'occupied' | 'cleaning' | 'out_of_service';

/**
 * Manual states — bed management may only set these; `occupied` is
 * reserved for the allocation workflow (CIMA-060).
 */
export const MANUAL_BED_STATUSES: BedStatus[] = [
    'available',
    'cleaning',
    'out_of_service',
];

export const BED_STATUS_LABELS: Record<BedStatus, string> = {
    available: 'Available',
    occupied: 'Occupied',
    cleaning: 'Cleaning',
    out_of_service: 'Out of service',
};

export type BedRecord = {
    id: string;
    number: string;
    status: BedStatus;
    status_label: string;
    ward: {
        id: string;
        code: string;
        name: string;
    };
};

/** CIMA-046: append-only bed history entry (bed_events). */
export type BedEventRecord = {
    id: string;
    action: string;
    occurred_at: string;
    actor_name: string | null;
    details: Record<string, string> | null;
};

export const BED_EVENT_LABELS: Record<string, string> = {
    'bed.created': 'Bed created',
    'bed.status_changed': 'Status changed',
    'bed.number_changed': 'Bed number changed',
};

export type WardOption = {
    id: string;
    code: string;
    name: string;
};
