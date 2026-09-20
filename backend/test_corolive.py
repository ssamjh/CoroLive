import unittest
from datetime import date, timedelta
from pathlib import Path
from unittest.mock import patch

import corolive


CAMERA = {
    "name": "whitianga",
    "url": "http://example.invalid/snapshot",
    "latitude": -36.8333,
    "longitude": 175.7000,
    "elevation": 0.0,
}


class SolarArchiveScheduleTests(unittest.TestCase):
    def test_windows_are_even_and_seasonal(self):
        winter_start, winter_end = corolive.archive_window(CAMERA, date(2026, 6, 21))
        summer_start, summer_end = corolive.archive_window(CAMERA, date(2026, 12, 21))

        for boundary in (winter_start, winter_end, summer_start, summer_end):
            self.assertEqual(boundary.minute % 2, 0)
            self.assertEqual(boundary.second, 0)

        self.assertLess(winter_end - winter_start, summer_end - summer_start)
        self.assertEqual(winter_start.utcoffset(), timedelta(hours=12))
        self.assertEqual(summer_start.utcoffset(), timedelta(hours=13))

    def test_only_even_minutes_inside_the_window_are_accepted(self):
        start, end = corolive.archive_window(CAMERA, date(2026, 6, 21))

        self.assertTrue(corolive.should_archive(CAMERA, start + timedelta(seconds=45)))
        self.assertTrue(corolive.should_archive(CAMERA, end + timedelta(seconds=45)))
        self.assertFalse(corolive.should_archive(CAMERA, start + timedelta(minutes=1)))
        self.assertFalse(corolive.should_archive(CAMERA, start - timedelta(minutes=2)))
        self.assertFalse(corolive.should_archive(CAMERA, end + timedelta(minutes=2)))

    def test_nightly_animation_is_after_the_summer_archive_window(self):
        _, end = corolive.archive_window(CAMERA, date(2026, 12, 21))
        self.assertLess(end.strftime("%H:%M"), corolive.ANIMATE_AT)

    def test_odd_manual_archive_call_cannot_open_a_database(self):
        start, _ = corolive.archive_window(CAMERA, date(2026, 6, 21))
        odd_minute = start + timedelta(minutes=1)

        with patch.object(corolive, "open_db") as open_db:
            corolive.save_archive(
                CAMERA["name"], Path("unused.jpg"), CAMERA, now=odd_minute
            )

        open_db.assert_not_called()


if __name__ == "__main__":
    unittest.main()
