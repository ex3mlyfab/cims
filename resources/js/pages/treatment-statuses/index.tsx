import { Form, Head, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import TreatmentStatusController from '@/actions/App/Http/Controllers/Clinical/TreatmentStatusController';
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
import type { TreatmentStatusRecord } from '@/types/treatment-status';

/**
 * CIMA-052: treatment workflow status registry.
 */
export default function TreatmentStatusesIndex({
    statuses,
}: {
    statuses: TreatmentStatusRecord[];
}) {
    const { permissions = [] } = usePage().props;
    const canManage = permissions.includes('statuses.manage');

    return (
        <>
            <Head title="Treatment statuses" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Treatment statuses"
                        description="Configurable workflow labels for the clinical status axis"
                    />
                    {canManage && (
                        <Button asChild data-test="create-treatment-status">
                            <Link href={TreatmentStatusController.create.url()}>
                                Add status
                            </Link>
                        </Button>
                    )}
                </div>

                <div className="rounded-lg border border-border bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Label</TableHead>
                                <TableHead>Slug</TableHead>
                                <TableHead className="w-24">Order</TableHead>
                                <TableHead className="w-28">Active</TableHead>
                                <TableHead className="w-48 text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {statuses.map((status) => (
                                <TableRow key={status.id}>
                                    <TableCell className="font-medium">
                                        {status.label}
                                    </TableCell>
                                    <TableCell className="font-mono text-sm">
                                        {status.slug}
                                    </TableCell>
                                    <TableCell>{status.sort_order}</TableCell>
                                    <TableCell>
                                        {status.is_active ? (
                                            <Badge data-test={`status-active-${status.id}`}>
                                                Active
                                            </Badge>
                                        ) : (
                                            <Badge variant="outline" className="text-muted-foreground">
                                                Inactive
                                            </Badge>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {canManage ? (
                                            <div className="flex justify-end gap-1">
                                                <Button variant="ghost" size="sm" asChild>
                                                    <Link href={TreatmentStatusController.edit.url(status.id)}>
                                                        Edit
                                                    </Link>
                                                </Button>
                                                <Form
                                                    {...TreatmentStatusController.destroy.form(status.id)}
                                                    options={{ preserveScroll: true }}
                                                    data-test={`delete-treatment-status-${status.id}`}
                                                >
                                                    {({ processing }) => (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            disabled={processing}
                                                            className="text-destructive hover:text-destructive"
                                                        >
                                                            Delete
                                                        </Button>
                                                    )}
                                                </Form>
                                            </div>
                                        ) : (
                                            <span className="text-xs text-muted-foreground">
                                                —
                                            </span>
                                        )}
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

TreatmentStatusesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Treatment statuses',
            href: TreatmentStatusController.index.url(),
        },
    ],
};
