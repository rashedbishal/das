/**
 * The sales phone number: the CMS contact phone, else the WhatsApp number.
 * Null when neither is set, so callers hide their call links instead of
 * showing a number that belongs to nobody (no hard-coded fallbacks).
 */
export function resolvePhone(
    whatsapp: {
        enabled: boolean;
        phone: string | null;
    },
    contactPhone: string | null = null,
): { display: string; tel: string } | null {
    if (contactPhone?.trim()) {
        return {
            display: contactPhone,
            tel: contactPhone.replace(/[^\d+]/g, ''),
        };
    }

    if (whatsapp.enabled && whatsapp.phone) {
        return { display: `+${whatsapp.phone}`, tel: `+${whatsapp.phone}` };
    }

    return null;
}
