import TreatmentStatusController from '@/actions/App/Http/Controllers/Clinical/TreatmentStatusController';
import TreatmentStatusForm from '@/components/treatment-status-form';

/**
 * CIMA-052: add a treatment workflow status.
 */
export default function CreateTreatmentStatus() {
    return (
        <TreatmentStatusForm
            title="Add treatment status"
            description="Create a new label for the treatment workflow axis"
        />
    );
}

CreateTreatmentStatus.layout = {
    breadcrumbs: [
        {
            title: 'Treatment statuses',
            href: TreatmentStatusController.index.url(),
        },
        {
            title: 'Add status',
            href: TreatmentStatusController.create.url(),
        },
    ],
};
