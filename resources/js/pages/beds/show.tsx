import { Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
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
import { index } from '@/routes/beds';
import {
    BED_EVENT_LABELS,
    BED_STATUS_LABELS,
    type BedEventRecord,
    type BedRecord,
} from '@/types/bed';

/**
 * CIMA-047: dedicated bed history page (read-only projection over the
 * append-only bed_events ledger, CIMA-046). Newest first; every row shows
 * the labelled action, when it happened, the from/to details and the actor.
 */
export default function BedHistory({
    bed,
    history = [],
}: {
    bed: BedRecord;
    history?: BedEventRecord[];
}) {
    const title = `Bed history: ${bed.ward.code} · ${bed.number}`;

    return (
        <>
            <Head title={title} />

            <h1 className="sr-only">{title}</h1>

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title={title}
                        description="Append-only record of this bed's state changes"
                    />
                    <Button variant="ghost" asChild data-test="back-to-beds">
                        <Link href={index()}>Back to beds</Link>
                    </Button>
                </div>

                <div className="flex flex-wrap items-center gap-2 text-sm">
                    <Badge variant="outline" data-test="bed-ward">
                        {bed.ward.code}
                    </Badge>
                    <span
                        className="font-medium"
                        data-test="bed-number"
                    >
                        {bed.number}
                    </span>
                    <Badge data-test="bed-status">
                        {BED_STATUS_LABELS[bed.status]}
                    </Badge>
                </div>

                <div className="rounded-lg border border-border bg-card">
                    {history.length === 0 ? (
                        <p
                            className="p-4 text-sm text-muted-foreground"
                            data-test="bed-history-empty"
                        >
                            No history yet — changes to this bed (status moves,
                            number edits, and later allocations) will be
                            recorded here.
                        </p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-48">
                                        Event
                                    </TableHead>
                                    <TableHead>Details</TableHead>
                                    <TableHead className="w-40">
                                        Recorded by
                                    </TableHead>
                                    <TableHead className="w-56 text-right">
                                        When
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {history.map((event) => (
                                    <TableRow
                                        key={event.id}
                                        data-test="bed-event"
                                    >
                                        <TableCell className="font-medium">
                                            {BED_EVENT_LABELS[
                                                event.action
                                            ] ?? event.action}
                                        </TableCell>
                                        <TableCell
                                            data-test="bed-event-details"
                                        >
                                            {event.details
                                                ? Object.entries(
                                                      event.details,
                                                  )
                                                      .map(
                                                          ([key, value]) =>
                                                              `${key}: ${value}`,
                                                      )
                                                      .join(' · ')
                                                : '—'}
                                        </TableCell>
                                        <TableCell>
                                            {event.actor_name ?? 'System'}
                                        </TableCell>
                                        <TableCell className="text-right text-muted-foreground">
                                            {new Date(
                                                event.occurred_at,
                                            ).toLocaleString()}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </div>
            </div>
        </>
    );
}

BedHistory.layout = {
    breadcrumbs: [
        {
            title: 'Beds',
            href: index(),
        },
    ],
};
