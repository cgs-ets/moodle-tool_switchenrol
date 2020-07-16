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
 * File containing the helper class.
 *
 * @package    tool_switchenrol
 * @copyright  2020 Michael Vangelovski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();;

/**
 * Class containing a set of helpers.
 *
 * @package    tool_switchenrol
 * @copyright  2020 Michael Vangelovski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_switchenrol_helper {

    /**
    * Extract the enrol records for the course enrolments to be switched.
    *
    *
    * @param stdClass $course
    * @return array
    */
    public static function get_enrol_methods($course) {
        $oldmethod = null;
        $newmethod = null;
        $instances = enrol_get_instances($course->id, false);
        foreach ($instances as $instance) {
            if ($instance->enrol == $course->enrolold) {
                $oldmethod = $instance;
            }
            if ($instance->enrol == $course->enrolnew) {
                $newmethod = $instance;
            }
        }
        return array($oldmethod, $newmethod);
    }
  
}
