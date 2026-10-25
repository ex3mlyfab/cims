import WardForm from '@/components/ward-form';
import { index } from '@/routes/wards';
import type { WardRecord } from '@/types/ward';

/**
 * CIMA-044: edit a ward (Facility Management).
 */
export default function EditWard({ ward }: { ward: WardRecord }) {
    return (
        <WardForm
            title={`Edit ward: ${ward.name}`}
            description="Update the ward code, name or declared bed capacity"
            ward={ward}
        />
    );
}

EditWard.layout = {
    breadcrumbs: [
        {
            title: 'Wards',
            href: index(),
        },
    ],
};
