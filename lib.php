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
 * lib.php - Contains Plagiarism plugin specific functions called by Modules.
 *
 * @since 2.0
 * @package    plagiarism_urkund
 * @subpackage plagiarism
 * @author     Dan Marsden <dan@danmarsden.com>
 * @copyright  2010 Dan Marsden http://danmarsden.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

//get global class
global $CFG;
require_once($CFG->dirroot.'/plagiarism/lib.php');

define('URKUND_MAX_SUBMISSION_ATTEMPTS', 6); //maximum number of times to try and send a submission to URKUND
define('URKUND_MAX_SUBMISSION_DELAY', 60); //maximum time to wait between submissions (defined in minutes)
define('URKUND_SUBMISSION_DELAY', 15); //initial wait time - this is doubled each time a check is made until the max_submission_delay is met.
define('URKUND_MAX_STATUS_ATTEMPTS', 10); //maximum number of times to try and obtain the status of a submission.
define('URKUND_MAX_STATUS_DELAY', 1440); //maximum time to wait between checks (defined in minutes)
define('URKUND_STATUS_DELAY', 30); //initial wait time - this is doubled each time a check is made until the max_status_delay is met.
///// Turnitin Class ////////////////////////////////////////////////////
class plagiarism_plugin_urkund extends plagiarism_plugin {
    /**
    * This function should be used to initialise settings and check if plagiarism is enabled
    * *
    * @return mixed - false if not enabled, or returns an array of relavant settings.
    */
    public function get_settings() {
        global $DB;
        $plagiarismsettings = (array)get_config('plagiarism');
        //check if tii enabled.
        if (isset($plagiarismsettings['urkund_use']) && $plagiarismsettings['urkund_use']) {
            //now check to make sure required settings are set!
            if (empty($plagiarismsettings['urkund_api'])) {
                error("URKUND API URL not set!");
            }
            return $plagiarismsettings;
        } else {
            return false;
        }
    }
    public function config_options() {
        return array('use_urkund','urkund_show_student_score','urkund_show_student_report',
                     'urkund_draft_submit','urkund_receiver');
    }
     /**
     * hook to allow plagiarism specific information to be displayed beside a submission 
     * @param array  $linkarraycontains all relevant information for the plugin to generate a link
     * @return string
     * 
     */
    public function get_links($linkarray) {
        //$userid, $file, $cmid, $course, $module
        $cmid = $linkarray['cmid'];
        $userid = $linkarray['userid'];
        $file = $linkarray['file'];
        $output = '';
        //add link/information about this file to $output
         
        return $output;
    }

    /* hook to save plagiarism specific settings on a module settings page
     * @param object $data - data from an mform submission.
    */
    public function save_form_elements($data) {

    }

    /**
     * hook to add plagiarism specific settings to a module settings page
     * @param object $mform  - Moodle form
     * @param object $context - current context
     */
    public function get_form_elements_module($mform, $context) {
        global $CFG, $DB;
        if (!$this->get_settings()) {
            return;
        }
        $cmid = optional_param('update', 0, PARAM_INT); //there doesn't seem to be a way to obtain the current cm a better way - $this->_cm is not available here.
        if (!empty($cmid)) {
            $plagiarismvalues = $DB->get_records_menu('urkund_config', array('cm'=>$cmid),'','name,value');
        }
        $plagiarismdefaults = $DB->get_records_menu('urkund_config', array('cm'=>0),'','name,value'); //cmid(0) is the default list.
        $plagiarismelements = $this->config_options();
        if (has_capability('moodle/plagiarism_urkund:enable', $context)) {
            urkund_get_form_elements($mform);
            if ($mform->elementExists('urkund_draft_submit')) {
                $mform->disabledIf('urkund_draft_submit', 'var4', 'eq', 0);
            }
            //disable all plagiarism elements if use_plagiarism eg 0
            foreach ($plagiarismelements as $element) {
                if ($element <> 'use_urkund') { //ignore this var
                    $mform->disabledIf($element, 'use_urkund', 'eq', 0);
                }
            }
        } else { //add plagiarism settings as hidden vars.
            foreach ($plagiarismelements as $element) {
                $mform->addElement('hidden', $element);
            }
        }
        //now set defaults.
        foreach ($plagiarismelements as $element) {
            if (isset($plagiarismvalues[$element])) {
                $mform->setDefault($element, $plagiarismvalues[$element]);
            } else if (isset($plagiarismdefaults[$element])) {
                $mform->setDefault($element, $plagiarismdefaults[$element]);
            }
        }

    }

    /**
     * hook to allow a disclosure to be printed notifying users what will happen with their submission
     * @param int $cmid - course module id
     * @return string
     */
    public function print_disclosure($cmid) {
        global $OUTPUT;
        $plagiarismsettings = (array)get_config('plagiarism');
        //TODO: check if this cmid has plagiarism enabled.
        echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        echo format_text($plagiarismsettings['urkund_student_disclosure'], FORMAT_MOODLE, $formatoptions);
        echo $OUTPUT->box_end();
    }

    /**
     * hook to allow status of submitted files to be updated - called on grading/report pages.
     *
     * @param object $course - full Course object
     * @param object $cm - full cm object
     */
    public function update_status($course, $cm) {
        //called at top of submissions/grading pages - allows printing of admin style links or updating status
    }

    /**
     * called by admin/cron.php 
     *
     */
    public function cron() {
        //do any scheduled task stuff
    }
    public function event_handler($eventdata) {
        global $DB, $CFG;
        $result = true;
        $supportedmodules = array('assignment');
        if (empty($eventdata->modulename) || !in_array($eventdata->modulename, $supportedmodules)) {
            debugging("this module isn't handled:".$eventdata->modulename); //TODO: remove this debug when working.
            return true;
        }

        $plagiarismsettings = $this->get_settings();
        $cmid = (!empty($eventdata->cm->id)) ? $eventdata->cm->id : $eventdata->cmid;
        $plagiarismvalues = $DB->get_records_menu('urkund_config', array('cm'=>$cmid),'','name,value');
        if (!$plagiarismsettings || empty($plagiarismvalues['use_urkund'])) {
            //nothing to do here... move along!
            return $result;
        }

        if ($eventdata->eventtype=="file_uploaded") {
            // check if the module associated with this event still exists
            if (!$DB->record_exists('course_modules', array('id' => $eventdata->cmid))) {
                return $result;
            }

            if (!empty($eventdata->file) && empty($eventdata->files)) { //single assignment type passes a single file
                $eventdata->files[] = $eventdata->file;
            }
            if (!empty($eventdata->files)) { //this is an upload event with multiple files
                foreach ($eventdata->files as $efile) {
                    if ($efile->get_filename() ==='.') {
                        continue;
                    }
                    //hacky way to check file still exists
                    $fs = get_file_storage();
                    $fileid = $fs->get_file_by_id($efile->get_id());
                    if (empty($fileid)) {
                        mtrace("nofilefound!");
                        continue;
                    }
                    if (empty($plagiarismvalues['plagiarism_draft_submit'])) { //check if this is an advanced assignment and shouldn't send the file yet.
                        $result = urkund_send_file($cmid, $eventdata->userid, $efile, $plagiarismsettings);
                    }
                }
            } else { //this is a finalize event
                mtrace("finalise");
                if (isset($plagiarismvalues['plagiarism_draft_submit']) && $plagiarismvalues['plagiarism_draft_submit'] == 1) { // is file to be sent on final submission?
                    require_once("$CFG->dirroot/mod/assignment/lib.php"); //HACK to include filelib so that when event cron is run then file_storage class is available
                    // we need to get a list of files attached to this assignment and put them in an array, so that
                    // we can submit each of them for processing.
                    $assignmentbase = new assignment_base($cmid);
                    $submission = $assignmentbase->get_submission($eventdata->userid);
                    $modulecontext = get_context_instance(CONTEXT_MODULE, $eventdata->cmid);
                    $fs = get_file_storage();
                    if ($files = $fs->get_area_files($modulecontext->id, 'mod_assignment', 'submission', $submission->id, "timemodified")) {
                        foreach ($files as $file) {
                            if ($file->get_filename()==='.') {
                                continue;
                            }
                            $result = urkund_send_file($cmid, $eventdata->userid, $file, $plagiarismsettings);
                        }
                    }
                }
            }
            return $result;
        } else {
            //return true; //Don't need to handle this event
        }
    }
}

function event_file_uploaded($eventdata) {
    $eventdata->eventtype = 'file_uploaded';
    $urkund = new plagiarism_plugin_urkund();
    return $urkund->event_handler($eventdata);
}
function event_files_done($eventdata) {
    $eventdata->eventtype = 'file_uploaded';
    $urkund = new plagiarism_plugin_urkund();
    return $urkund->event_handler($eventdata);
}

function event_mod_created($eventdata) {
    $result = true;
        //a new module has been created - this is a generic event that is called for all module types
        //make sure you check the type of module before handling if needed.

    return $result;
}

function event_mod_updated($eventdata) {
    $result = true;
        //a module has been updated - this is a generic event that is called for all module types
        //make sure you check the type of module before handling if needed.

    return $result;
}

function event_mod_deleted($eventdata) {
    $result = true;
        //a module has been deleted - this is a generic event that is called for all module types
        //make sure you check the type of module before handling if needed.

    return $result;
}

/**
* adds the list of plagiarism settings to a form
*
* @param object $mform - Moodle form object
*/
function urkund_get_form_elements($mform) {
    $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
    $tiioptions = array(0 => get_string("never"), 1 => get_string("always"), 2 => get_string("showwhenclosed", "plagiarism_urkund"));
    $tiidraftoptions = array(0 => get_string("submitondraft","plagiarism_urkund"), 1 => get_string("submitonfinal","plagiarism_urkund"));

    $mform->addElement('header', 'plagiarismdesc');
    $mform->addElement('select', 'use_urkund', get_string("useurkund", "plagiarism_urkund"), $ynoptions);
    $mform->addElement('text', 'urkund_receiver', get_string("urkund_receiver", "plagiarism_urkund"));
    $mform->addHelpButton('urkund_receiver', 'urkund_receiver', 'plagiarism_urkund');
    $mform->addElement('select', 'urkund_show_student_score', get_string("urkund_show_student_score", "plagiarism_urkund"), $tiioptions);
    $mform->addHelpButton('urkund_show_student_score', 'urkund_show_student_score', 'plagiarism_urkund');
    $mform->addElement('select', 'urkund_show_student_report', get_string("urkund_show_student_report", "plagiarism_urkund"), $tiioptions);
    $mform->addHelpButton('urkund_show_student_report', 'urkund_show_student_report', 'plagiarism_urkund');
    if ($mform->elementExists('var4')) {
       $mform->addElement('select', 'urkund_draft_submit', get_string("urkund_draft_submit", "plagiarism_urkund"), $tiidraftoptions);
    }
}

/**
* updates a turnitin_files record
*
* @param int $cmid - course module id
* @param int $userid - user id
* @param varied $identifier - identifier for this plagiarism record - hash of file, id of quiz question etc
* @return int - id of turnitin_files record
*/
function urkund_update_record($cmid, $userid, $identifier) {
    global $DB;

    //now update or insert record into turnitin_files
    $plagiarism_file = $DB->get_record_sql(
                                "SELECT * FROM {urkund_files}
                                 WHERE cm = ? AND userid = ? AND " .
                                $DB->sql_compare_text('identifier') . " = ?",
                                array($cmid, $userid,$identifier));
    if (!empty($plagiarism_file)) {
            return $plagiarism_file;
    } else {
        $plagiarism_file = new object();
        $plagiarism_file->cm = $cmid;
        $plagiarism_file->userid = $userid;
        $plagiarism_file->identifier = $identifier;
        $plagiarism_file->statuscode = 'pending';
        $plagiarism_file->attempt = 0;
        $plagiarism_file->timesubmitted = time();
        if (!$pid = $DB->insert_record('urkund_files', $plagiarism_file)) {
            debugging("insert into urkund_files failed");
        }
        $plagiarism_file->id = $pid;
        return $plagiarism_file;
    }
}
function urkund_send_file($cmid, $userid, $file, $plagiarismsettings) {
    $plagiarism_file = urkund_update_record($cmid, $userid, $file->get_contenthash());

    //check if $plagiarism_file actually needs to be submitted.
    if ($plagiarism_file->statuscode <> 'pending') {
        return true;
    }
    //check if we need to delay this submission
    $attemptallowed = urkund_check_attempt_timeout($plagiarism_file);
    if (!$attemptallowed) {
        return false;
    }

    return urkund_send_file_to_urkund($plagiarism_file, $plagiarismsettings, $file);


}
//function to check timesubmitted and attempt to see if we need to delay an API check.
//also checks max attempts to see if it has exceeded.
function urkund_check_attempt_timeout($plagiarism_file) {
    global $DB;
    //the first time a file is submitted we don't need to wait at all.
    if (empty($plagiarism_file->attempt) && $plagiarism_file->statuscode == 'pending') {
        return true;
    }
    $now = time();
    if ($plagiarism_file->statuscode == 'pending') {
        $submissiondelay = URKUND_SUBMISSION_DELAY; //initial wait time - this is doubled each time a check is made until the max delay is met.
        $maxsubmissiondelay = URKUND_MAX_SUBMISSION_DELAY; //maximum time to wait between submissions
        $maxattempts = URKUND_MAX_SUBMISSION_ATTEMPTS; //maximum number of times to try and send a submission.
    } elseif ($plagiarism_file->statuscode =='submitted') {
        $submissiondelay = URKUND_STATUS_DELAY; //initial wait time - this is doubled each time a check is made until the max delay is met.
        $maxsubmissiondelay = URKUND_MAX_STATUS_DELAY; //maximum time to wait between checks
        $maxattempts = URKUND_MAX_STATUS_ATTEMPTS; //maximum number of times to try and send a submission.
    }
    $wait = $submissiondelay;
    //check if we have exceeded the max attempts
    if ($plagiarism_file->attempt > $maxattempts) {
        $plagiarism_file->statuscode = 'timeout';
        $DB->update_record('urkund_files', $plagiarism_file);
        return true; //return true to cancel the event.
    }
    //now calculate wait time.
    $i= 0;
    while ($i < $plagiarism_file->attempt) {
        if ($wait > $maxsubmissiondelay) {
            $wait = $maxsubmissiondelay;
        }
        $wait = $wait * $plagiarism_file->attempt;
        $i++;
    }
    //calculate when this should be checked next
    $timetocheck = $plagiarism_file->timesubmitted +($wait*60); //$wait is in minutes - multiply to get seconds.
    if ($timetocheck > $now) {
        return true;
    } else {
        return false;
    }
}

function urkund_send_file_to_urkund($plagiarism_file, $plagiarismsettings, $file) {

}