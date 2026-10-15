// The CMS settings table has a `general` group with contact_email,
// contact_phone and address fields, but only `whatsapp.phone` (via the
// shared `contactWidgets` Inertia prop) is actually exposed publicly today.
// These have no real backend source yet, so they stay as placeholders until
// a public endpoint/shared prop exposes them.
export const FALLBACK_CONTACT = {
    phone: '+44 20 7946 0000',
    telephone: '+442079460000',
    email: 'sales@dentora.example',
    address: 'Business address to be confirmed',
    hours: 'Mon–Fri, 9:00–17:00 (illustrative)',
};

export function resolvePhone(whatsapp: {
    enabled: boolean;
    phone: string | null;
}): { display: string; tel: string } {
    if (whatsapp.enabled && whatsapp.phone) {
        return { display: `+${whatsapp.phone}`, tel: `+${whatsapp.phone}` };
    }

    return { display: FALLBACK_CONTACT.phone, tel: FALLBACK_CONTACT.telephone };
}
