import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

type Occupant = {
    id: string;
    patient_id_masked: string;
    initials: string;
    bed_number: string;
    sex: string;
    age: number;
    duration: string;
    treatment_status: string;
    team: string;
    occupancy_status: string;
};

export default function WardDisplay({
    ward,
    occupants = [],
    generated_at,
}: {
    ward: { id: string; code: string; name: string };
    occupants?: Occupant[];
    generated_at: string;
}) {
    const [updatedAt, setUpdatedAt] = useState(generated_at);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const [isStale, setIsStale] = useState(false);

    useEffect(() => {
        const refresh = () => {
            setIsRefreshing(true);
            setIsStale(false);

            router.reload({
                only: ['ward', 'occupants', 'generated_at'],
                onSuccess: (page) => {
                    const nextUpdatedAt = page.props.generated_at as string | undefined;
                    if (nextUpdatedAt) {
                        setUpdatedAt(nextUpdatedAt);
                    }
                    setIsRefreshing(false);
                },
                onError: () => {
                    setIsStale(true);
                    setIsRefreshing(false);
                },
            });
        };

        const interval = window.setInterval(refresh, 30000);

        return () => window.clearInterval(interval);
    }, []);

    return (
        <>
            <Head title={`${ward.code} display`} />

            <div className="min-h-screen bg-slate-950 p-6 text-slate-50">
                <div className="mb-6 flex items-end justify-between gap-4 border-b border-slate-700 pb-4">
                    <div>
                        <p className="text-xs uppercase tracking-[0.2em] text-slate-400">Ward display</p>
                        <h1 className="mt-2 text-4xl font-bold">{ward.code} · {ward.name}</h1>
                    </div>
                    <div className="text-right text-sm text-slate-300">
                        <div className="flex items-center justify-end gap-2">
                            <span>Updated</span>
                            {isRefreshing && <span className="text-xs text-cyan-300">Refreshing…</span>}
                            {isStale && <span className="text-xs text-amber-300">Stale</span>}
                        </div>
                        <div className="font-medium text-slate-100">{new Date(updatedAt).toLocaleString()}</div>
                    </div>
                </div>

                {occupants.length === 0 ? (
                    <div className="rounded-lg border border-slate-700 bg-slate-900 p-10 text-center text-slate-300">
                        No occupied beds in this ward.
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-lg border border-slate-700 bg-slate-900 shadow-2xl shadow-slate-950/40">
                        <table className="min-w-full border-collapse text-left text-sm">
                            <thead className="bg-slate-800 text-slate-200">
                                <tr>
                                    <th className="px-4 py-3 font-semibold">Patient ID</th>
                                    <th className="px-4 py-3 font-semibold">Initials</th>
                                    <th className="px-4 py-3 font-semibold">Bed</th>
                                    <th className="px-4 py-3 font-semibold">Sex</th>
                                    <th className="px-4 py-3 font-semibold">Age</th>
                                    <th className="px-4 py-3 font-semibold">Duration</th>
                                    <th className="px-4 py-3 font-semibold">Treatment</th>
                                    <th className="px-4 py-3 font-semibold">Team</th>
                                    <th className="px-4 py-3 font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {occupants.map((occupant) => (
                                    <tr key={occupant.id} className="border-t border-slate-700 bg-slate-900/80">
                                        <td className="px-4 py-3 font-medium text-slate-100">{occupant.patient_id_masked}</td>
                                        <td className="px-4 py-3 text-slate-100">{occupant.initials}</td>
                                        <td className="px-4 py-3 text-slate-200">{occupant.bed_number}</td>
                                        <td className="px-4 py-3 text-slate-200">{occupant.sex}</td>
                                        <td className="px-4 py-3 text-slate-200">{occupant.age}</td>
                                        <td className="px-4 py-3 text-slate-200">{occupant.duration}</td>
                                        <td className="px-4 py-3 text-slate-200">{occupant.treatment_status}</td>
                                        <td className="px-4 py-3 text-slate-200">{occupant.team}</td>
                                        <td className="px-4 py-3 text-amber-300">{occupant.occupancy_status}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}
