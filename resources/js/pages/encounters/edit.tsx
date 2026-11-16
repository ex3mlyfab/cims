type PatientSummary = {
    id: string;
    patient_code: string;
    fullname: string;
    sex: string;
    age: number | null;
};

export default function EditEncounter({
    encounter,
    patient,
    wards,
    teams,
    treatment_statuses,
    available_beds,
}: {
    encounter: {
        id: string;
        patient_id: string;
        ward_id: string;
        bed_id: string;
        team_ids: string[];
        treatment_status_id: string;
        notes: string | null;
        arrived_at: string | null;
    };
    patient: PatientSummary;
    wards: Array<{ id: string; code: string; name: string }>;
    teams: Array<{ id: string; name: string }>;
    treatment_statuses: Array<{ id: string; label: string; slug: string }>;
    available_beds: Array<{ id: string; ward_id: string; ward_code: string; number: string; label: string }>;
}) {
    return (
        <div className="space-y-6">
            <div className="rounded-xl border bg-card p-6 shadow-sm">
                <h1 className="text-2xl font-semibold">Update admission</h1>
                <p className="mt-2 text-sm text-muted-foreground">
                    Adjust the admission ward, bed, managing teams, treatment status or notes while preserving the allocation history.
                </p>
            </div>

            <form method="post" action={`/encounters/${encounter.id}`} className="space-y-6 rounded-xl border bg-card p-6 shadow-sm">
                <input type="hidden" name="_token" value={typeof document !== 'undefined' ? (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '') : ''} />
                <input type="hidden" name="_method" value="put" />
                <input type="hidden" name="patient_id" value={patient.id} />

                <div className="rounded-lg border bg-muted/50 p-4">
                    <p className="text-xs uppercase tracking-wide text-muted-foreground">Patient</p>
                    <p className="mt-2 font-medium">{patient.fullname}</p>
                    <p className="text-sm text-muted-foreground">{patient.patient_code} · {patient.sex} · {patient.age ?? 'Age unknown'}</p>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <label className="mb-2 block text-sm font-medium">Ward</label>
                        <select name="ward_id" defaultValue={encounter.ward_id} className="w-full rounded-md border border-input bg-background px-3 py-2">
                            <option value="">Select ward</option>
                            {wards.map((ward) => (
                                <option key={ward.id} value={ward.id}>{ward.code} · {ward.name}</option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium">Bed</label>
                        <select name="bed_id" defaultValue={encounter.bed_id} className="w-full rounded-md border border-input bg-background px-3 py-2">
                            <option value="">Select bed</option>
                            {available_beds.map((bed) => (
                                <option key={bed.id} value={bed.id}>{bed.label}</option>
                            ))}
                        </select>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <div className="mb-2 flex items-center justify-between gap-3">
                            <span className="block text-sm font-medium">Managing teams</span>
                            <span className="text-xs text-muted-foreground">Optional · select all that apply</span>
                        </div>
                        <div className="max-h-44 space-y-1 overflow-y-auto rounded-md border border-input bg-background p-2">
                            {teams.length === 0 ? (
                                <p className="px-2 py-1 text-sm text-muted-foreground">No teams configured.</p>
                            ) : (
                                teams.map((team) => (
                                    <label key={team.id} className="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-muted/60">
                                        <input
                                            type="checkbox"
                                            name="team_ids[]"
                                            value={team.id}
                                            defaultChecked={encounter.team_ids.includes(team.id)}
                                            className="size-4 rounded border-input accent-primary"
                                        />
                                        <span>{team.name}</span>
                                    </label>
                                ))
                            )}
                        </div>
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium">Treatment status</label>
                        <select name="treatment_status_id" defaultValue={encounter.treatment_status_id} className="w-full rounded-md border border-input bg-background px-3 py-2">
                            <option value="">Select status</option>
                            {treatment_statuses.map((status) => (
                                <option key={status.id} value={status.id}>{status.label}</option>
                            ))}
                        </select>
                    </div>
                </div>

                <div>
                    <label className="mb-2 block text-sm font-medium">Notes</label>
                    <textarea name="notes" defaultValue={encounter.notes ?? ''} rows={4} className="w-full rounded-md border border-input bg-background px-3 py-2" />
                </div>

                <div className="flex justify-end gap-3">
                    <button type="submit" className="rounded-md bg-primary px-4 py-2 text-primary-foreground">
                        Save changes
                    </button>
                </div>
            </form>

            <form method="post" action={`/encounters/${encounter.id}/discharge`} className="rounded-xl border border-destructive/30 bg-destructive/5 p-4">
                <input type="hidden" name="_token" value={typeof document !== 'undefined' ? (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '') : ''} />
                <input type="hidden" name="_method" value="delete" />
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <p className="font-medium text-destructive">Discharge patient</p>
                        <p className="text-sm text-muted-foreground">Closes the active bed allocation, returns the bed to available and marks the admission as complete.</p>
                    </div>
                    <button type="submit" className="rounded-md bg-destructive px-4 py-2 text-destructive-foreground">
                        Discharge
                    </button>
                </div>
            </form>
        </div>
    );
}

EditEncounter.layout = {
    breadcrumbs: [
        { title: 'Patients', href: '/patients' },
        { title: 'Update admission', href: '/encounters' },
    ],
};
