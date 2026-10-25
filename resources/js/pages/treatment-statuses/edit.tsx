import TreatmentStatusController from '@/actions/App/Http/Controllers/Clinical/TreatmentStatusController';
import TreatmentStatusForm from '@/components/treatment-status-form';
import type { TreatmentStatusRecord } from '@/types/treatment-status';

/**
 * CIMA-052: edit an existing status.
 */
export default function EditTreatmentStatus({
    status,
}: {
    status: TreatmentStatusRecord;
}) {
    return (
        <TreatmentStatusForm
            title="Edit treatment status"
            description="Update the label, slug, ordering or active state"
            status={status}
        />
    );
}

EditTreatmentStatus.layout = {
    breadcrumbs: [
        {
            title: 'Treatment statuses',
            href: TreatmentStatusController.index.url(),
        },
        {
            title: 'Edit status',
            href: '#',
        },
    ],
};
