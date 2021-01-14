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

/**
 * Link to the Hijri calendar type settings.
 *
 * @package calendartype_hijri
 * @copyright 2008 onwards Foodle Group {@link http://foodle.org}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$settings->add(new admin_setting_configselect('calendartype_hijri/algorithm',
        new lang_string('algorithm', 'calendartype_hijri'),
        new lang_string('configalgorithm', 'calendartype_hijri'), 0,
        array(
            new lang_string('algorithm1', 'calendartype_hijri'),
            new lang_string('algorithm2', 'calendartype_hijri'),
            new lang_string('algorithm3', 'calendartype_hijri'),
        )));
