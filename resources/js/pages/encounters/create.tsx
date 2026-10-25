type PatientSummary = {
    id: string;
    patient_code: string;
    fullname: string;
    sex: string;
    age: number;
};

export default function CreateEncounter({
    patient,
    wards,
    teams,
    treatment_statuses,
    available_beds,
}: {
    patient: PatientSummary | null;
    wards: Array<{ id: string; code: string; name: string }>;
    teams: Array<{ id: string; name: string }>;
    treatment_statuses: Array<{ id: string; label: string; slug: string }>;
    available_beds: Array<{ id: string; ward_id: string; ward_code: string; number: string; label: string }>;
}) {
    return (
        <div className="space-y-6">
            <div className="rounded-xl border bg-card p-6 shadow-sm">
                <h1 className="text-2xl font-semibold">Create admission</h1>
                <p className="mt-2 text-sm text-muted-foreground">
                    Select the patient, ward, bed, managing team and initial treatment status.
                </p>
            </div>

            <form method="post" action="/encounters" className="space-y-6 rounded-xl border bg-card p-6 shadow-sm">
                <input type="hidden" name="_token" value={typeof document !== 'undefined' ? (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '') : ''} />
                {patient ? (
                    <div className="rounded-lg border bg-muted/50 p-4">
                        <p className="text-xs uppercase tracking-wide text-muted-foreground">Patient</p>
                        <p className="mt-2 font-medium">{patient.fullname}</p>
                        <p className="text-sm text-muted-foreground">{patient.patient_code} · {patient.sex} · {patient.age}</p>
                        <input type="hidden" name="patient_id" value={patient.id} />
                    </div>
                ) : (
                    <div>
                        <label className="mb-2 block text-sm font-medium">Patient</label>
                        <input
                            type="text"
                            name="patient_id"
                            placeholder="Patient ID"
                            className="w-full rounded-md border border-input bg-background px-3 py-2"
                        />
                    </div>
                )}

                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <label className="mb-2 block text-sm font-medium">Ward</label>
                        <select name="ward_id" className="w-full rounded-md border border-input bg-background px-3 py-2">
                            <option value="">Select ward</option>
                            {wards.map((ward) => (
                                <option key={ward.id} value={ward.id}>{ward.code} · {ward.name}</option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium">Bed</label>
                        <select name="bed_id" className="w-full rounded-md border border-input bg-background px-3 py-2">
                            <option value="">Select bed</option>
                            {available_beds.map((bed) => (
                                <option key={bed.id} value={bed.id}>{bed.label}</option>
                            ))}
                        </select>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <label className="mb-2 block text-sm font-medium">Managing team</label>
                        <select name="team_id" className="w-full rounded-md border border-input bg-background px-3 py-2">
                            <option value="">Select team</option>
                            {teams.map((team) => (
                                <option key={team.id} value={team.id}>{team.name}</option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="mb-2 block text-sm font-medium">Initial treatment status</label>
                        <select name="treatment_status_id" className="w-full rounded-md border border-input bg-background px-3 py-2">
                            <option value="">Select status</option>
                            {treatment_statuses.map((status) => (
                                <option key={status.id} value={status.id}>{status.label}</option>
                            ))}
                        </select>
                    </div>
                </div>

                <div>
                    <label className="mb-2 block text-sm font-medium">Notes</label>
                    <textarea name="notes" rows={4} className="w-full rounded-md border border-input bg-background px-3 py-2" />
                </div>

                <div className="flex justify-end gap-3">
                    <button type="submit" className="rounded-md bg-primary px-4 py-2 text-primary-foreground">
                        Admit patient
                    </button>
                </div>
            </form>
        </div>
    );
}

CreateEncounter.layout = {
    breadcrumbs: [
        { title: 'Patients', href: '/patients' },
        { title: 'Create admission', href: '/encounters/create' },
    ],
};
