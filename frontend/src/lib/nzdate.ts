// Date handling for the timelapse archive. All dates are plain Y-m-d calendar
// strings in Pacific/Auckland - never Date objects - so DST can never shift a
// calendar day. Lives in its own module so scripts/test-timelapse-dates.js can
// exercise the same code the page ships.

/** Longest span the timelapse player will load in one go. */
export const MAX_RANGE_DAYS = 7;

/** Current NZ date + HH:MM, via Intl - no date library needed. */
export function nzNow() {
  const p: Record<string, string> = {};
  new Intl.DateTimeFormat('en-GB', {
    timeZone: 'Pacific/Auckland',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
  })
    .formatToParts(new Date())
    .forEach((x) => (p[x.type] = x.value));
  return { date: `${p.year}-${p.month}-${p.day}`, time: `${p.hour}:${p.minute}` };
}

/** Calendar-day arithmetic on Y-m-d strings (UTC avoids any DST drift). */
export function addDays(date: string, n: number) {
  const t = new Date(`${date}T00:00:00Z`);
  t.setUTCDate(t.getUTCDate() + n);
  return t.toISOString().slice(0, 10);
}

export const isValidDate = (s: string) =>
  /^\d{4}-\d{2}-\d{2}$/.test(s) && addDays(s, 0) === s;

export type DateBounds = {
  /** First day with an archive. */
  start: string;
  /** Newest selectable day. */
  max: string;
  /** Day to use when the param is absent or unusable. */
  fallback: string;
};

/**
 * Parse `?date=YYYY-MM-DD` or `?date=YYYY-MM-DD_YYYY-MM-DD` into the list of
 * days to load. Reversed ranges are swapped, out-of-range days are dropped, and
 * anything unusable falls back to `bounds.fallback`.
 *
 * Out-of-range days still count against the MAX_RANGE_DAYS budget, so a range
 * starting before the archive does simply yields fewer days.
 */
export function selectedDates(raw: string | null, bounds: DateBounds): string[] {
  const inRange = (d: string) => d >= bounds.start && d <= bounds.max;
  const dates: string[] = [];

  if (raw) {
    const parts = raw.split('_').slice(0, 2).map((s) => s.trim());

    if (parts.length === 2 && isValidDate(parts[0]!) && isValidDate(parts[1]!)) {
      let [from, to] = parts as [string, string];
      if (from > to) [from, to] = [to, from];
      for (let cur = from, n = 0; cur <= to && n < MAX_RANGE_DAYS; cur = addDays(cur, 1), n++) {
        if (inRange(cur)) dates.push(cur);
      }
    } else if (isValidDate(parts[0]!) && inRange(parts[0]!)) {
      dates.push(parts[0]!);
    }
  }

  return dates.length ? dates : [bounds.fallback];
}
