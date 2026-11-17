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

/** One run of days with the same hours: "Saturday to Thursday" / "10:00 AM – 7:00 PM". */
export type HoursGroup = { days: string; time: string };

/**
 * The week with consecutive days that share the same hours merged:
 * { days: "Saturday to Thursday", time: "10:00 AM – 7:00 PM" },
 * { days: "Friday", time: "Closed" }. Built from the CMS settings, so it
 * stays right when the hours change. Days left unset in the CMS are skipped
 * and break a run. Used by the footer and the contact page.
 */
export function groupedHours(
    hours: OpeningHoursDay[] | null | undefined,
): HoursGroup[] {
    const groups: { first: OpeningHoursDay['day']; last: OpeningHoursDay['day']; count: number; time: string }[] = [];
    let previousIndex = -2;

    for (const entry of orderedHours(hours)) {
        const index = WEEKDAYS.indexOf(entry.day);
        const time = entry.closed
            ? 'Closed'
            : `${formatTime(entry.opens)} – ${formatTime(entry.closes)}`;
        const current = groups.at(-1);

        if (current && current.time === time && index === previousIndex + 1) {
            current.last = entry.day;
            current.count++;
        } else {
            groups.push({ first: entry.day, last: entry.day, count: 1, time });
        }

        previousIndex = index;
    }

    return groups.map(({ first, last, count, time }) => {
        const days =
            count === 1
                ? dayLabel(first)
                : count === 2
                  ? `${dayLabel(first)} & ${dayLabel(last)}`
                  : `${dayLabel(first)} to ${dayLabel(last)}`;

        return { days, time };
    });
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
