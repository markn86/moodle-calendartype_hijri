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
 * @package calendar_type_plugin_hijri
 * @copyright Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class structure extends type_base {

    /**
     * @var float the islamic epoch
     */
    private $ISLAMIC_EPOCH = 1948439.5;

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
        return 1390;
    }

    /**
     * Returns the maximum year of the calendar.
     *
     * @return int the max year
     */
    public function get_max_year() {
        return 1440;
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
    public function userdate($date, $format, $timezone, $fixday, $fixhour) {
        global $CFG;

        $amstring = get_string('am', 'calendartype_hijri');
        $pmstring = get_string('pm', 'calendartype_hijri');

        if (empty($format)) {
            $format = get_string('strftimedaydatetime');
        }

        if (!empty($CFG->nofixday)) { // Config.php can force %d not to be fixed.
            $fixday = false;
        }

        $date_ = $this->usergetdate($date, $timezone);
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
            ), array($date_["weekday"],
                     $date_["weekday"],
                     $date_["month"],
                     $date_["month"],
                     (($date_["mday"] < 10 && !$fixday) ? '0' : '') . $date_["mday"],
                     ($date_["mon"] < 10 ? '0' : '') . $date_["mon"],
                     $date_["year"] % 100,
                     $date_["year"],
                     ($date_["hours"] < 12 ? strtoupper($amstring) : strtoupper($pmstring)),
                     ($date_["hours"] < 12 ? $amstring : $pmstring)
            ), $format);

        return parent::userdate($date, $format, $timezone, $fixday, $fixhour);
    }

    /**
     * Given a $time timestamp in GMT (seconds since epoch), returns an array that
     * represents the date in user time.
     *
     * @param int $time Timestamp in GMT
     * @param float|int|string $timezone offset's time with timezone, if float and not 99, then no
     *        dst offset is applyed {@link http://docs.moodle.org/dev/Time_API#Timezone}
     * @return array An array that represents the date in user time
     */
    public function usergetdate($time, $timezone) {
        $date = parent::usergetdate($time, $timezone);
        $new_date = $this->convert_from_gregorian($date["mday"], $date["mon"], $date["year"],
            $date['hours'], $date['minutes']);

        $date["month"] = get_string("month{$new_date['month']}", 'calendartype_hijri');
        $date["weekday"] = get_string("weekday{$date['wday']}", 'calendartype_hijri');
        $date["yday"] = null;
        $date["year"] = $new_date['year'];
        $date["mon"] = $new_date['month'];
        $date["mday"] = $new_date['day'];

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
    public function convert_to_gregorian($day, $month, $year, $hour = 0, $minute = 0) {
        $jd = $this->islamic_to_jd($day, $month, $year);
        $array = $this->jd_to_gregorian($jd);
        $array['hour'] = $hour;
        $array['minute'] = $minute;

        return $array;
    }

    /**
     * Provided with a day, month, year, hour and minute in a Gregorian date
     * convert it into the specific calendar type date.
     *
     * @param int $day
     * @param int $month
     * @param int $year
     * @param int $hour
     * @param int $minute
     * @return array the converted day, month, year, hour and minute.
     */
    public function convert_from_gregorian($day, $month, $year, $hour = 0, $minute = 0) {
        $jd = gregoriantojd($month, $day, $year);
        $array = $this->jd_to_islamic($jd);
        $array['hour'] = $hour;
        $array['minute'] = $minute;

        return $array;
    }

    /**
     * Calculate Gregorian calendar date from Julian day
     *
     * @param int jd
     * @return array
     */
    private function jd_to_gregorian($jd) {
        $gregorian = jdtogregorian($jd);
        list($month, $day, $year) = explode('/', $gregorian);
        return array('year' => $year,
                     'month' => $month,
                     'day' => $day);
    }

    /**
     * Convert islamic date to the JD calendar.
     *
     * @param int $day
     * @param int $month
     * @param int $year
     * @return float
     */
    private function islamic_to_jd($day, $month, $year) {
        return ($day + ceil(29.5 * ($month - 1)) + ($year - 1) * 354 + floor((3 + (11 * $year)) / 30) +
            $this->ISLAMIC_EPOCH) - 1;
    }

    /**
     * Convert a JD date to the Islamic date.
     *
     * @param float $jd
     * @return array
     */
    private function jd_to_islamic($jd) {
        $jd = floor($jd) + 0.5;
        $year = floor(((30 * ($jd - $this->ISLAMIC_EPOCH)) + 10646) / 10631);
        $month = min(12, ceil(($jd - (29 + $this->islamic_to_jd(1, 1, $year))) / 29.5) + 1);
        $day = ($jd - $this->islamic_to_jd(1, $month, $year)) + 1;

        return array('year' => $year,
                     'month' => $month,
                     'day' => $day);
    }
}
