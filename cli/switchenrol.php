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
 * CLI Bulk course enrolment method switch script from a comma separated file.
 *
 * @package    tool_switchenrol
 * @copyright  2020 Michael Vangelovski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/csvlib.class.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array(
    'file' => '',
    'delimiter' => 'comma',
    'encoding' => 'UTF-8',
),
array(
    'f' => 'file',
    'd' => 'delimiter',
    'e' => 'encoding',
));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help =
"Execute Switch Enrol.

Options:
-f, --file                 CSV file
-d, --delimiter            CSV delimiter: colon, semicolon, tab, cfg, comma
-e, --encoding             CSV file encoding: utf8, ... etc

Example:
\$sudo -u www-data /usr/bin/php admin/tool/switchenrol/cli/switchenrol.php \\
    --file=./courses.csv --delimiter=comma
";

echo "Switch enrol running ...\n";

// File.
if (!empty($options['file'])) {
    $options['file'] = realpath($options['file']);
}
if (!file_exists($options['file'])) {
    echo get_string('invalidcsvfile', 'tool_switchenrol')."\n";
    echo $help;
    die();
}

// Encoding.
$encodings = core_text::get_encodings();
if (!isset($encodings[$options['encoding']])) {
    echo get_string('invalidencoding', 'tool_switchenrol')."\n";
    echo $help;
    die();
}

// Emulate normal session.
cron_setup_user();

// Let's get started!
$content = file_get_contents($options['file']);
$importid = csv_import_reader::get_new_iid('switchenrol');
$cir = new csv_import_reader($importid, 'switchenrol');
$readcount = $cir->load_csv_content($content, $options['encoding'], $options['delimiter']);
unset($content);
if ($readcount === false) {
    print_error('csvfileerror', 'tool_switchenrol', '', $cir->get_error());
} else if ($readcount == 0) {
    print_error('csvemptyfile', 'error', '', $cir->get_error());
}
$processor = new tool_switchenrol_processor($cir);
$processor->execute(new tool_switchenrol_tracker(tool_switchenrol_tracker::OUTPUT_PLAIN));
