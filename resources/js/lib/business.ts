/** One weekday of the business's opening hours (Admin -> Settings -> General). */
export type OpeningHoursDay = {
    day:
        | 'monday'
        | 'tuesday'
        | 'wednesday'
        | 'thursday'
        | 'friday'
        | 'saturday'
        | 'sunday';
    closed: boolean;
    opens: string | null;
    closes: string | null;
};

export const WEEKDAYS: OpeningHoursDay['day'][] = [
    'saturday',
    'sunday',
    'monday',
    'tuesday',
    'wednesday',
    'thursday',
    'friday',
];

export function dayLabel(day: OpeningHoursDay['day']): string {
    return day.charAt(0).toUpperCase() + day.slice(1);
}

/** "09:00" -> "9:00 AM". */
export function formatTime(value: string | null): string {
    if (!value) return '';
    const [hours, minutes] = value.split(':').map(Number);
    const suffix = hours >= 12 ? 'PM' : 'AM';
    const hour12 = hours % 12 === 0 ? 12 : hours % 12;

    return `${hour12}:${String(minutes).padStart(2, '0')} ${suffix}`;
}

/** The days in display order (Saturday first, as in Bangladesh), skipping unset ones. */
export function orderedHours(
    hours: OpeningHoursDay[] | null | undefined,
): OpeningHoursDay[] {
    const byDay = new Map((hours ?? []).map((entry) => [entry.day, entry]));

    return WEEKDAYS.map((day) => byDay.get(day)).filter(
        (entry): entry is OpeningHoursDay =>
            entry !== undefined &&
            (entry.closed || (!!entry.opens && !!entry.closes)),
    );
}

/** schema.org OpeningHoursSpecification entries for open days. */
export function openingHoursSpecification(
    hours: OpeningHoursDay[] | null | undefined,
) {
    return orderedHours(hours)
        .filter((entry) => !entry.closed)
        .map((entry) => ({
            '@type': 'OpeningHoursSpecification',
            dayOfWeek: `https://schema.org/${dayLabel(entry.day)}`,
            opens: entry.opens,
            closes: entry.closes,
        }));
}
