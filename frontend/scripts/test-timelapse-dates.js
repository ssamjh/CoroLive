// Self-check for the NZ date logic behind the timelapse page.
// Run: npm test  (from frontend/)
//
// Imports src/lib/nzdate.ts directly - Node strips the types - so the test
// exercises the same code the page bundles, rather than a copy of it.
import assert from 'node:assert';
import { MAX_RANGE_DAYS, addDays, isValidDate, nzNow, selectedDates } from '../src/lib/nzdate.ts';

// --- addDays -------------------------------------------------------------
assert.strictEqual(addDays('2026-08-19', 1), '2026-08-20');
assert.strictEqual(addDays('2026-01-01', -1), '2025-12-31');
assert.strictEqual(addDays('2024-02-28', 1), '2024-02-29', 'leap year');
// NZDT->NZST transition (first Sunday in April) must not shift the calendar day.
assert.strictEqual(addDays('2026-04-05', 1), '2026-04-06', 'DST end');
assert.strictEqual(addDays('2026-09-27', 1), '2026-09-28', 'DST start');

// --- isValidDate ---------------------------------------------------------
assert.ok(isValidDate('2026-08-19'));
assert.ok(!isValidDate('2026-02-30'), 'rolls over, so not a real date');
assert.ok(!isValidDate('19-08-2026'));
assert.ok(!isValidDate("2026-08-19' OR 1=1"));
assert.ok(!isValidDate(''));

// --- nzNow ---------------------------------------------------------------
const now = nzNow();
assert.ok(/^\d{4}-\d{2}-\d{2}$/.test(now.date), `nzNow date shape: ${now.date}`);
assert.ok(/^([01]\d|2[0-3]):[0-5]\d$/.test(now.time), `nzNow time shape (h23): ${now.time}`);

// --- selectedDates -------------------------------------------------------
// Bounds are pinned so the expectations don't move with the wall clock.
const BOUNDS = { start: '2019-03-17', max: '2026-08-19', fallback: '2026-08-19' };
const dates = (raw) => selectedDates(raw, BOUNDS);

assert.deepStrictEqual(dates(null), ['2026-08-19'], 'no param -> default');
assert.deepStrictEqual(dates('2026-08-01'), ['2026-08-01'], 'single');
assert.deepStrictEqual(
  dates('2026-08-01_2026-08-03'),
  ['2026-08-01', '2026-08-02', '2026-08-03'],
  'range',
);
assert.deepStrictEqual(
  dates('2026-08-03_2026-08-01'),
  ['2026-08-01', '2026-08-02', '2026-08-03'],
  'reversed range is swapped',
);
assert.strictEqual(dates('2026-01-01_2026-12-31').length, MAX_RANGE_DAYS, 'capped at 7 days');
assert.deepStrictEqual(dates('2030-01-01'), ['2026-08-19'], 'future -> default');
assert.deepStrictEqual(dates('2015-01-01'), ['2026-08-19'], 'before camera start -> default');
assert.deepStrictEqual(dates('garbage'), ['2026-08-19'], 'junk -> default');
// A range straddling max keeps only the in-range days. Out-of-range days still
// count against the 7-day budget, so trailing days are simply dropped.
assert.deepStrictEqual(
  dates('2026-08-18_2026-08-21'),
  ['2026-08-18', '2026-08-19'],
  'clamped to max',
);

console.log('timelapse date logic OK');
