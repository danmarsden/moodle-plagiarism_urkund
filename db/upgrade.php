<?php

// This file keeps track of upgrades to
// the plagiarism URKUND plugin
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

/**
 * @global moodle_database $DB
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
    if ($oldversion < 2012050904) {
        require_once($CFG->dirroot . '/plagiarism/urkund/lib.php');
        // We have changed the way files are identified to urkund - we need to check for files that have been
        // submitted using the old indentifier format but haven't had a report returned.
        $sql = "UPDATE {plagiarism_urkund_files}
                   SET statuscode = '".URKUND_STATUSCODE_ACCEPTED_OLD."'".
               " WHERE statuscode = '".URKUND_STATUSCODE_ACCEPTED."'";
        $DB->execute($sql);
        upgrade_plugin_savepoint(true, 2012050904, 'plagiarism', 'urkund');
    }

    if ($oldversion < 2012050908) {
        // Check for old API address and update if required.
        $apiaddress = get_config('plagiarism', 'urkund_api');
        if ($apiaddress == 'https://secure.urkund.com/ws/integration/1.0/rest/submissions' ||
            $apiaddress == 'https://secure.urkund.com/api/rest/submissions' ||
            $apiaddress == 'https://secure.urkund.com/api') {

            set_config('urkund_api', 'https://secure.urkund.com/api/submissions', 'plagiarism');
        }

        upgrade_plugin_savepoint(true, 2012050908, 'plagiarism', 'urkund');
    }

    return true;
}