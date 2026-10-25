import { Form, Head } from '@inertiajs/react';
import TreatmentStatusController from '@/actions/App/Http/Controllers/Clinical/TreatmentStatusController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { TreatmentStatusRecord } from '@/types/treatment-status';

/**
 * CIMA-052: shared create/edit form for the treatment workflow status registry.
 */
export default function TreatmentStatusForm({
    title,
    description,
    status,
}: {
    title: string;
    description: string;
    status?: TreatmentStatusRecord;
}) {
    const action = status
        ? TreatmentStatusController.update.form(status.id)
        : TreatmentStatusController.store.form();

    return (
        <>
            <Head title={title} />

            <h1 className="sr-only">{title}</h1>

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading title={title} description={description} />

                <Form
                    {...action}
                    options={{ preserveScroll: true }}
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <div className="space-y-6 rounded-lg border border-border bg-card p-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="label">Label</Label>
                                    <Input
                                        id="label"
                                        name="label"
                                        required
                                        maxLength={60}
                                        defaultValue={status?.label ?? ''}
                                        placeholder="e.g. Awaiting review"
                                        data-test="treatment-status-label"
                                    />
                                    <InputError
                                        className="mt-2"
                                        message={errors.label}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="slug">Slug</Label>
                                    <Input
                                        id="slug"
                                        name="slug"
                                        required
                                        maxLength={30}
                                        defaultValue={status?.slug ?? ''}
                                        placeholder="e.g. awaiting-review"
                                        data-test="treatment-status-slug"
                                    />
                                    <InputError
                                        className="mt-2"
                                        message={errors.slug}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="sort_order">Display order</Label>
                                    <Input
                                        id="sort_order"
                                        type="number"
                                        min={1}
                                        max={99}
                                        step={1}
                                        name="sort_order"
                                        required
                                        defaultValue={
                                            status?.sort_order ?? 10
                                        }
                                        placeholder="10"
                                        data-test="treatment-status-sort-order"
                                    />
                                    <InputError
                                        className="mt-2"
                                        message={errors.sort_order}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="is_active">Status</Label>
                                    <Select
                                        defaultValue={
                                            status?.is_active === false
                                                ? '0'
                                                : '1'
                                        }
                                        name="is_active"
                                        data-test="treatment-status-active"
                                    >
                                        <SelectTrigger id="is_active">
                                            <SelectValue placeholder="Choose status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="1">
                                                Active
                                            </SelectItem>
                                            <SelectItem value="0">
                                                Inactive
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        className="mt-2"
                                        message={errors.is_active}
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-4">
                                <Button disabled={processing} data-test="save-treatment-status">
                                    {status ? 'Save status' : 'Add status'}
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </div>
        </>
    );
}
