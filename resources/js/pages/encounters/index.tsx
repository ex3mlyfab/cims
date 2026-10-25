import { Head, Link, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Plus } from 'lucide-react';

type AdmissionRecord = {
    id: string;
    patient: { id: string; fullname: string; patient_code: string };
    ward: { id: string; code: string; name: string };
    team: { id: string; name: string };
    treatment_status: { id: string; label: string };
    arrived_at: string | null;
    discharged_at: string | null;
    is_active: boolean;
    notes: string | null;
};

export default function EncountersIndex({
    query = '',
    filters = {},
    encounters = [],
    wards = [],
    teams = [],
    treatment_statuses = [],
    pagination,
}: {
    query?: string;
    filters?: {
        ward_id?: string;
        team_id?: string;
        treatment_status_id?: string;
        from?: string;
        to?: string;
    };
    encounters?: AdmissionRecord[];
    wards?: Array<{ id: string; code: string; name: string }>;
    teams?: Array<{ id: string; name: string }>;
    treatment_statuses?: Array<{ id: string; label: string }>;
    pagination: {
        current_page: number;
        last_page: number;
        total: number;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}) {
    const submitSearch = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        const params = new URLSearchParams();

        const q = (formData.get('q') ?? '').toString().trim();
        if (q) params.set('q', q);

        const wardId = (formData.get('ward_id') ?? '').toString();
        if (wardId) params.set('ward_id', wardId);

        const teamId = (formData.get('team_id') ?? '').toString();
        if (teamId) params.set('team_id', teamId);

        const statusId = (formData.get('treatment_status_id') ?? '').toString();
        if (statusId) params.set('treatment_status_id', statusId);

        const from = (formData.get('from') ?? '').toString();
        if (from) params.set('from', from);

        const to = (formData.get('to') ?? '').toString();
        if (to) params.set('to', to);

        router.get('/encounters', Object.fromEntries(params.entries()));
    };

    return (
        <>
            <Head title="Admissions" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Admissions"
                        description="Current and historical admissions across all wards"
                    />
                    <Button asChild>
                        <Link href="/encounters/create">
                            <Plus className="mr-2 h-4 w-4" />
                            New admission
                        </Link>
                    </Button>
                </div>

                <form onSubmit={submitSearch} className="rounded-lg border bg-card p-4">
                    <div className="grid gap-3 md:grid-cols-6">
                        <div className="md:col-span-2">
                            <label className="mb-2 block text-sm font-medium">Patient</label>
                            <Input name="q" defaultValue={query} placeholder="Search fullname or ID" />
                        </div>
                        <div>
                            <label className="mb-2 block text-sm font-medium">Ward</label>
                            <select name="ward_id" defaultValue={filters.ward_id ?? ''} className="w-full rounded-md border border-input bg-background px-3 py-2">
                                <option value="">All wards</option>
                                {wards.map((ward) => (
                                    <option key={ward.id} value={ward.id}>{ward.code} · {ward.name}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="mb-2 block text-sm font-medium">Team</label>
                            <select name="team_id" defaultValue={filters.team_id ?? ''} className="w-full rounded-md border border-input bg-background px-3 py-2">
                                <option value="">All teams</option>
                                {teams.map((team) => (
                                    <option key={team.id} value={team.id}>{team.name}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="mb-2 block text-sm font-medium">Status</label>
                            <select name="treatment_status_id" defaultValue={filters.treatment_status_id ?? ''} className="w-full rounded-md border border-input bg-background px-3 py-2">
                                <option value="">All statuses</option>
                                {treatment_statuses.map((status) => (
                                    <option key={status.id} value={status.id}>{status.label}</option>
                                ))}
                            </select>
                        </div>
                        <div className="flex items-end gap-2">
                            <Button type="submit" variant="secondary">Filter</Button>
                            <Button type="button" variant="ghost" onClick={() => router.get('/encounters')}>Reset</Button>
                        </div>
                    </div>

                    <div className="mt-3 grid gap-3 md:grid-cols-2">
                        <div>
                            <label className="mb-2 block text-sm font-medium">Arrived from</label>
                            <Input type="date" name="from" defaultValue={filters.from ?? ''} />
                        </div>
                        <div>
                            <label className="mb-2 block text-sm font-medium">Arrived to</label>
                            <Input type="date" name="to" defaultValue={filters.to ?? ''} />
                        </div>
                    </div>
                </form>

                <div className="rounded-lg border border-border bg-card">
                    {encounters.length === 0 ? (
                        <p className="p-4 text-sm text-muted-foreground">No admissions match the current filters.</p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Patient</TableHead>
                                    <TableHead>Ward</TableHead>
                                    <TableHead>Team</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Arrived</TableHead>
                                    <TableHead>Outcome</TableHead>
                                    <TableHead className="w-20">Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {encounters.map((encounter) => (
                                    <TableRow key={encounter.id}>
                                        <TableCell>
                                            <div className="font-medium">{encounter.patient.fullname}</div>
                                            <div className="text-xs text-muted-foreground">{encounter.patient.patient_code}</div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="font-medium">{encounter.ward.code}</div>
                                            <div className="text-xs text-muted-foreground">{encounter.ward.name}</div>
                                        </TableCell>
                                        <TableCell>{encounter.team.name}</TableCell>
                                        <TableCell>
                                            <Badge variant={encounter.is_active ? 'default' : 'secondary'}>
                                                {encounter.treatment_status.label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {encounter.arrived_at ? new Date(encounter.arrived_at).toLocaleString() : '—'}
                                        </TableCell>
                                        <TableCell>
                                            {encounter.is_active ? (
                                                <span className="text-emerald-600">Active</span>
                                            ) : (
                                                <span className="text-slate-500">Discharged</span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Link href={`/encounters/${encounter.id}/edit`} className="text-sm font-medium text-primary">
                                                Edit
                                            </Link>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </div>

                {pagination && pagination.last_page > 1 && (
                    <div className="flex flex-wrap items-center gap-2">
                        {pagination.links.map((link, index) => (
                            <Button
                                key={`${link.label}-${index}`}
                                type="button"
                                size="sm"
                                variant={link.active ? 'default' : 'outline'}
                                onClick={() => {
                                    if (link.url) {
                                        const url = new URL(link.url, window.location.origin);
                                        router.get(url.pathname + url.search, {});
                                    }
                                }}
                                disabled={!link.url}
                                className={link.active ? 'pointer-events-none' : ''}
                            >
                                {link.label.replace('&laquo;', '').replace('&raquo;', '')}
                            </Button>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

EncountersIndex.layout = {
    breadcrumbs: [
        { title: 'Admissions', href: '/encounters' },
    ],
};
