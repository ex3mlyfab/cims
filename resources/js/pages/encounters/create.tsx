import { useEffect, useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown, UserRoundPlus } from 'lucide-react';
import InputError from '@/components/input-error';
import PatientCreateModal, {
    type PatientOption,
} from '@/components/patient-create-modal';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

type PatientSummary = PatientOption | null;

export default function CreateEncounter({
    patient: initialPatient,
    wards,
    teams,
    treatment_statuses,
    available_beds,
}: {
    patient: PatientSummary;
    wards: Array<{ id: string; code: string; name: string }>;
    teams: Array<{ id: string; name: string }>;
    treatment_statuses: Array<{ id: string; label: string; slug: string }>;
    available_beds: Array<{
        id: string;
        ward_id: string;
        ward_code: string;
        number: string;
        label: string;
    }>;
}) {
    const { permissions } = usePage().props;
    const [patient, setPatient] = useState<PatientSummary>(initialPatient);
    const [comboboxOpen, setComboboxOpen] = useState(false);
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [results, setResults] = useState<PatientOption[]>([]);
    const [searching, setSearching] = useState(false);
    const [searchError, setSearchError] = useState('');
    const canCreatePatient = permissions.includes('patients.create');
    const form = useForm({
        patient_id: initialPatient?.id ?? '',
        ward_id: '',
        bed_id: '',
        team_id: '',
        treatment_status_id: '',
        notes: '',
    });
    const wardBeds = available_beds.filter(
        (bed) => bed.ward_id === form.data.ward_id,
    );

    const selectPatient = (selectedPatient: PatientOption) => {
        setPatient(selectedPatient);
        form.setData('patient_id', selectedPatient.id);
        form.clearErrors('patient_id');
    };

    const submitAdmission = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post('/encounters');
    };

    useEffect(() => {
        const term = search.trim();
        if (term.length < 2) {
            setResults([]);
            setSearchError('');
            setSearching(false);
            return;
        }

        const controller = new AbortController();
        const timeout = window.setTimeout(async () => {
            setSearching(true);
            setSearchError('');

            try {
                const response = await fetch(
                    `/encounters/patient-search?q=${encodeURIComponent(term)}`,
                    {
                        headers: { Accept: 'application/json' },
                        signal: controller.signal,
                    },
                );

                if (!response.ok) {
                    throw new Error(
                        'Patient search is unavailable. Try again.',
                    );
                }

                const data = (await response.json()) as {
                    patients: PatientOption[];
                };
                setResults(data.patients);
            } catch (error) {
                if (
                    error instanceof DOMException &&
                    error.name === 'AbortError'
                ) {
                    return;
                }

                setSearchError(
                    error instanceof Error
                        ? error.message
                        : 'Patient search is unavailable. Try again.',
                );
                setResults([]);
            } finally {
                if (!controller.signal.aborted) {
                    setSearching(false);
                }
            }
        }, 250);

        return () => {
            window.clearTimeout(timeout);
            controller.abort();
        };
    }, [search]);

    const createPatientFromSearch = () => {
        setComboboxOpen(false);
        setCreateModalOpen(true);
    };

    return (
        <div className="space-y-6">
            <div className="rounded-xl border bg-card p-6 shadow-sm">
                <h1 className="text-2xl font-semibold">Create admission</h1>
                <p className="mt-2 text-sm text-muted-foreground">
                    Select the patient, ward, bed, managing team and initial
                    treatment status.
                </p>
            </div>

            <form
                onSubmit={submitAdmission}
                className="space-y-6 rounded-xl border bg-card p-6 shadow-sm"
            >
                <div>
                    <label
                        className="mb-2 block text-sm font-medium"
                        htmlFor="patient-picker"
                    >
                        Patient
                    </label>
                    <Popover open={comboboxOpen} onOpenChange={setComboboxOpen}>
                        <PopoverTrigger asChild>
                            <Button
                                id="patient-picker"
                                type="button"
                                variant="outline"
                                role="combobox"
                                aria-expanded={comboboxOpen}
                                className="h-auto min-h-10 w-full justify-between px-3 py-2 text-left font-normal"
                            >
                                {patient ? (
                                    <span className="min-w-0">
                                        <span className="block truncate font-medium">
                                            {patient.fullname}
                                        </span>
                                        <span className="block truncate text-xs text-muted-foreground">
                                            {patient.patient_code} ·{' '}
                                            {patient.sex} · {patient.age}
                                        </span>
                                    </span>
                                ) : (
                                    <span className="text-muted-foreground">
                                        Search by patient name or ID…
                                    </span>
                                )}
                                <ChevronsUpDown className="ml-2 size-4 shrink-0 opacity-50" />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent
                            className="w-[--radix-popover-trigger-width] p-0"
                            align="start"
                        >
                            <Command shouldFilter={false}>
                                <CommandInput
                                    placeholder="Type a name or patient ID…"
                                    value={search}
                                    onValueChange={setSearch}
                                />
                                <CommandList>
                                    {searchError ? (
                                        <CommandEmpty className="text-destructive">
                                            {searchError}
                                        </CommandEmpty>
                                    ) : searching ? (
                                        <CommandEmpty>
                                            Searching patients…
                                        </CommandEmpty>
                                    ) : results.length === 0 ? (
                                        <CommandEmpty>
                                            {search.trim().length < 2
                                                ? 'Enter at least 2 characters to search.'
                                                : canCreatePatient
                                                  ? 'No matching patient found.'
                                                  : 'No match found. Ask a patient-registry user to register them.'}
                                        </CommandEmpty>
                                    ) : null}
                                    {results.length > 0 && (
                                        <CommandGroup heading="Patients">
                                            {results.map((option) => (
                                                <CommandItem
                                                    key={option.id}
                                                    value={option.id}
                                                    onSelect={() => {
                                                        selectPatient(option);
                                                        setComboboxOpen(false);
                                                        setSearch('');
                                                    }}
                                                >
                                                    <Check
                                                        className={cn(
                                                            'size-4',
                                                            patient?.id ===
                                                                option.id
                                                                ? 'opacity-100'
                                                                : 'opacity-0',
                                                        )}
                                                    />
                                                    <span className="min-w-0">
                                                        <span className="block truncate font-medium">
                                                            {option.fullname}
                                                        </span>
                                                        <span className="block truncate text-xs text-muted-foreground">
                                                            {
                                                                option.patient_code
                                                            }{' '}
                                                            · {option.sex} ·{' '}
                                                            {option.age}
                                                        </span>
                                                    </span>
                                                </CommandItem>
                                            ))}
                                        </CommandGroup>
                                    )}
                                    {search.trim().length >= 2 &&
                                        results.length === 0 &&
                                        !searching &&
                                        !searchError &&
                                        canCreatePatient && (
                                            <CommandGroup>
                                                <CommandItem
                                                    value="create-patient"
                                                    onSelect={
                                                        createPatientFromSearch
                                                    }
                                                >
                                                    <UserRoundPlus className="size-4" />
                                                    Register “{search.trim()}”
                                                </CommandItem>
                                            </CommandGroup>
                                        )}
                                </CommandList>
                            </Command>
                        </PopoverContent>
                    </Popover>
                    {canCreatePatient && (
                        <p className="mt-2 text-xs text-muted-foreground">
                            If the patient is not registered, search first then
                            choose Register.
                        </p>
                    )}
                    <InputError
                        className="mt-2"
                        message={form.errors.patient_id}
                    />
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <label className="mb-2 block text-sm font-medium">
                            Ward
                        </label>
                        <select
                            value={form.data.ward_id}
                            onChange={(event) => {
                                form.setData((data) => ({
                                    ...data,
                                    ward_id: event.target.value,
                                    bed_id: '',
                                }));
                                form.clearErrors('ward_id', 'bed_id');
                            }}
                            aria-invalid={!!form.errors.ward_id}
                            className="w-full rounded-md border border-input bg-background px-3 py-2"
                        >
                            <option value="">Select ward</option>
                            {wards.map((ward) => (
                                <option key={ward.id} value={ward.id}>
                                    {ward.code} · {ward.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={form.errors.ward_id} />
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium">
                            Bed
                        </label>
                        <select
                            value={form.data.bed_id}
                            onChange={(event) =>
                                form.setData('bed_id', event.target.value)
                            }
                            disabled={
                                !form.data.ward_id || wardBeds.length === 0
                            }
                            aria-invalid={!!form.errors.bed_id}
                            className="w-full rounded-md border border-input bg-background px-3 py-2"
                        >
                            <option value="">
                                {!form.data.ward_id
                                    ? 'Select a ward first'
                                    : wardBeds.length === 0
                                      ? 'No available beds in this ward'
                                      : 'Select bed'}
                            </option>
                            {wardBeds.map((bed) => (
                                <option key={bed.id} value={bed.id}>
                                    {bed.label}
                                </option>
                            ))}
                        </select>
                        <InputError message={form.errors.bed_id} />
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <label className="mb-2 block text-sm font-medium">
                            Managing team
                        </label>
                        <select
                            value={form.data.team_id}
                            onChange={(event) =>
                                form.setData('team_id', event.target.value)
                            }
                            aria-invalid={!!form.errors.team_id}
                            className="w-full rounded-md border border-input bg-background px-3 py-2"
                        >
                            <option value="">Select team</option>
                            {teams.map((team) => (
                                <option key={team.id} value={team.id}>
                                    {team.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={form.errors.team_id} />
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium">
                            Initial treatment status
                        </label>
                        <select
                            value={form.data.treatment_status_id}
                            onChange={(event) =>
                                form.setData(
                                    'treatment_status_id',
                                    event.target.value,
                                )
                            }
                            aria-invalid={!!form.errors.treatment_status_id}
                            className="w-full rounded-md border border-input bg-background px-3 py-2"
                        >
                            <option value="">Select status</option>
                            {treatment_statuses.map((status) => (
                                <option key={status.id} value={status.id}>
                                    {status.label}
                                </option>
                            ))}
                        </select>
                        <InputError message={form.errors.treatment_status_id} />
                    </div>
                </div>

                <div>
                    <label className="mb-2 block text-sm font-medium">
                        Notes
                    </label>
                    <textarea
                        rows={4}
                        value={form.data.notes}
                        onChange={(event) =>
                            form.setData('notes', event.target.value)
                        }
                        aria-invalid={!!form.errors.notes}
                        className="w-full rounded-md border border-input bg-background px-3 py-2"
                    />
                    <InputError message={form.errors.notes} />
                </div>

                <div className="flex justify-end gap-3">
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="rounded-md bg-primary px-4 py-2 text-primary-foreground"
                    >
                        {form.processing ? 'Admitting…' : 'Admit patient'}
                    </button>
                </div>
            </form>

            <PatientCreateModal
                open={createModalOpen}
                onOpenChange={setCreateModalOpen}
                initialFullname={
                    looksLikePatientCode(search) ? '' : search.trim()
                }
                initialPatientCode={
                    looksLikePatientCode(search) ? search.trim() : ''
                }
                onCreated={selectPatient}
            />
        </div>
    );
}

function looksLikePatientCode(term: string): boolean {
    return /\d/.test(term) && !/\s/.test(term);
}

CreateEncounter.layout = {
    breadcrumbs: [
        { title: 'Patients', href: '/patients' },
        { title: 'Create admission', href: '/encounters/create' },
    ],
};
