import WardController from '@/actions/App/Http/Controllers/Facility/WardController';
import WardForm from '@/components/ward-form';
import { create } from '@/routes/wards';

/**
 * CIMA-044: add a ward (Facility Management).
 */
export default function CreateWard() {
    return (
        <WardForm
            title="Add ward"
            description="Create a ward with its code and declared bed capacity"
        />
    );
}

CreateWard.layout = {
    breadcrumbs: [
        {
            title: 'Add ward',
            href: create(),
        },
    ],
};
