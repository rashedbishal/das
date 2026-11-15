function getCookie(name: string): string | null {
    if (typeof document === 'undefined') {
        return null;
    }

    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));
    return match ? decodeURIComponent(match[1]) : null;
}

export class ApiError extends Error {
    status: number;
    errors: Record<string, string[]>;

    constructor(
        message: string,
        status: number,
        errors: Record<string, string[]> = {},
    ) {
        super(message);
        this.status = status;
        this.errors = errors;
    }
}

export async function apiFetch<T = unknown>(
    url: string,
    options: RequestInit = {},
): Promise<T | null> {
    if (typeof window === 'undefined') {
        // Server-side render: there is no browser session/cookie to send,
        // and relative URLs have no base. Skip the request — the client
        // re-fetches after hydration.
        return null;
    }

    const token = getCookie('XSRF-TOKEN');

    // Start from the caller's headers (a plain object, array or Headers instance)
    // and fill in the defaults they didn't set.
    const headers = new Headers(options.headers);
    headers.set('Accept', headers.get('Accept') ?? 'application/json');
    headers.set(
        'Content-Type',
        headers.get('Content-Type') ?? 'application/json',
    );

    if (token && !headers.has('X-XSRF-TOKEN')) {
        headers.set('X-XSRF-TOKEN', token);
    }

    const response = await fetch(url, { ...options, headers });

    if (!response.ok) {
        const body = await response.json().catch(() => ({}));
        throw new ApiError(
            body.message ?? 'Request failed',
            response.status,
            body.errors ?? {},
        );
    }

    if (response.status === 204) {
        return null;
    }

    return response.json() as Promise<T>;
}
