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
 * File containing processor class.
 *
 * @package    tool_switchenrol
 * @copyright  2020 Michael Vangelovski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/csvlib.class.php');

/**
 * Processor class.
 *
 * @package    tool_switchenrol
 * @copyright  2020 Michael Vangelovski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_switchenrol_processor {

    /** @var csv_import_reader */
    protected $cir;

    /** @var array CSV columns. */
    protected $columns = array();

    /** @var array of errors where the key is the line number. */
    protected $errors = array();

    /** @var int line number. */
    protected $linenb = 0;

    /** @var bool whether the process has been started or not. */
    protected $processstarted = false;

    /**
     * Constructor
     *
     * @param csv_import_reader $cir import reader object
     * @param array $options options of the process
     */
    public function __construct(csv_import_reader $cir) {
        $this->cir = $cir;
        $this->columns = $cir->get_columns();
        $this->validate();
        $this->reset();
    }

    /**
     * Execute the process.
     *
     * @param object $tracker the output tracker to use.
     * @return void
     */
    public function execute($tracker = null) {
        if ($this->processstarted) {
            throw new coding_exception('Process has already been started');
        }
        $this->processstarted = true;

        if (empty($tracker)) {
            $tracker = new tool_switchenrol_tracker(tool_switchenrol_tracker::NO_OUTPUT);
        }
        $tracker->start();

        $total = 0;
        $updated = 0;
        $errors = 0;

        // We will most certainly need extra time and memory to process big files.
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_EXTRA);

        // Loop over the CSV lines.
        while ($line = $this->cir->next()) {
            $this->linenb++;
            $total++;

            $data = $this->parse_line($line);
            $course = $this->get_course($data);
            if ($course->prepare()) {
                $course->proceed();

                $status = $course->get_statuses();
                if (array_key_exists('enrolupdated', $status)) {
                    $updated++;
                }

                $data = array_merge($data, $course->get_data(), array('id' => $course->get_id()));
                $tracker->output($this->linenb, true, $status, $data);
            } else {
                $errors++;
                $tracker->output($this->linenb, false, $course->get_errors(), $data);
            }
        }

        $tracker->finish();
        $tracker->results($total, $updated, $errors);
    }

    /**
     * Return a course import object.
     *
     * @param array $data data to import the course with.
     * @return tool_switchenrol_course
     */
    protected function get_course($data) {
        return new tool_switchenrol_course($data);
    }

    /**
     * Return the errors.
     *
     * @return array
     */
    public function get_errors() {
        return $this->errors;
    }


    /**
     * Log errors on the current line.
     *
     * @param array $errors array of errors
     * @return void
     */
    protected function log_error($errors) {
        if (empty($errors)) {
            return;
        }

        foreach ($errors as $code => $langstring) {
            if (!isset($this->errors[$this->linenb])) {
                $this->errors[$this->linenb] = array();
            }
            $this->errors[$this->linenb][$code] = $langstring;
        }
    }

    /**
     * Parse a line to return an array(column => value)
     *
     * @param array $line returned by csv_import_reader
     * @return array
     */
    protected function parse_line($line) {
        $data = array();
        foreach ($line as $keynum => $value) {
            if (!isset($this->columns[$keynum])) {
                // This should not happen.
                continue;
            }

            $key = $this->columns[$keynum];
            $data[$key] = $value;
        }
        return $data;
    }

    /**
     * Return a preview of the import.
     *
     * This only returns passed data, along with the errors.
     *
     * @param integer $rows number of rows to preview.
     * @param object $tracker the output tracker to use.
     * @return array of preview data.
     */
    public function preview($rows = 10, $tracker = null) {
        if ($this->processstarted) {
            throw new coding_exception('Process has already been started');
        }
        $this->processstarted = true;

        if (empty($tracker)) {
            $tracker = new tool_switchenrol_tracker(tool_switchenrol_tracker::NO_OUTPUT);
        }
        $tracker->start();

        // We might need extra time and memory depending on the number of rows to preview.
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_EXTRA);

        // Loop over the CSV lines.
        $preview = array();
        while (($line = $this->cir->next()) && $rows > $this->linenb) {
            $this->linenb++;
            $data = $this->parse_line($line);
            $course = $this->get_course($data);
            $result = $course->prepare();
            if (!$result) {
                $tracker->output($this->linenb, $result, $course->get_errors(), $data);
            } else {
                $tracker->output($this->linenb, $result, $course->get_statuses(), $data);
            }
            $row = $data;
            $preview[$this->linenb] = $row;
        }

        $tracker->finish();

        return $preview;
    }

    /**
     * Reset the current process.
     *
     * @return void.
     */
    public function reset() {
        $this->processstarted = false;
        $this->linenb = 0;
        $this->cir->init();
        $this->errors = array();
    }

    /**
     * Validation.
     *
     * @return void
     */
    protected function validate() {
        if (empty($this->columns)) {
            throw new moodle_exception('cannotreadtmpfile', 'error');
        } else if (count($this->columns) < 2) {
            throw new moodle_exception('csvfewcolumns', 'error');
        }
    }
}
