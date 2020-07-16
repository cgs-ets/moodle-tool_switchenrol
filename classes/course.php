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
 * File containing the course class.
 *
 * @package    tool_switchenrol
 * @copyright  2020 Michael Vangelovski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Course class.
 *
 * @package    tool_switchenrol
 * @copyright  2020 Michael Vangelovski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_switchenrol_course {

    /** @var array final import data. */
    protected $data = array();

    /** @var array errors. */
    protected $errors = array();

    /** @var int the ID of the course that had been processed. */
    protected $id;

    /** @var bool set to true once we have prepared the course */
    protected $prepared = false;

    /** @var bool set to true once we have started the process of the course */
    protected $processstarted = false;

    /** @var array course import data. */
    protected $rawdata = array();

    /** @var string course shortname. */
    protected $shortname;

    /** @var array errors. */
    protected $statuses = array();

    /** @var array fields allowed as course data. */
    static protected $validfields = array('shortname', 'enrolold', 'enrolnew');

    /** @var array fields required on course creation. */
    static protected $mandatoryfields = array('enrolold', 'enrolnew');

    /**
     * Constructor
     *
     * @param array $rawdata raw course data.
     */
    public function __construct($rawdata) {

        if (isset($rawdata['shortname'])) {
            $this->shortname = $rawdata['shortname'];
        }
        $this->rawdata = $rawdata;

    }

    /**
     * Log an error
     *
     * @param string $code error code.
     * @param lang_string $message error message.
     * @return void
     */
    protected function error($code, lang_string $message) {
        if (array_key_exists($code, $this->errors)) {
            throw new coding_exception('Error code already defined');
        }
        $this->errors[$code] = $message;
    }

    /**
     * Return whether the course exists or not.
     *
     * @param string $shortname the shortname to use to check if the course exists. Falls back on $this->shortname if empty.
     * @return bool
     */
    protected function exists($shortname = null) {
        global $DB;
        if (is_null($shortname)) {
            $shortname = $this->shortname;
        }
        if (!empty($shortname) || is_numeric($shortname)) {
            return $DB->record_exists('course', array('shortname' => $shortname));
        }
        return false;
    }

    /**
     * Return the data that will be used upon saving.
     *
     * @return null|array
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * Return the errors found during preparation.
     *
     * @return array
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Return the ID of the processed course.
     *
     * @return int|null
     */
    public function get_id() {
        if (!$this->processstarted) {
            throw new coding_exception('The course has not been processed yet!');
        }
        return $this->id;
    }


    /**
     * Return the errors found during preparation.
     *
     * @return array
     */
    public function get_statuses() {
        return $this->statuses;
    }

    /**
     * Return whether there were errors with this course.
     *
     * @return boolean
     */
    public function has_errors() {
        return !empty($this->errors);
    }

    /**
     * Validates and prepares the data.
     *
     * @return bool false is any error occured.
     */
    public function prepare() {
        global $DB, $SITE;
        $this->prepared = true;

        // Validate the shortname.
        if (!empty($this->shortname) || is_numeric($this->shortname)) {
            if ($this->shortname !== clean_param($this->shortname, PARAM_TEXT)) {
                $this->error('invalidshortname', new lang_string('invalidshortname', 'tool_switchenrol'));
                return false;
            }
        }

        $exists = $this->exists();
        if (!$exists) {
            $this->error('coursedoesnotexist', new lang_string('coursedoesnotexist', 'tool_switchenrol'));
            return false;
        }

        // Basic data.
        $coursedata = array();
        foreach ($this->rawdata as $field => $value) {
            if (!in_array($field, self::$validfields)) {
                continue;
            } else if ($field == 'shortname') {
                // Let's leave it apart from now, use $this->shortname only.
                continue;
            }
            $coursedata[$field] = $value;
        }

        // Mandatory fields upon creation.
        $errors = array();
        foreach (self::$mandatoryfields as $field) {
            if ((!isset($coursedata[$field]) || $coursedata[$field] === '') &&
                    (!isset($this->defaults[$field]) || $this->defaults[$field] === '')) {
                $errors[] = $field;
            }
        }
        if (!empty($errors)) {
            $this->error('missingmandatoryfields', new lang_string('missingmandatoryfields', 'tool_uploadcourse',
                implode(', ', $errors)));
            return false;
        }

        // Make sure we are not trying to mess with the front page, though we should never get here!
        $coursedata['id'] = $DB->get_field('course', 'id', array('shortname' => $this->shortname));
        if ($coursedata['id'] == $SITE->id) {
            $this->error('cannotupdatefrontpage', new lang_string('cannotupdatefrontpage', 'tool_switchenrol'));
            return false;
        }

        // Find the old and new enrol method instances for the course
        $course = (object) $coursedata;
        list($oldmethod, $newmethod) = tool_switchenrol_helper::get_enrol_instances($course);
        if (empty($oldmethod) || empty($newmethod)) {
            $this->error('enrolmethoddoesnotexist', new lang_string('enrolmethoddoesnotexist', 'tool_switchenrol'));
            return false;
        }

        // Check whether the old method has any enrolments.
        if( ! $DB->record_exists('user_enrolments', array('enrolid' => $oldmethod->id))) {
            $this->error('enrolmethoddoesnousers', new lang_string('enrolmethoddoesnousers', 'tool_switchenrol'));
            return false;
        }

        // Saving data.
        $this->data = $coursedata;

        return true;
    }

    /**
     * Proceed with the import of the course.
     *
     * @return void
     */
    public function proceed() {
        global $CFG, $USER, $DB;

        if (!$this->prepared) {
            throw new coding_exception('The course has not been prepared.');
        } else if ($this->has_errors()) {
            throw new moodle_exception('Cannot proceed, errors were detected.');
        } else if ($this->processstarted) {
            throw new coding_exception('The process has already been started.');
        }
        $this->processstarted = true;

        $course = (object) $this->data;

        // Find the old and new enrol method instances for the course
        list($oldinstance, $newinstance) = tool_switchenrol_helper::get_enrol_instances($course);
        if (empty($oldinstance) || empty($newinstance)) {
            // One of the methods does not exist in this course... this warning should have been picked up in the preview.
            return;
        }

        $sql = "UPDATE {user_enrolments}
                   SET enrolid = ?
                 WHERE enrolid = ?";
        $params = array($newinstance->id, $oldinstance->id);
        $DB->execute($sql, $params);
        $this->status('enrolupdated', new lang_string('enrolupdated', 'tool_switchenrol'));
    }
   
    /**
     * Log a status
     *
     * @param string $code status code.
     * @param lang_string $message status message.
     * @return void
     */
    protected function status($code, lang_string $message) {
        if (array_key_exists($code, $this->statuses)) {
            throw new coding_exception('Status code already defined');
        }
        $this->statuses[$code] = $message;
    }

}
