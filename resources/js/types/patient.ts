/**
 * CIMA-050: patient registry types (search-first index + create modal).
 */

export type SexValue = 'male' | 'female' | 'other';

export type PatientRecord = {
    id: string;
    patient_code: string;
    fullname: string;
    sex: SexValue;
    sex_label: string;
    age: number;
};

export const SEX_LABELS: Record<SexValue, string> = {
    male: 'Male',
    female: 'Female',
    other: 'Other',
};
