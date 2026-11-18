/**
 * Helpers for the product form's repeatable lists (highlights, specifications,
 * options). Each row carries a stable `key` so removing or moving one doesn't
 * shift focus or typed text into its neighbour.
 */
let nextKey = 0;

export function keyed<T extends object>(row: T): T & { key: number } {
    return { ...row, key: nextKey++ };
}

/** Swaps a row with its neighbour; `offset` is -1 (up) or 1 (down). */
export function moveRow<T>(rows: T[], index: number, offset: number): void {
    const target = index + offset;

    if (target < 0 || target >= rows.length) {
        return;
    }

    [rows[index], rows[target]] = [rows[target], rows[index]];
}
