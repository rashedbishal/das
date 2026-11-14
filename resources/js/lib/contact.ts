export const FALLBACK_CONTACT = {
    phone: '01761158577',
    telephone: '01761158577',
    email: 'afazad@gmail.com',
    address: 'Business address to be confirmed',
    hours: 'Business hours available on request',
};

export function resolvePhone(
    whatsapp: {
        enabled: boolean;
        phone: string | null;
    },
    contactPhone: string | null = null,
): { display: string; tel: string } {
    if (contactPhone?.trim()) {
        return {
            display: contactPhone,
            tel: contactPhone.replace(/[^\d+]/g, ''),
        };
    }

    if (whatsapp.enabled && whatsapp.phone) {
        return { display: `+${whatsapp.phone}`, tel: `+${whatsapp.phone}` };
    }

    return { display: FALLBACK_CONTACT.phone, tel: FALLBACK_CONTACT.telephone };
}
