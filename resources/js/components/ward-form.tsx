import { Form, Head } from '@inertiajs/react';
import WardController from '@/actions/App/Http/Controllers/Facility/WardController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { WardRecord } from '@/types/ward';

/**
 * CIMA-044: shared create/edit form for wards (code, name, capacity).
 */
export default function WardForm({
    title,
    description,
    ward,
}: {
    title: string;
    description: string;
    ward?: WardRecord;
}) {
    const action = ward
        ? WardController.update.form(ward.id)
        : WardController.store.form();

    return (
        <>
            <Head title={title} />

            <h1 className="sr-only">{title}</h1>

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading title={title} description={description} />

                <Form
                    {...action}
                    options={{
                        preserveScroll: true,
                    }}
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <div className="space-y-6 rounded-lg border border-border bg-card p-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="code">Code</Label>

                                    <Input
                                        id="code"
                                        className="mt-1 block"
                                        defaultValue={ward?.code ?? ''}
                                        name="code"
                                        required
                                        maxLength={24}
                                        placeholder="e.g. ED-A"
                                        data-test="ward-code"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.code}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="capacity">
                                        Capacity (beds)
                                    </Label>

                                    <Input
                                        id="capacity"
                                        type="number"
                                        min={1}
                                        max={999}
                                        step={1}
                                        className="mt-1 block"
                                        defaultValue={
                                            ward?.capacity === undefined
                                                ? ''
                                                : ward.capacity
                                        }
                                        name="capacity"
                                        required
                                        placeholder="e.g. 24"
                                        data-test="ward-capacity"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.capacity}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block"
                                    defaultValue={ward?.name ?? ''}
                                    name="name"
                                    required
                                    maxLength={120}
                                    placeholder="e.g. Emergency department observation ward"
                                    data-test="ward-name"
                                />

                                <InputError className="mt-2" message={errors.name} />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="save-ward"
                                >
                                    {ward ? 'Save ward' : 'Add ward'}
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </div>
        </>
    );
}
