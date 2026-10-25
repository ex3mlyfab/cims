import { Form, Head, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import BedController from '@/actions/App/Http/Controllers/Facility/BedController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { create, edit, index, show } from '@/routes/beds';
import { BED_STATUS_LABELS, type BedRecord, type BedStatus } from '@/types/bed';

/**
 * CIMA-045: beds (Facility Management group) — CRUD over per-ward bed
 * numbers and the manual bed states. Status badges always carry a text
 * label (cima-ui-design rule).
 */
const STATUS_BADGE_VARIANT: Record<BedStatus, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    available: 'default',
    occupied: 'default',
    cleaning: 'secondary',
    out_of_service: 'destructive',
};

export default function BedsIndex({ beds }: { beds: BedRecord[] }) {
    const { permissions = [] } = usePage().props;

    const canManage = permissions.includes('beds.manage');

    return (
        <>
            <Head title="Beds" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Beds"
                        description="Physical bed units per ward with manual lifecycle states"
                    />
                    {canManage && (
                        <Button asChild data-test="create-bed">
                            <Link href={create()}>Add bed</Link>
                        </Button>
                    )}
                </div>

                <div className="rounded-lg border border-border bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-32">Ward</TableHead>
                                <TableHead>Bed number</TableHead>
                                <TableHead className="w-40">Status</TableHead>
                                <TableHead className="w-48 text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {beds.map((bed) => (
                                <TableRow key={bed.id}>
                                    <TableCell>
                                        <Badge
                                            variant="outline"
                                            data-test="bed-ward"
                                        >
                                            {bed.ward.code}
                                        </Badge>
                                    </TableCell>
                                    <TableCell
                                        className="font-medium"
                                        data-test="bed-number"
                                    >
                                        {bed.number}
                                    </TableCell>
                                    <TableCell data-test="bed-status">
                                        <Badge
                                            variant={
                                                STATUS_BADGE_VARIANT[
                                                    bed.status
                                                ]
                                            }
                                        >
                                            {bed.status_label ??
                                                BED_STATUS_LABELS[bed.status]}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-1">
                                            {/* CIMA-047: history page,
                                                readable with beds.view. */}
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={show(bed.id)}
                                                    data-test={`history-bed-${bed.id}`}
                                                >
                                                    History
                                                </Link>
                                            </Button>
                                            {canManage && (
                                                <>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={edit(bed.id)}
                                                            data-test={`edit-bed-${bed.id}`}
                                                        >
                                                            Edit
                                                        </Link>
                                                    </Button>
                                                    <Form
                                                        {...BedController.destroy.form(
                                                            bed.id,
                                                        )}
                                                        options={{
                                                            preserveScroll: true,
                                                        }}
                                                        data-test={`delete-bed-${bed.id}`}
                                                    >
                                                        {({ processing }) => (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                disabled={
                                                                    processing
                                                                }
                                                                className="text-destructive hover:text-destructive"
                                                            >
                                                                Delete
                                                            </Button>
                                                        )}
                                                    </Form>
                                                </>
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}

BedsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Beds',
            href: index(),
        },
    ],
};
