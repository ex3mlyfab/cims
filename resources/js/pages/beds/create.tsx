import BedForm from '@/components/bed-form';
import { create, index } from '@/routes/beds';
import type { WardOption } from '@/types/bed';

/**
 * CIMA-045: install a bed in a ward (Facility Management).
 */
export default function CreateBed({ wards }: { wards: WardOption[] }) {
    return (
        <BedForm
            title="Add bed"
            description="Install a physical bed in a ward and set its initial state"
            wards={wards}
        />
    );
}

CreateBed.layout = {
    breadcrumbs: [
        {
            title: 'Add bed',
            href: create(),
        },
    ],
};
