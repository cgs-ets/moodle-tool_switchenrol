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
 * Strings for component 'tool_switchenrol'.
 *
 * @package    tool_switchenrol
 * @copyright  2020 Michael Vangelovski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['cannotupdatefrontpage'] = 'It is forbidden to modify the front page';
$string['coursefile'] = 'File';
$string['coursefile_help'] = 'This file must be a CSV file.';
$string['coursedoesnotexist'] = 'The course does not exist';
$string['coursesupdated'] = 'Courses updated: {$a}';
$string['courseserrors'] = 'Courses errors: {$a}';
$string['enrolupdated'] = 'Enrolments updated';
$string['csvdelimiter'] = 'CSV delimiter';
$string['csvdelimiter_help'] = 'CSV delimiter of the CSV file.';
$string['csvfileerror'] = 'There is something wrong with the format of the CSV file. Please check the number of headings and columns match, and that the delimiter and file encoding are correct: {$a}';
$string['csvline'] = 'Line';
$string['encoding'] = 'Encoding';
$string['encoding_help'] = 'Encoding of the CSV file.';
$string['id'] = 'ID';
$string['invalidcsvfile'] = 'Invalid input CSV file';
$string['invalidencoding'] = 'Invalid encoding';
$string['invalidshortname'] = 'Invalid shortname';
$string['missingmandatoryfields'] = 'Missing value for mandatory fields: {$a}';
$string['nochanges'] = 'No changes';
$string['pluginname'] = 'Switch enrol plugins';
$string['preview'] = 'Preview';
$string['result'] = 'Result';
$string['rowpreviewnum'] = 'Preview rows';
$string['rowpreviewnum_help'] = 'Number of rows from the CSV file that will be previewed on the following page. This option is for limiting the size of the following page.';
$string['switchenrol'] = 'Switch enrol plugins';
$string['switchenrol_help'] = 'Courses may be uploaded via text file. The format of the file should be as follows:
* Each line of the file contains one record
* Each record is a series of data separated by commas (or other delimiters)
* The first record contains a list of fieldnames defining the format of the rest of the file
* Required fieldnames are shortname, enrolold, and enrolnew';
$string['switchenrolspreview'] = 'Switch enrol plugins preview';
$string['switchenrolsresult'] = 'Switch enrol plugins results';
$string['privacy:metadata'] = 'The Switch enrol plugin does not store any personal data.';
$string['enrolold'] = 'Enrol plugin (old)';
$string['enrolnew'] = 'Enrol plugin (new)';
$string['enrolmethoddoesnotexist'] = 'One of the enrolment methods does not exist in the course.';

