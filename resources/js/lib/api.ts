function getCookie(name: string): string | null {
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
    const token = getCookie('XSRF-TOKEN');

    const response = await fetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...(token ? { 'X-XSRF-TOKEN': token } : {}),
            ...options.headers,
        },
    });

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
