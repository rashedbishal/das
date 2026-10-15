export type User = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    user_type: 'admin' | 'customer';
    role_id: number | null;
    is_active: boolean;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
};

export type ContactWidgets = {
    whatsapp: {
        enabled: boolean;
        phone: string | null;
        message: string | null;
    };
    messenger: { enabled: boolean; page_url: string | null };
};
