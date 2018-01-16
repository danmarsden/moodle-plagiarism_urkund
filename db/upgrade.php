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
 * URKUND upgrade tasks.
 *
 * @package    plagiarism_urkund
 * @author     Dan Marsden http://danmarsden.com
 * @copyright  2017 Dan Marsden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Xmldb upgrade api.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_plagiarism_urkund_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2011121200) {
        $table = new xmldb_table('urkund_files');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'plagiarism_urkund_files');
        }

        $table = new xmldb_table('urkund_config');
        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, 'plagiarism_urkund_config');
        }

        upgrade_plugin_savepoint(true, 2011121200, 'plagiarism', 'urkund');
    }
    if ($oldversion < 2013081900) {
        require_once($CFG->dirroot . '/plagiarism/urkund/lib.php');
        // We have changed the way files are identified to urkund - we need to check for files that have been
        // submitted using the old indentifier format but haven't had a report returned.
        $sql = "UPDATE {plagiarism_urkund_files}
                   SET statuscode = '".URKUND_STATUSCODE_ACCEPTED_OLD."'".
               " WHERE statuscode = '".URKUND_STATUSCODE_ACCEPTED."'";
        $DB->execute($sql);
        upgrade_plugin_savepoint(true, 2013081900, 'plagiarism', 'urkund');
    }

    if ($oldversion < 2015052100) {
        // Check for old API address and update if required.
        $apiaddress = get_config('plagiarism', 'urkund_api');
        if ($apiaddress == 'https://secure.urkund.com/ws/integration/1.0/rest/submissions' ||
            $apiaddress == 'https://secure.urkund.com/api/rest/submissions' ||
            $apiaddress == 'https://secure.urkund.com/api') {
            set_config('urkund_api', 'https://secure.urkund.com/api/submissions', 'plagiarism');
        }

        upgrade_plugin_savepoint(true, 2015052100, 'plagiarism', 'urkund');
    }

    if ($oldversion < 2015102800) {
        // Set new opt-out setting as true by default.
        set_config('urkund_optout', 1, 'plagiarism');

        upgrade_plugin_savepoint(true, 2015102800, 'plagiarism', 'urkund');
    }
    if ($oldversion < 2015112400) {
        global $OUTPUT;
        // Check to make sure no events are still in the queue as these will be deleted/ignored.
         $sql = "SELECT count(*) FROM {events_queue_handlers} qh
                   JOIN {events_handlers} eh ON qh.handlerid = eh.id
                  WHERE eh.component = 'plagiarism_urkund' AND
                        (eh.eventname = 'assessable_file_uploaded' OR
                         eh.eventname = 'assessable_content_uploaded' OR
                         eh.eventname = 'assessable_submitted')";
        $countevents = $DB->count_records_sql($sql);
        if (!empty($countevents)) {
            echo $OUTPUT->notification(get_string('cannotupgradeunprocesseddata', 'plagiarism_urkund'));
            return false;
        }

        upgrade_plugin_savepoint(true, 2015112400, 'plagiarism', 'urkund');
    }
    if ($oldversion < 2015121401) {
        if (!$DB->record_exists('plagiarism_urkund_config', array('name' => 'urkund_allowallfile', 'cm' => 0))) {
            // Set appropriate defaults for new setting.
            $newelement = new Stdclass();
            $newelement->cm = 0;
            $newelement->name = 'urkund_allowallfile';
            $newelement->value = 1;
            $DB->insert_record('plagiarism_urkund_config', $newelement);

            upgrade_plugin_savepoint(true, 2015121401, 'plagiarism', 'urkund');
        }

    }
    if ($oldversion < 2015122901) {
        // Fix incorrect URKUND config.
        // Assignments that do not have submission drafts set but
        // URKUND is set to only send on submit drafts.
        $sql = "SELECT u.* from {plagiarism_urkund_config} u
                  JOIN {course_modules} cm ON cm.id = u.cm
                  JOIN {assign} a ON a.id = cm.instance
                  JOIN {modules} m ON m.id = cm.module
                 WHERE u.name = 'urkund_draft_submit'
                       AND m.name = 'assign'
                       AND a.submissiondrafts = '0'
                       AND u.value = '1'";
        $urkundconfig = $DB->get_recordset_sql($sql);
        foreach ($urkundconfig as $uc) {
            $uc->value = 0;
            $DB->update_record('plagiarism_urkund_config', $uc);
        }
        $urkundconfig->close();
        upgrade_plugin_savepoint(true, 2015122901, 'plagiarism', 'urkund');
    }

    if ($oldversion < 2016061600) {

        // Define field relateduserid to be added to plagiarism_urkund_files.
        $table = new xmldb_table('plagiarism_urkund_files');
        $field = new xmldb_field('relateduserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'userid');

        // Conditionally launch add field relateduserid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Urkund savepoint reached.
        upgrade_plugin_savepoint(true, 2016061600, 'plagiarism', 'urkund');
    }

    if ($oldversion < 2017072500) {

        // Set advanceditems setting.
        if (!$DB->record_exists('plagiarism_urkund_config', array('cm' => 0, 'name' => 'urkund_advanceditems'))) {
            $advsetting = new stdClass();
            $advsetting->cm = 0;
            $advsetting->name = 'urkund_advanceditems';
            $advsetting->value = 'urkund_receiver,urkund_studentemail,urkund_allowallfile,urkund_selectfiletypes';
            $DB->insert_record('plagiarism_urkund_config', $advsetting);
        }

        // Urkund savepoint reached.
        upgrade_plugin_savepoint(true, 2017072500, 'plagiarism', 'urkund');
    }

    if ($oldversion < 2017081402) {
        // Check to see if this has already been completed.
        $sql = "cm = 0 AND (name LIKE '%_assign' OR name LIKE '%_forum' OR name LIKE '%_workshop')";
        if (!$DB->record_exists_select('plagiarism_urkund_config', $sql)) {
            $defaults = $DB->get_records('plagiarism_urkund_config', array('cm' => 0));

            // Store list of id's to delete.
            $defaultsdelete = array();
            foreach ($defaults as $default) {
                $defaultsdelete[] = $default->id;
            }

            // Set the existing default as the default for assign/forum/workshop.
            $supportedmodules = array('assign', 'forum', 'workshop');
            foreach ($supportedmodules as $sm) {
                foreach ($defaults as $default) {
                    $newitem = clone $default;
                    unset($newitem->id);
                    $newitem->name .= '_'.$sm;
                    $DB->insert_record('plagiarism_urkund_config', $newitem);
                }
            }

            // Delete old records.
            foreach ($defaults as $default) {
                $DB->delete_records_list('plagiarism_urkund_config', 'id', $defaultsdelete);
            }
        }

        // Urkund savepoint reached.
        upgrade_plugin_savepoint(true, 2017081402, 'plagiarism', 'urkund');
    }

    if ($oldversion < 2018011700) {

        // Define field revision to be added to plagiarism_urkund_files.
        $table = new xmldb_table('plagiarism_urkund_files');
        $field = new xmldb_field('revision', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timesubmitted');

        // Conditionally launch add field revision.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Urkund savepoint reached.
        upgrade_plugin_savepoint(true, 2018011700, 'plagiarism', 'urkund');
    }

    return true;
}
