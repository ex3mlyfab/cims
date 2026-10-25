import { Head, router, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import PatientController from '@/actions/App/Http/Controllers/Clinical/PatientController';
import Heading from '@/components/heading';
import PatientCreateModal from '@/components/patient-create-modal';
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
import { index } from '@/routes/patients';
import { useState } from 'react';
import type { PatientRecord } from '@/types/patient';

/**
 * CIMA-050: patient registry (Clinical Operations) — search-first module.
 * Lookup by fullname or patient id; when nothing matches, the inline
 * "Register patient" modal (fullname, patient id, sex, age) closes the
 * gap. Search results are capped server-side; the list is sorted by name.
 */
export default function PatientsIndex({
    search_query = '',
    patients,
}: {
    search_query?: string;
    patients: PatientRecord[];
}) {
    const { permissions = [] } = usePage().props;

    const canCreate = permissions.includes('patients.create');

    const [search, setSearch] = useState(search_query);
    const [modalOpen, setModalOpen] = useState(false);

    const submitSearch = (event: React.FormEvent) => {
        event.preventDefault();

        router.get(index.url(), { q: search }, { preserveState: true });
    };

    const noMatch = search_query !== '' && patients.length === 0;

    return (
        <>
            <Head title="Patients" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Patients"
                        description="Hospital patient registry — search before creating"
                    />
                    {canCreate && (
                        <Button
                            data-test="open-create-patient"
                            onClick={() => setModalOpen(true)}
                        >
                            Register patient
                        </Button>
                    )}
                </div>

                <form
                    className="flex max-w-md items-center gap-2"
                    onSubmit={submitSearch}
                    data-test="patient-search"
                >
                    <Input
                        id="q"
                        name="q"
                        autoFocus
                        placeholder="Search fullname or patient ID…"
                        maxLength={120}
                        defaultValue={search_query}
                        onChange={(e) => setSearch(e.target.value)}
                        data-test="patient-search-input"
                    />
                    <Button type="submit" variant="secondary">
                        Search
                    </Button>
                    {search_query !== '' && (
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => {
                                setSearch('');
                                router.get(
                                    index.url(),
                                    {},
                                    { preserveState: true },
                                );
                            }}
                        >
                            Clear
                        </Button>
                    )}
                </form>

                {noMatch && canCreate ? (
                    <div
                        className="flex flex-wrap items-center gap-4 rounded-lg border border-border bg-card p-4"
                        data-test="patient-no-match"
                    >
                        <div className="flex-1">
                            <p className="font-medium">
                                No matches for “{search_query}”
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Check for duplicate records first — if this
                                person definitely is not in the list, register
                                them now.
                            </p>
                        </div>
                        <Button
                            data-test="register-no-match"
                            onClick={() => setModalOpen(true)}
                        >
                            Register patient
                        </Button>
                    </div>
                ) : null}

                <div className="rounded-lg border border-border bg-card">
                    {patients.length === 0 && !noMatch ? (
                        <p
                            className="p-4 text-sm text-muted-foreground"
                            data-test="patient-list-empty"
                        >
                            No patients in the registry yet.
                        </p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-40">
                                        Patient ID
                                    </TableHead>
                                    <TableHead>Fullname</TableHead>
                                    <TableHead className="w-28">
                                        Sex
                                    </TableHead>
                                    <TableHead className="w-20">
                                        Age
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {patients.map((patient) => (
                                    <TableRow key={patient.id}>
                                        <TableCell
                                            className="font-medium"
                                            data-test="patient-code"
                                        >
                                            {patient.patient_code}
                                        </TableCell>
                                        <TableCell data-test="patient-fullname-cell">
                                            {patient.fullname}
                                        </TableCell>
                                        <TableCell data-test="patient-sex">
                                            <Badge variant="outline">
                                                {patient.sex_label}
                                            </Badge>
                                        </TableCell>
                                        <TableCell data-test="patient-age">
                                            {patient.age}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </div>

                <PatientCreateModal
                    open={modalOpen}
                    onOpenChange={setModalOpen}
                    initialFullname={noMatch && !looksLikeCode(search_query) ? search_query : ''}
                    initialPatientCode={noMatch && looksLikeCode(search_query) ? search_query : ''}
                />
            </div>
        </>
    );
}

/**
 * "Register patient" prefill heuristic (CIMA-050): a search term with
 * digits and no spaces looks like the patient-ID format rather than a
 * fullname, so it lands in the patient-ID field of the modal instead.
 */
function looksLikeCode(term: string): boolean {
    return /\d/.test(term) && !/\s/.test(term);
}

PatientsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Patients',
            href: index(),
        },
    ],
};
