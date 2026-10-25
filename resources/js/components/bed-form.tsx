import { Form, Head } from '@inertiajs/react';
import BedController from '@/actions/App/Http/Controllers/Facility/BedController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BedRecord, WardOption } from '@/types/bed';

/**
 * CIMA-045: shared create/edit form for beds. Creating picks the ward
 * (wards dropdown); editing keeps the ward fixed. Status offers the manual
 * bed states only — `occupied` is reserved for the allocation workflow.
 * CIMA-047: history lives on its own `/beds/{bed}` page, not here.
 */
export default function BedForm({
    title,
    description,
    bed,
    wards,
}: {
    title: string;
    description: string;
    bed?: BedRecord;
    /** Ward options; required when creating (ward assignment). */
    wards?: WardOption[];
}) {
    const action = bed
        ? BedController.update.form(bed.id)
        : BedController.store.form();

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
                            {bed ? (
                                <div className="grid gap-2">
                                    <Label htmlFor="ward-code">Ward</Label>

                                    <Input
                                        id="ward-code"
                                        className="mt-1 block"
                                        defaultValue={`${bed.ward.code} — ${bed.ward.name}`}
                                        disabled
                                        data-test="bed-ward"
                                    />
                                </div>
                            ) : (
                                <div className="grid gap-2">
                                    <Label htmlFor="ward_id">Ward</Label>

                                    <select
                                        id="ward_id"
                                        name="ward_id"
                                        required
                                        defaultValue=""
                                        className="mt-1 block rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                        data-test="bed-ward"
                                    >
                                        <option value="" disabled>
                                            Select a ward…
                                        </option>
                                        {wards?.map((ward) => (
                                            <option key={ward.id} value={ward.id}>
                                                {ward.code} — {ward.name}
                                            </option>
                                        ))}
                                    </select>

                                    <InputError
                                        className="mt-2"
                                        message={errors.ward_id}
                                    />
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="number">Bed number</Label>

                                <Input
                                    id="number"
                                    className="mt-1 block"
                                    defaultValue={bed?.number ?? ''}
                                    name="number"
                                    required
                                    maxLength={24}
                                    placeholder="e.g. A-01"
                                    data-test="bed-number"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.number}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>

                                <select
                                    id="status"
                                    name="status"
                                    required
                                    defaultValue={bed?.status ?? 'available'}
                                    className="mt-1 block rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                    data-test="bed-status"
                                >
                                    <option value="available">
                                        Available
                                    </option>
                                    <option value="cleaning">Cleaning</option>
                                    <option value="out_of_service">
                                        Out of service
                                    </option>
                                </select>

                                <InputError
                                    className="mt-2"
                                    message={errors.status}
                                />

                                <p className="text-xs text-muted-foreground">
                                    Occupied is managed through the bed
                                    allocation workflow.
                                </p>
                            </div>

                            <div className="flex items-center gap-4">
                                <Button disabled={processing} data-test="save-bed">
                                    {bed ? 'Save bed' : 'Add bed'}
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </div>
        </>
    );
}
