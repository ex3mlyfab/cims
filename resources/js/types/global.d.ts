import type { Auth } from '@/types/auth';
import type { HospitalBranding } from '@/types/branding';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            branding: HospitalBranding;
            permissions: string[];
            [key: string]: unknown;
        };
    }
}
