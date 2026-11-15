/**
 * Serialises structured data into a JSON-LD <script> tag. "<" is escaped so a value
 * containing "</script>" can't break out of the tag.
 */
export function jsonLdScript(data: Record<string, unknown>): string {
    const json = JSON.stringify(data).replace(/</g, '\\u003c');

    return `<script type="application/ld+json">${json}</script>`;
}

/** Turns a site-relative path into an absolute URL; absolute URLs pass through. */
export function absoluteUrl(appUrl: string, path: string): string {
    return /^https?:\/\//.test(path)
        ? path
        : `${appUrl}${path.startsWith('/') ? '' : '/'}${path}`;
}
