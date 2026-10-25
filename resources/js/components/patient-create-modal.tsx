import { useForm } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import PatientController from '@/actions/App/Http/Controllers/Clinical/PatientController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from 'sonner';

export type PatientOption = {
    id: string;
    patient_code: string;
    fullname: string;
    sex: string;
    age: number;
};

/**
 * CIMA-050: the inline "create patient" modal of the search-first module.
 * Fields are exactly fullname, patient id, sex and age — the "no match"
 * path of the admission-record flow. The attribute classes mirror the
 * other module forms (status selects use native elements).
 */
export default function PatientCreateModal({
    open,
    onOpenChange,
    onCreated,
    initialFullname = '',
    initialPatientCode = '',
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onCreated?: (patient: PatientOption) => void;
    /** Prefill incoming search term (the "no match" path). */
    initialFullname?: string;
    initialPatientCode?: string;
}) {
    const [isCreating, setIsCreating] = useState(false);
    const form = useForm({
        fullname: initialFullname,
        patient_code: initialPatientCode,
        sex: 'female',
        age: '',
    });

    useEffect(() => {
        if (open) {
            form.setData({
                fullname: initialFullname,
                patient_code: initialPatientCode,
                sex: 'female',
                age: '',
            });
            form.clearErrors();
        }
    }, [open, initialFullname, initialPatientCode]);

    const submit = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!onCreated) {
            form.post(PatientController.store.url(), {
                preserveScroll: true,
                onSuccess: () => onOpenChange(false),
            });
            return;
        }

        form.clearErrors();
        setIsCreating(true);

        try {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content');
            const response = await fetch(PatientController.store.url(), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
                body: JSON.stringify(form.data),
            });

            const result = await response.json();

            if (response.status === 422 && result.errors) {
                Object.entries(
                    result.errors as Record<string, string[]>,
                ).forEach(([field, messages]) => {
                    if (messages[0]) {
                        form.setError(
                            field as keyof typeof form.data,
                            messages[0],
                        );
                    }
                });
                return;
            }

            if (!response.ok || !result.patient) {
                throw new Error(
                    'Patient registration failed. Please try again.',
                );
            }

            onCreated(result.patient as PatientOption);
            onOpenChange(false);
        } catch (error) {
            toast.error(
                error instanceof Error
                    ? error.message
                    : 'Patient registration failed. Please try again.',
            );
        } finally {
            setIsCreating(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle data-test="create-patient-modal-title">
                        Register patient
                    </DialogTitle>
                    <DialogDescription>
                        No match found — add the patient to the registry.
                        Existing admission details follow in the next step.
                    </DialogDescription>
                </DialogHeader>

                <form
                    onSubmit={submit}
                    className="space-y-6"
                    data-test="create-patient-form"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="fullname">Fullname</Label>
                        <Input
                            id="fullname"
                            name="fullname"
                            required
                            maxLength={120}
                            placeholder="e.g. Amina Yusuf"
                            value={form.data.fullname}
                            onChange={(event) =>
                                form.setData('fullname', event.target.value)
                            }
                            autoFocus
                            data-test="patient-fullname"
                        />
                        <InputError message={form.errors.fullname} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="patient_code">Patient ID</Label>
                        <Input
                            id="patient_code"
                            name="patient_code"
                            required
                            maxLength={30}
                            placeholder="e.g. P-000123"
                            value={form.data.patient_code}
                            onChange={(event) =>
                                form.setData('patient_code', event.target.value)
                            }
                            data-test="patient-code"
                        />
                        <InputError message={form.errors.patient_code} />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="sex">Sex</Label>
                            <select
                                id="sex"
                                name="sex"
                                required
                                value={form.data.sex}
                                onChange={(event) =>
                                    form.setData('sex', event.target.value)
                                }
                                className="mt-1 block rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                data-test="patient-sex"
                            >
                                <option value="female">Female</option>
                                <option value="male">Male</option>
                                <option value="other">Other</option>
                            </select>
                            <InputError message={form.errors.sex} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="age">Age</Label>
                            <Input
                                id="age"
                                name="age"
                                type="number"
                                required
                                min={0}
                                max={130}
                                step={1}
                                value={form.data.age}
                                onChange={(event) =>
                                    form.setData('age', event.target.value)
                                }
                                data-test="patient-age"
                            />
                            <InputError message={form.errors.age} />
                        </div>
                    </div>

                    <div className="flex items-center justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            disabled={form.processing || isCreating}
                            data-test="save-patient"
                        >
                            Register patient
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
