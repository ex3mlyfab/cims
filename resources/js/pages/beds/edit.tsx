import BedForm from '@/components/bed-form';
import { index } from '@/routes/beds';
import type { BedRecord } from '@/types/bed';

/**
 * CIMA-045: edit a bed (number + manual status; the ward is fixed).
 * CIMA-047: history moved to its own `/beds/{bed}` page.
 */
export default function EditBed({ bed }: { bed: BedRecord }) {
    return (
        <BedForm
            title={`Edit bed: ${bed.ward.code} · ${bed.number}`}
            description="Update the bed number or move it through its manual states"
            bed={bed}
        />
    );
}

EditBed.layout = {
    breadcrumbs: [
        {
            title: 'Beds',
            href: index(),
        },
    ],
};
