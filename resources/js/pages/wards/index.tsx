import { Form, Head, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import WardController from '@/actions/App/Http/Controllers/Facility/WardController';
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
import { create, edit, index } from '@/routes/wards';
import type { WardRecord } from '@/types/ward';

/**
 * CIMA-044: wards (Facility Management group) — CRUD over code, name and
 * declared bed capacity.
 */
export default function WardsIndex({ wards }: { wards: WardRecord[] }) {
    const { permissions = [] } = usePage().props;

    const canManage = permissions.includes('wards.manage');
    const canViewDisplay = permissions.includes('display.manage');

    return (
        <>
            <Head title="Wards" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Wards"
                        description="Physical areas that hold beds, management and capacity tracking"
                    />
                    {canManage && (
                        <Button asChild data-test="create-ward">
                            <Link href={create()}>Add ward</Link>
                        </Button>
                    )}
                </div>

                <div className="rounded-lg border border-border bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-32">Code</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead className="w-32">
                                    Capacity
                                </TableHead>
                                <TableHead className="w-48 text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {wards.map((ward) => (
                                <TableRow key={ward.id}>
                                    <TableCell>
                                        <Badge
                                            variant="outline"
                                            data-test="ward-code"
                                        >
                                            {ward.code}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="font-medium">
                                        {ward.name}
                                    </TableCell>
                                    <TableCell data-test="ward-capacity">
                                        {ward.capacity}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex justify-end gap-1">
                                            {canViewDisplay && ward.display_url && (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={ward.display_url}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        data-test={`display-ward-${ward.id}`}
                                                    >
                                                        Display
                                                    </Link>
                                                </Button>
                                            )}
                                            {canManage ? (
                                                <>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={edit(ward.id)}
                                                            data-test={`edit-ward-${ward.id}`}
                                                        >
                                                            Edit
                                                        </Link>
                                                    </Button>
                                                    <Form
                                                        {...WardController.destroy.form(
                                                            ward.id,
                                                        )}
                                                        options={{
                                                            preserveScroll: true,
                                                        }}
                                                        data-test={`delete-ward-${ward.id}`}
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
                                            ) : canViewDisplay ? null : (
                                                <span className="text-xs text-muted-foreground">
                                                    —
                                                </span>
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

WardsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Wards',
            href: index(),
        },
    ],
};
