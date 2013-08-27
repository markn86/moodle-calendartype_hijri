<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace calendartype_hijri;
use core_calendar\type_base;

/**
 * Handles calendar functions for the hijri calendar.
 *
 * @package calendartype_hijri
 * @copyright 2008 onwards Foodle Group {@link http://foodle.org}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class structure extends type_base {

    /** @var float the islamic epoch */
    private $islamicepoch = 1948439.5;

    /** @var float the gregorian epoch */
    private $gregorianepoch = 1721425.5;

    /**
     * Returns a list of all the possible days for all months.
     *
     * This is used to generate the select box for the days
     * in the date selector elements. Some months contain more days
     * than others so this function should return all possible days as
     * we can not predict what month will be chosen (the user
     * may have JS turned off and we need to support this situation in
     * Moodle).
     *
     * @return array the days
     */
    public function get_days() {
        $days = array();

        for ($i = 1; $i <= 31; $i++) {
            $days[$i] = $i;
        }

        return $days;
    }

    /**
     * Returns a list of all the names of the months.
     *
     * @return array the month names
     */
    public function get_months() {
        $months = array();

        for ($i=1; $i<=12; $i++) {
            $months[$i] = get_string('month' . $i, 'calendartype_hijri');
        }

        return $months;
    }

    /**
     * Returns the minimum year of the calendar.
     *
     * @return int the minumum year
     */
    public function get_min_year() {
        return 1317;
    }

    /**
     * Returns the maximum year of the calendar.
     *
     * @return int the max year
     */
    public function get_max_year() {
        return 1473;
    }

    /**
     * Returns a formatted string that represents a date in user time.
     *
     * If parameter fixday = true (default), then take off leading
     * zero from %d, else maintain it.
     *
     * @param int $date the timestamp in UTC, as obtained from the database.
     * @param string $format strftime format. You should probably get this using
     *        get_string('strftime...', 'langconfig');
     * @param int|float|string  $timezone by default, uses the user's time zone. if numeric and
     *        not 99 then daylight saving will not be added.
     *        {@link http://docs.moodle.org/dev/Time_API#Timezone}
     * @param bool $fixday if true (default) then the leading zero from %d is removed.
     *        If false then the leading zero is maintained.
     * @param bool $fixhour if true (default) then the leading zero from %I is removed.
     * @return string the formatted date/time.
     */
    public function timestamp_to_date_string($date, $format, $timezone, $fixday, $fixhour) {
        global $CFG;

        $amstring = get_string('am', 'calendartype_hijri');
        $pmstring = get_string('pm', 'calendartype_hijri');

        if (empty($format)) {
            $format = get_string('strftimedaydatetime');
        }

        if (!empty($CFG->nofixday)) { // Config.php can force %d not to be fixed.
            $fixday = false;
        }

        $hjdate = $this->timestamp_to_date_array($date, $timezone);
        $format = str_replace(array(
            "%a",
            "%A",
            "%b",
            "%B",
            "%d",
            "%m",
            "%y",
            "%Y",
            "%p",
            "%P"
        ), array($hjdate["weekday"],
            $hjdate["weekday"],
            $hjdate["month"],
            $hjdate["month"],
            (($hjdate["mday"] < 10 && !$fixday) ? '0' : '') . $hjdate["mday"],
            ($hjdate["mon"] < 10 ? '0' : '') . $hjdate["mon"],
            $hjdate["year"] % 100,
            $hjdate["year"],
            ($hjdate["hours"] < 12 ? strtoupper($amstring) : strtoupper($pmstring)),
            ($hjdate["hours"] < 12 ? $amstring : $pmstring)
        ), $format);

        $gregoriancalendar = \core_calendar\type_factory::get_calendar_instance('gregorian');
        return $gregoriancalendar->timestamp_to_date_string($date, $format, $timezone, $fixday, $fixhour);
    }

    /**
     * Given a $time timestamp in GMT (seconds since epoch), returns an array that
     * represents the date in user time.
     *
     * @param int $time Timestamp in GMT
     * @param float|int|string $timezone offset's time with timezone, if float and not 99, then no
     *        dst offset is applied {@link http://docs.moodle.org/dev/Time_API#Timezone}
     * @return array an array that represents the date in user time
     */
    public function timestamp_to_date_array($time, $timezone) {
        $gregoriancalendar = \core_calendar\type_factory::get_calendar_instance('gregorian');

        $date = $gregoriancalendar->timestamp_to_date_array($time, $timezone);
        $hjdate = $this->convert_from_gregorian($date['year'], $date['mon'], $date['mday']);

        $date['month'] = get_string("month{$hjdate['month']}", 'calendartype_hijri');
        $date['weekday'] = get_string("weekday{$date['wday']}", 'calendartype_hijri');
        $date['yday'] = ($hjdate['month'] - 1) * 29 + intval($hjdate['month'] / 2) + $hjdate['day'];
        $date['year'] = $hjdate['year'];
        $date['mon'] = $hjdate['month'];
        $date['mday'] = $hjdate['day'];

        return $date;
    }

    /**
     * Provided with a day, month, year, hour and minute in a specific
     * calendar type convert it into the equivalent Gregorian date.
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @param int $hour
     * @param int $minute
     * @return array the converted day, month, year, hour and minute.
     */
    public function convert_from_gregorian($year, $month, $day, $hour = 0, $minute = 0) {
        $jd = $this->gregorian_to_jd($year, $month, $day);

        $date = $this->jd_to_hijri($jd);
        $date['hour'] = $hour;
        $date['minute'] = $minute;

        return $date;
    }

    /**
     * Provided with a day, month, year, hour and minute in hijri
     * convert it into the equivalent gregorian date.
     *
     * @param int $day
     * @param int $month
     * @param int $year
     * @param int $hour
     * @param int $minute
     * @return array the converted day, month, year, hour and minute.
     */
    public function convert_to_gregorian($year, $month = null, $day = null, $hour = 0, $minute = 0) {
        $jd = $this->hijri_to_jd($year, $month, $day);
        $date = $this->jd_to_gregorian($jd);
        $date['hour'] = $hour;
        $date['minute'] = $minute;

        return $date;
    }

    /**
     * Convert given Julian day into Hijri date.
     *
     * @param int $jd the Julian day
     * @return array the Hijri date
     */
    private function jd_to_hijri($jd) {
        $jd = floor($jd) + 0.5;
        $year = floor(((30 * ($jd - $this->islamicepoch)) + 10646) / 10631);
        $month = min(12, ceil(($jd - (29 + $this->hijri_to_jd($year, 1, 1))) / 29.5) + 1);
        $day = ($jd - $this->hijri_to_jd($year, $month, 1)) + 1;

        $date = array();
        $date['year'] = $year;
        $date['month'] = $month;
        $date['day'] = $day;

        return $date;
    }

    /**
     * Convert given Hijri date into Julian day.
     *
     * @param int $y the year
     * @param int $m the month
     * @param int $d the day
     * @return int
     */
    private function hijri_to_jd($y, $m, $d) {
        return ($d + ceil(29.5 * ($m - 1)) + ($y - 1) * 354 +
            floor((3 + (11 * $y)) / 30) + $this->islamicepoch) - 1;
    }

    /**
     * Converts a Gregorian date to Julian Day Count.
     *
     * @param int $y the year
     * @param int $m the month
     * @param int $d the day
     * @return int the Julian Day for the given Gregorian date
     */
    private function gregorian_to_jd($y, $m, $d) {
        if (function_exists('gregoriantojd')) {
            return gregoriantojd($m, $d, $y);
        } else {
            return ($this->gregorianepoch - 1) +
            (365 * ($y - 1)) +
            floor(($y - 1) / 4) +
            (-floor(($y - 1) / 100)) +
            floor(($y - 1) / 400) +
            floor((((367 * $m) - 362) / 12) +
            (($m <= 2) ? 0 : ($this->leap_gregorian($y) ? -1 : -2)) + $d);
        }
    }

    /**
     * Returns true if the Gregorian year supplied is a leap year.
     *
     * @param $year
     * @return bool
     */
    private function leap_gregorian($year) {
        return (($year % 4) == 0) && (!((($year % 100) == 0) && (($year % 400) != 0)));
    }

    /**
     * Converts a JD to Gregorian date.
     *
     * @param int $jd the Julian Day
     * @return array the Gregorian date
     */
    private function jd_to_gregorian($jd) {
        $wjd = floor($jd - 0.5) + 0.5;
        $depoch = $wjd - $this->gregorianepoch;
        $quadricent = floor($depoch / 146097);
        $dqc = $depoch % 146097;
        $cent = floor($dqc / 36524);
        $dcent = $dqc % 36524;
        $quad = floor($dcent / 1461);
        $dquad = $dcent % 1461;
        $yindex = floor($dquad / 365);
        $year = ($quadricent * 400) + ($cent * 100) + ($quad * 4) + $yindex;
        if (!(($cent == 4) || ($yindex == 4))) {
            $year++;
        }
        $yearday = $wjd - $this->gregorian_to_jd($year, 1, 1);
        $leapadj = (($wjd < $this->gregorian_to_jd($year, 3, 1)) ? 0 : ($this->leap_gregorian($year) ? 1 : 2));
        $month = floor(((($yearday + $leapadj) * 12) + 373) / 367);
        $day = ($wjd - $this->gregorian_to_jd($year, $month, 1)) + 1;

        $date = array();
        $date['year'] = $year;
        $date['month'] = $month;
        $date['day'] = $day;

        return $date;
    }
}
