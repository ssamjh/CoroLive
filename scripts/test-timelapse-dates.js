// Self-check for the NZ date logic in web-src/timelapse.html.
// Run: node scripts/test-timelapse-dates.js
const assert = require('assert');
const fs = require('fs');
const path = require('path');

// Pull the date helpers straight out of the page so the test can't drift from it.
const page = fs.readFileSync(path.join(__dirname, '..', 'web-src', 'timelapse.html'), 'utf8');
const helpers = page.slice(page.indexOf('var MAX_RANGE_DAYS'), page.indexOf('var camera = queryCamera()'));
// eval is deliberate and safe here: the input is a fixed slice of a file in this
// repo, and evaluating it is the point - it keeps the test from drifting from
// the shipped code. No external or user input reaches this.
eval(helpers);

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
assert.ok(/^\d{4}-\d{2}-\d{2}$/.test(now.date), 'nzNow date shape: ' + now.date);
assert.ok(/^([01]\d|2[0-3]):[0-5]\d$/.test(now.time), 'nzNow time shape (h23): ' + now.time);

// --- selectedDates -------------------------------------------------------
// Re-implemented here against pinned globals, mirroring the page's logic.
global.START_DATE = '2019-03-17';
global.MAX_DATE = '2026-08-19';
global.DEFAULT_DATE = '2026-08-19';
function inRange(d) { return d >= START_DATE && d <= MAX_DATE; }

function selectedDates(raw) {
    const dates = [];
    if (raw) {
        const parts = raw.split('_').slice(0, 2).map(s => s.trim());
        if (parts.length === 2 && isValidDate(parts[0]) && isValidDate(parts[1])) {
            let from = parts[0], to = parts[1];
            if (from > to) { [from, to] = [to, from]; }
            for (let cur = from, n = 0; cur <= to && n < MAX_RANGE_DAYS; cur = addDays(cur, 1), n++) {
                if (inRange(cur)) { dates.push(cur); }
            }
        } else if (isValidDate(parts[0]) && inRange(parts[0])) {
            dates.push(parts[0]);
        }
    }
    return dates.length ? dates : [DEFAULT_DATE];
}

assert.deepStrictEqual(selectedDates(null), ['2026-08-19'], 'no param -> default');
assert.deepStrictEqual(selectedDates('2026-08-01'), ['2026-08-01'], 'single');
assert.deepStrictEqual(selectedDates('2026-08-01_2026-08-03'),
    ['2026-08-01', '2026-08-02', '2026-08-03'], 'range');
assert.deepStrictEqual(selectedDates('2026-08-03_2026-08-01'),
    ['2026-08-01', '2026-08-02', '2026-08-03'], 'reversed range is swapped');
assert.strictEqual(selectedDates('2026-01-01_2026-12-31').length, MAX_RANGE_DAYS, 'capped at 7 days');
assert.deepStrictEqual(selectedDates('2030-01-01'), ['2026-08-19'], 'future -> default');
assert.deepStrictEqual(selectedDates('2015-01-01'), ['2026-08-19'], 'before camera start -> default');
assert.deepStrictEqual(selectedDates('garbage'), ['2026-08-19'], 'junk -> default');
// A range straddling MAX_DATE keeps only the in-range days. Out-of-range days
// still count against the 7-day budget, so trailing days are simply dropped.
assert.deepStrictEqual(selectedDates('2026-08-18_2026-08-21'),
    ['2026-08-18', '2026-08-19'], 'clamped to MAX_DATE');

console.log('timelapse date logic OK');
