import { Form, Head } from '@inertiajs/react';
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

/**
 * CIMA-050: the inline "create patient" modal of the search-first module.
 * Fields are exactly fullname, patient id, sex and age — the "no match"
 * path of the admission-record flow. The attribute classes mirror the
 * other module forms (status selects use native elements).
 */
export default function PatientCreateModal({
    open,
    onOpenChange,
    initialFullname = '',
    initialPatientCode = '',
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** Prefill incoming search term (the "no match" path). */
    initialFullname?: string;
    initialPatientCode?: string;
}) {
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

                <Form
                    {...PatientController.store.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                    data-test="create-patient-form"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="fullname">Fullname</Label>

                                <Input
                                    id="fullname"
                                    name="fullname"
                                    className="mt-1 block"
                                    required
                                    maxLength={120}
                                    placeholder="e.g. Amina Yusuf"
                                    defaultValue={initialFullname}
                                    autoFocus
                                    data-test="patient-fullname"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.fullname}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="patient_code">
                                    Patient ID
                                </Label>

                                <Input
                                    id="patient_code"
                                    name="patient_code"
                                    className="mt-1 block"
                                    required
                                    maxLength={30}
                                    placeholder="e.g. P-000123"
                                    defaultValue={initialPatientCode}
                                    data-test="patient-code"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.patient_code}
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="sex">Sex</Label>

                                    <select
                                        id="sex"
                                        name="sex"
                                        required
                                        defaultValue="female"
                                        className="mt-1 block rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                                        data-test="patient-sex"
                                    >
                                        <option value="female">Female</option>
                                        <option value="male">Male</option>
                                        <option value="other">Other</option>
                                    </select>

                                    <InputError
                                        className="mt-2"
                                        message={errors.sex}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="age">Age</Label>

                                    <Input
                                        id="age"
                                        name="age"
                                        type="number"
                                        className="mt-1 block"
                                        required
                                        min={0}
                                        max={130}
                                        step={1}
                                        data-test="patient-age"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.age}
                                    />
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
                                    disabled={processing}
                                    data-test="save-patient"
                                >
                                    Register patient
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
