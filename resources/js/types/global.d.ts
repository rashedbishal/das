import type { OpeningHoursDay } from '@/lib/business';
import type { Auth, ContactWidgets } from '@/types/auth';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(
            pattern: string,
            options?: { eager?: boolean },
        ) => Record<string, T>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            googleSignIn: boolean;
            loginMode: 'email' | 'phone' | 'both';
            contactWidgets: ContactWidgets;
            siteSettings: {
                name: string;
                logo: string | null;
                contactPhone: string | null;
                contactEmail: string | null;
                address: string | null;
                addressCity: string | null;
                addressRegion: string | null;
                postalCode: string | null;
                mapUrl: string | null;
                latitude: string | number | null;
                longitude: string | number | null;
                openingHours: OpeningHoursDay[];
                socialLinks: Record<string, string>;
                currency: string;
                deliveryNote: string | null;
            };
            seo: {
                defaultTitle: string | null;
                defaultDescription: string | null;
                canonicalUrl: string;
                appUrl: string;
                googleSiteVerification: string | null;
                bingSiteVerification: string | null;
            };
            [key: string]: unknown;
        };
    }
}
