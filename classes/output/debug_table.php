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
 * Debug table
 *
 * @package    plagiarism_urkund
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_urkund\output;

defined('MOODLE_INTERNAL') || die();

use moodle_url, html_writer;

/**
 * Table to display list backups.
 *
 * @package    plagiarism_urkund
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class debug_table extends \table_sql {

    /**
     * @var array stores cached activity name.
     */
    public $activitynames = array();

    /**
     * Constructor
     * @param int $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     */
    public function __construct($uniqueid) {
        global $OUTPUT, $PAGE;
        parent::__construct($uniqueid);

        $url = $PAGE->url;
        $this->define_baseurl($url);

        // Set Download flag so we can check it before defining columns/headers to show.
        $this->is_downloading(optional_param('download', '', PARAM_ALPHA), 'UrkundDebugOutput');

        // Define the list of columns to show.
        $columns = array();
        $headers = array();

        // Add selector column if not downloading report.
        if (!$this->is_downloading()) {
            // Add selector column to report.
            $columns[] = 'selector';

            $options = [
                'id' => 'check-items',
                'name' => 'check-items',
                'value' => 1,
            ];
            $mastercheckbox = new \core\output\checkbox_toggleall('items', true, $options);

            $headers[] = $OUTPUT->render($mastercheckbox);
        }

        $columns = array_merge($columns, array('id', 'fullname', 'course', 'activity', 'identifier',
                                               'statuscode', 'attempt', 'timesubmitted'));
        $headers = array_merge($headers, array(get_string('id', 'plagiarism_urkund'),
            get_string('user'),
            get_string('course'),
            get_string('activity'),
            get_string('identifier', 'plagiarism_urkund'),
            get_string('status', 'plagiarism_urkund'),
            get_string('attempts', 'plagiarism_urkund'),
            get_string('timesubmitted', 'plagiarism_urkund')));

        // Add actions column if not downloading this report.
        if (!$this->is_downloading()) {
            array_push($columns, 'action');
            array_push($headers, get_string('action'));
        }
        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->no_sorting('action');
        $this->no_sorting('activity');
        $this->no_sorting('selector');
        $this->initialbars(false);
    }

    /**
     * Function to display the checkbox for bulk actions.
     *
     * @param object $row the data from the db containing all fields from the current row.
     * @return string
     */
    public function col_selector($row) {
        global $OUTPUT;
        if ($this->is_downloading()) {
            return '';
        }
        $options = [
            'id' => 'item'.$row->id,
            'name' => 'item'.$row->id,
            'value' => $row->id,
        ];
        $itemcheckbox = new \core\output\checkbox_toggleall('items', false, $options);
        return $OUTPUT->render($itemcheckbox);
    }

    /**
     * Function to display the available actions for each record.
     *
     * @param object $row the data from the db containing all fields from the current row.
     * @return string
     */
    public function col_action($row) {
        $output = '';
        if ($row->statuscode == URKUND_STATUSCODE_ACCEPTED || $row->statuscode == 'Error') {
            // Add Get score action.
            $url = new moodle_url('/plagiarism/urkund/urkund_debug.php',
                array('id' => $row->id, 'reset' => 2, 'sesskey' => sesskey()));
            $output .= html_writer::link($url, get_string('getscore', 'plagiarism_urkund')). ' | ';
        }

        if ($row->statuscode != URKUND_STATUSCODE_ACCEPTED) {
            // If this is not in an accepted state, add resubmit button.
            $url = new moodle_url('/plagiarism/urkund/urkund_debug.php',
                array('id' => $row->id, 'reset' => 1, 'sesskey' => sesskey()));
            $output .= html_writer::link($url, get_string('resubmit', 'plagiarism_urkund')). ' | ';
        }

        $url = new moodle_url('/plagiarism/urkund/urkund_debug.php',
            array('id' => $row->id, 'delete' => 1, 'sesskey' => sesskey()));
        $output .= html_writer::link($url, get_string('delete'));

        return $output;
    }

    /**
     * Function to display the human readable activity name.
     *
     * @param object $row the data from the db containing all fields from the current row.
     * @return string
     */
    public function col_activity($row) {
        if ($this->is_downloading()) {
            return $row->cm .' '. $row->moduletype;
        }
        if (!empty($activitynames[$row->cm])) {
            $coursemodulename = $activitynames[$row->cm];
        } else {
            $coursemodule = get_coursemodule_from_id($row->moduletype, $row->cm);
            $coursemodulename = $coursemodule->name;
            $activitynames[$row->cm] = $coursemodule->name;
        }

        $cmurl = new moodle_url('/mod/'.$row->moduletype.'/view.php', array('id' => $row->cm));
        return html_writer::link($cmurl, shorten_text($coursemodulename, 40, true), array('title' => $coursemodulename));
    }

    /**
     * Function to display the human readable time this file was submitted.
     *
     * @param object $row the data from the db containing all fields from the current row.
     * @return string
     */
    public function col_timesubmitted($row) {
        return userdate($row->timesubmitted);
    }

    /**
     * Function to display the human readable statuscode this file was submitted.
     *
     * @param object $row the data from the db containing all fields from the current row.
     * @return string
     */
    public function col_statuscode($row) {

        if (strtolower($row->statuscode) == 'error' && !empty($row->errorresponse)) {
            $json = json_decode($row->errorresponse);
            if (json_last_error() == JSON_ERROR_NONE) {
                $last = end($json); // When multiple results, the last one is the important one.
                if (!empty($last->Status->ErrorCode)) {
                    $errorcode = (int)$last->Status->ErrorCode;
                    $errorstring = plagiarism_urkund_get_error_string($errorcode);
                    if (!empty($errorstring)) {
                        return $errorstring;
                    }
                }
            }
        }
        if (!empty($row->statuscode) && get_string_manager()->string_exists('status_'.$row->statuscode, 'plagiarism_urkund')) {
            return get_string('status_'.$row->statuscode, 'plagiarism_urkund');
        } else {
            return $row->statuscode;
        }
    }

    /**
     * Generate content for course column.
     *
     * @param object $row
     * @return string html used to display the course name.
     */
    public function col_course($row) {
        $courseid = $row->courseid;
        if (empty($courseid)) {
            return '';
        }

        if ($this->is_downloading()) {
            return $row->shortname;
        }

        return \html_writer::link(new \moodle_url('/course/view.php', array('id' => $courseid)), $row->shortname);
    }

    /**
     * Finish output - add some extra debug output to end of table when downloading.
     *
     * @param bool $closeexportclassdoc
     * @throws \dml_exception
     */
    public function finish_output($closeexportclassdoc = true) {
        global $DB;
        if ($this->is_downloading()) {
            // Include some extra debugging information in the table.
            // Add some extra lines first.
            $this->add_data(array());
            $this->add_data(array());
            $this->add_data(array());
            $this->add_data(array());
            $lastcron = $DB->get_field_sql('SELECT MAX(lastcron) FROM {modules}');
            if ($lastcron < time() - 3600 * 0.5) { // Check if run in last 30min.
                $this->add_data(array('', 'errorcron', 'lastrun: '.userdate($lastcron), 'not run in last 30min'));;
            }
            $this->add_data(array());
            $this->add_data(array());

            $configrecords = $DB->get_records('plagiarism_urkund_config');
            $this->add_data(array('id', 'cm', 'name', 'value'));
            foreach ($configrecords as $cf) {
                $this->add_data(array($cf->id, $cf->cm, $cf->name, $cf->value));
            }
            if (!empty($heldevents)) {
                $this->add_data(array());
                $this->add_data(array());
                foreach ($heldevents as $e) {
                    $e->eventdata = unserialize(base64_decode($e->eventdata));
                    $this->add_data(array('heldevent', $e->status, $e->component, $e->eventname, var_export($e, true)));
                }
            }
        }
        // Now call finish output to end.
        parent::finish_output($closeexportclassdoc);
    }
}
