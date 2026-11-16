import { page } from '@inertiajs/svelte';

/**
 * Whether the signed-in admin holds any of the given permission slugs.
 *
 * This only hides UI the policies would refuse anyway; the server stays the
 * source of truth. Reactive when read inside templates or `$derived`.
 */
export function can(...slugs: string[]): boolean {
    const held = page.props.auth?.permissions ?? [];

    return slugs.some((slug) => held.includes(slug));
}
