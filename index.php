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
 * Bulk course enrolment method switch script from a comma separated file.
 *
 * @package    tool_switchenrol
 * @copyright  2020 Michael Vangelovski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');

admin_externalpage_setup('tool_switchenrol');

$importid = optional_param('importid', '', PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);

$returnurl = new moodle_url('/admin/tool/switchenrol/index.php');

if (empty($importid)) {
    $mform1 = new tool_switchenrol_step1_form();
    if ($form1data = $mform1->get_data()) {
        $importid = csv_import_reader::get_new_iid('switchenrol');
        $cir = new csv_import_reader($importid, 'switchenrol');
        $content = $mform1->get_file_content('coursefile');
        $readcount = $cir->load_csv_content($content, $form1data->encoding, $form1data->delimiter_name);
        unset($content);
        if ($readcount === false) {
            print_error('csvfileerror', 'tool_switchenrol', $returnurl, $cir->get_error());
        } else if ($readcount == 0) {
            print_error('csvemptyfile', 'error', $returnurl, $cir->get_error());
        }
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading_with_help(get_string('switchenrol', 'tool_switchenrol'), 'switchenrol', 'tool_switchenrol');
        $mform1->display();
        echo $OUTPUT->footer();
        die();
    }
} else {
    $cir = new csv_import_reader($importid, 'switchenrol');
}

// Data to set in the form.
$data = array('importid' => $importid, 'previewrows' => $previewrows);
$context = context_system::instance();
$mform2 = new tool_switchenrol_step2_form(null, array('contextid' => $context->id, 'columns' => $cir->get_columns(),
    'data' => $data));

// If a file has been uploaded, then process it.
if ($form2data = $mform2->is_cancelled()) {
    $cir->cleanup(true);
    redirect($returnurl);
} else if ($form2data = $mform2->get_data()) {
    $processor = new tool_switchenrol_processor($cir);
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('switchenrolsresult', 'tool_switchenrol'));
    $processor->execute(new tool_switchenrol_tracker(tool_switchenrol_tracker::OUTPUT_HTML));
    echo $OUTPUT->continue_button($returnurl);
} else {
    $processor = new tool_switchenrol_processor($cir);
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('switchenrolspreview', 'tool_switchenrol'));
    $processor->preview($previewrows, new tool_switchenrol_tracker(tool_switchenrol_tracker::OUTPUT_HTML));
    $mform2->display();
}

echo $OUTPUT->footer();
