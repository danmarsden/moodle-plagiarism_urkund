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

    if ($oldversion < 2019101800) {
        if (!$DB->record_exists('plagiarism_urkund_config', array('name' => 'urkund_userpref', 'cm' => 0))) {
            // Set appropriate defaults for new setting.
            $newelement = new Stdclass();
            $newelement->cm = 0;
            $newelement->name = 'urkund_userpref';
            $newelement->value = 1;
            $DB->insert_record('plagiarism_urkund_config', $newelement);

            upgrade_plugin_savepoint(true, 2019101800, 'plagiarism', 'urkund');
        }
    }

    if ($oldversion < 2020020700) {
        // Conversion of old config_plugin settings.
        $oldsettings = get_config('plagiarism');
        foreach ($oldsettings as $setting => $value) {
            if (strpos($setting, 'urkund_') !== false) {
                if ($setting == 'urkund_use') { // Not deprecated yet - see MDL-67872.
                    // Not deprecated yet, so don't delete.
                    // Internal plugin code now checks plugin->enabled setting so we need to set both.
                    set_config('enabled', $value, 'plagiarism_urkund');
                } else {
                    $newsetting = substr($setting, 7); // Strip urkund from the start of this setting.
                    // Set new setting.
                    set_config($newsetting, $value, 'plagiarism_urkund');

                    // Remove old settings.
                    set_config($setting, null, 'plagiarism');
                }
            }
        }

        upgrade_plugin_savepoint(true, 2020020700, 'plagiarism', 'urkund');
    }

    if ($oldversion < 2020021300) {
        $DB->delete_records('user_preferences', array('name' => 'urkund_receiver'));

        upgrade_plugin_savepoint(true, 2020021300, 'plagiarism', 'urkund');
    }

    if ($oldversion < 2020031900) {
        if (get_config('plagiarism_urkund', 'unitid') != 0) {
            $plagiarismdefaults = $DB->get_records_menu('plagiarism_urkund_config',
                array('cm' => 0), '', 'name, value'); // The cmid(0) is the default list.
            $supportedmodules = urkund_supported_modules();
            foreach ($supportedmodules as $sm) {
                $element = 'urkund_receiver';
                $element .= "_" . $sm;
                $newelement = new Stdclass();
                $newelement->cm = 0;
                $newelement->name = $element;
                $newelement->value = '';
                if (isset($plagiarismdefaults[$element])) {
                    $newelement->id = $DB->get_field('plagiarism_urkund_config', 'id', (array('cm' => 0, 'name' => $element)));
                    $DB->update_record('plagiarism_urkund_config', $newelement);
                }
            }

            upgrade_plugin_savepoint(true, 2020031900, 'plagiarism', 'urkund');
        }
    }

    if ($oldversion < 2020033000) {
        // Delete old urkund_use setting.
        set_config('urkund_use', null, 'plagiarism');

        upgrade_plugin_savepoint(true, 2020033000, 'plagiarism', 'urkund');
    }

    if ($oldversion < 2020051100) {
        // Check for old API address and update if required.
        $apiaddress = get_config('plagiarism_urkund', 'api');
        if ($apiaddress == 'https://secure.urkund.com/api/submissions') {
            set_config('api', 'https://secure.urkund.com', 'plagiarism_urkund');
        }

        upgrade_plugin_savepoint(true, 2020051100, 'plagiarism', 'urkund');
    }

    if ($oldversion < 2020062500) {
        require_once($CFG->dirroot . '/plagiarism/urkund/lib.php');
        // New setting storeddocuments - find all existing coursemodules and set these to "yes".
        if (!$DB->record_exists('plagiarism_urkund_config', array('name' => 'urkund_storedocuments'))) {
            // If we don't have any existing activities with this setting it has just been added to the site.
            $sql = "SELECT distinct cm
                FROM {plagiarism_urkund_config}
                WHERE cm <> 0";
            $cms = $DB->get_records_sql($sql);
            $records = array();
            foreach ($cms as $cm) {
                $con = new stdClass();
                $con->cm = $cm->cm;
                $con->name = 'urkund_storedocuments';
                $con->value = 1;
                $records[] = $con;
            }
            if (!empty($records)) {
                $DB->insert_records('plagiarism_urkund_config', $records);
            }
        }

        // Now set overall default for all supported modules to Yes.
        $supportedmodules = urkund_supported_modules();
        $plagiarismdefaults = $DB->get_records_menu('plagiarism_urkund_config',
            array('cm' => 0), '', 'name, value'); // The cmid(0) is the default list.
        foreach ($supportedmodules as $sm) {
            $element = 'urkund_storedocuments';
            $element .= "_" . $sm;
            // Check if new setting is set to something.
            if (!isset($plagiarismdefaults[$element])) {
                $newelement = new stdclass();
                $newelement->cm = 0;
                $newelement->name = $element;
                $newelement->value = 1;
                $DB->insert_record('plagiarism_urkund_config', $newelement);
            }
        }

        upgrade_plugin_savepoint(true, 2020062500, 'plagiarism', 'urkund');
    }

    if ($oldversion < 2021030100) {
        // Check Student disclosure setting - if set to old default, change it to the new one.

        $disclosure = get_config('plagiarism_urkund', 'student_disclosure');
        $newstring = str_replace("URKUND", "Ouriginal", $disclosure);
        if ($disclosure != $newstring) {
            set_config('student_disclosure', $newstring, 'plagiarism_urkund');
        }

        upgrade_plugin_savepoint(true, 2021030100, 'plagiarism', 'urkund');
    }

    if ($oldversion < 2021080901) {

        // Define field errorcode to be added to plagiarism_urkund_files.
        $table = new xmldb_table('plagiarism_urkund_files');
        $field = new xmldb_field('errorcode', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'errorresponse');

        // Conditionally launch add field errorcode.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Now look at historical error response messages (limited to last 12 months) and populate errorcode dir.
        $errors = $DB->get_recordset_select('plagiarism_urkund_files',
            "statuscode = 'error' AND errorresponse != '' AND timesubmitted > ?",
                  [time() - YEARSECS]);
        foreach ($errors as $er) {
            $errorcode = plagiarism_get_errorcode_from_jsonresponse($er->errorresponse);
            if (!empty($errorcode)) {
                $er->errorcode = $errorcode;
                $DB->update_record('plagiarism_urkund_files', $er);
            }
        }
        $errors->close();

        // Urkund savepoint reached.
        upgrade_plugin_savepoint(true, 2021080901, 'plagiarism', 'urkund');
    }

    return true;
}
