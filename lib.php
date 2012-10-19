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
 * @copyright  2011 Dan Marsden http://danmarsden.com
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
define('URKUND_SUBMISSION_DELAY', 15); //initial wait time - this is doubled each time a check is made until the max_submission_delay is met
define('URKUND_MAX_STATUS_ATTEMPTS', 10); //maximum number of times to try and obtain the status of a submission.
define('URKUND_MAX_STATUS_DELAY', 1440); //maximum time to wait between checks (defined in minutes)
define('URKUND_STATUS_DELAY', 30); //initial wait time - this is doubled each time a check is made until the max_status_delay is met.
define('URKUND_STATUSCODE_PROCESSED', '200');
define('URKUND_STATUSCODE_ACCEPTED', '202');
define('URKUND_STATUSCODE_BAD_REQUEST', '400');
define('URKUND_STATUSCODE_NOT_FOUND', '404');
define('URKUND_STATUSCODE_UNSUPPORTED', '415');
define('URKUND_STATUSCODE_TOO_LARGE', '413');
define('URKUND_STATUSCODE_NORECEIVER', '444');

define('URKUND_FILETYPE_URL','https://secure.urkund.com/ws/integration/accepted-formats.xml'); //url to external xml that states URKUNDS allowed file type list.
define('URKUND_FILETYPE_URL_UPDATE','168'); //how often to check for updated file types (defined in hours)

define('PLAGIARISM_URKUND_SHOW_NEVER', 0);
define('PLAGIARISM_URKUND_SHOW_ALWAYS', 1);
define('PLAGIARISM_URKUND_SHOW_CLOSED', 2);

define('PLAGIARISM_URKUND_DRAFTSUBMIT_IMMEDIATE', 0);
define('PLAGIARISM_URKUND_DRAFTSUBMIT_FINAL', 1);
///// URKUND Class ////////////////////////////////////////////////////
class plagiarism_plugin_urkund extends plagiarism_plugin {
    /**
     * This function should be used to initialise settings and check if plagiarism is enabled
     * *
     * @return mixed - false if not enabled, or returns an array of relevant settings.
     */
    public function get_settings() {
        global $DB;
        static $plagiarismsettings;
        if (!empty($plagiarism_settings) || $plagiarismsettings === false) {
            return $plagiarismsettings;
        }
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
    /**
     * function which returns an array of all the module instance settings
     *
     * @return array
     *
     */
    public function config_options() {
        return array('use_urkund', 'urkund_show_student_score', 'urkund_show_student_report',
                     'urkund_draft_submit', 'urkund_receiver','urkund_studentemail');
    }
    /**
     * hook to allow plagiarism specific information to be displayed beside a submission
     * @param array  $linkarraycontains all relevant information for the plugin to generate a link
     * @return string
     *
     */
    public function get_links($linkarray) {
        global $DB, $USER, $COURSE, $OUTPUT, $CFG;
        $cmid = $linkarray['cmid'];
        $userid = $linkarray['userid'];
        $file = $linkarray['file'];
        $results = $this->get_file_results($cmid, $userid, $file);
        if (empty($results)) {
            // Info about this file is not available to this user
            return '';
        }
        $modulecontext = get_context_instance(CONTEXT_MODULE, $cmid);

        $output = '';
        if ($results['statuscode'] == 'pending') {
            //TODO: check to make sure there is a pending event entry for this file - if not add one.
            $output .= '<span class="plagiarismreport">'.
                       '<img src="'.$OUTPUT->pix_url('processing', 'plagiarism_urkund') .
                        '" alt="'.get_string('pending', 'plagiarism_urkund').'" '.
                        '" title="'.get_string('pending', 'plagiarism_urkund').'" />'.
                        '</span>';
            return $output;
        }
        if ($results['statuscode'] == 'Analyzed') {
            // Normal situation - URKUND has successfully analyzed the file
            $rank = urkund_get_css_rank($results['score']);
            $output .= '<span class="plagiarismreport">';
            if (!empty($results['reporturl'])) {
                // User is allowed to view the report
                // Score is contained in report, so they can see the score too.
                $output .= '<a href="'.$results['reporturl'].'" target="_blank">';
                $output .= get_string('similarity', 'plagiarism_urkund') . ':';
                $output .= '<span class="'.$rank.'">'.$results['score'].'%</span>';
                $output .= '</a>';
            } elseif ($results['score'] !== '') {
                // User is allowed to view only the score
                $output .= get_string('similarity', 'plagiarism_urkund') . ':';
                $output .= '<span class="' . $rank . '">' . $results['score'] . '%</span>';
            }
            if (!empty($results['optoutlink'])) {
                //display opt-out link
                $output .= '&nbsp;<span class"plagiarismoptout">' .
                        '<a href="' . $results['optoutlink'] . '" target="_blank">' .
                        get_string('optout','plagiarism_urkund') .
                        '</a></span>';
            }
            if (!empty($results['renamed'])) {
                $output .= $results['renamed'];
            }
            $output .= '</span>';
        } elseif ($results['statuscode'] == URKUND_STATUSCODE_ACCEPTED) {
            $output .= '<span class="plagiarismreport">'.
                       '<img src="'.$OUTPUT->pix_url('processing', 'plagiarism_urkund') .
                        '" alt="'.get_string('processing', 'plagiarism_urkund').'" '.
                        '" title="'.get_string('processing', 'plagiarism_urkund').'" />'.
                        '</span>';
        } elseif ($results['statuscode'] == URKUND_STATUSCODE_UNSUPPORTED) {
            $output .= '<span class="plagiarismreport">'.
                       '<img src="'.$OUTPUT->pix_url('warning', 'plagiarism_urkund') .
                        '" alt="'.get_string('unsupportedfiletype', 'plagiarism_urkund').'" '.
                        '" title="'.get_string('unsupportedfiletype', 'plagiarism_urkund').'" />'.
                        '</span>';
        } elseif ($results['statuscode'] == URKUND_STATUSCODE_TOO_LARGE) {
            $output .= '<span class="plagiarismreport">'.
                       '<img src="'.$OUTPUT->pix_url('warning', 'plagiarism_urkund') .
                        '" alt="'.get_string('toolarge', 'plagiarism_urkund').'" '.
                        '" title="'.get_string('toolarge', 'plagiarism_urkund').'" />'.
                        '</span>';
        } else {
            $title = get_string('unknownwarning', 'plagiarism_urkund');
            $reset = '';
            if (has_capability('moodle/plagiarism_urkund:resetfile', $modulecontext) &&
                !empty($results['error'])) { //this is a teacher viewing the responses.
                //strip out some possible known text to tidy it up
                $erroresponse = format_text($results['error'], FORMAT_PLAIN);
                $erroresponse = str_replace('{&quot;LocalisedMessage&quot;:&quot;','', $erroresponse);
                $erroresponse = str_replace('&quot;,&quot;Message&quot;:null}','', $erroresponse);
                $title .= ': ' . $erroresponse;
                $url = new moodle_url('/plagiarism/urkund/reset.php', array('cmid'=>$cmid, 'pf'=>$results['pid'], 'sesskey'=>sesskey()));
                $reset = "<a href='$url'>".get_string('reset')."</a>";
            }
            $output .= '<span class="plagiarismreport">'.
                       '<img src="'.$OUTPUT->pix_url('warning', 'plagiarism_urkund') .
                        '" alt="'.get_string('unknownwarning', 'plagiarism_urkund').'" '.
                        '" title="'.$title.'" />'.$reset.'</span>';
        }
        return $output;
    }

    function get_file_results($cmid, $userid, $file) {
        global $DB, $USER, $COURSE, $CFG;
        $plagiarismsettings = $this->get_settings();
        if (empty($plagiarismsettings)) {
            // Urkund is not enabled
            return false;
        }
        $plagiarismvalues = urkund_cm_use($cmid);
        if (empty($plagiarismvalues)) {
            // Urkund not enabled for this cm
            return false;
        }

        // Collect detail about the specified coursemodule
        $filehash = $file->get_contenthash();
        $modulesql = 'SELECT m.id, m.name, cm.instance'.
                ' FROM {course_modules} cm' .
                ' INNER JOIN {modules} m on cm.module = m.id ' .
                'WHERE cm.id = ?';
        $moduledetail = $DB->get_record_sql($modulesql, array($cmid));
        if (!empty($moduledetail)) {
            $sql = "SELECT * FROM " . $CFG->prefix . $moduledetail->name . " WHERE id= ?";
            $module = $DB->get_record_sql($sql, array($moduledetail->instance));
        }
        if (empty($module)) {
            // No such cmid
            return false;
        }

        $modulecontext = get_context_instance(CONTEXT_MODULE, $cmid);
        // If the user has permission to see result of all items in this course module.
        $viewscore = $viewreport = has_capability('moodle/plagiarism_urkund:viewreport', $modulecontext);

        // Determine if the activity is closed
        // If report is closed, this can make the report available to more users
        $assignclosed = false;
        $time = time();
        if (!empty($module->preventlate) && !empty($module->timedue)) {
            $assignclosed = ($module->timeavailable <= $time && $time <= $module->timedue);
        } elseif (!empty($module->timeavailable)) {
            $assignclosed = ($module->timeavailable <= $time);
        }

        // Under certain circumstances, users are allowed to see plagiarism info
        // even if they don't have view report capability
        if ($USER->id == $userid) {
            $selfreport = true;
            if (isset($plagiarismvalues['urkund_show_student_report']) &&
                    ($plagiarismvalues['urkund_show_student_report']== PLAGIARISM_URKUND_SHOW_ALWAYS ||
                     $plagiarismvalues['urkund_show_student_report']== PLAGIARISM_URKUND_SHOW_CLOSED && $assignclosed)) {
                $viewreport = true;
            }
            if (isset($plagiarismvalues['urkund_show_student_score']) &&
                    ($plagiarismvalues['urkund_show_student_score']== PLAGIARISM_URKUND_SHOW_ALWAYS) ||
                    ($plagiarismvalues['urkund_show_student_score']== PLAGIARISM_URKUND_SHOW_CLOSED && $assignclosed)) {
                $viewscore = true;
            }
        } else {
            $selfreport = false;
        }
        // End of rights checking.

        if (!$viewscore && !$viewreport && !$selfreport) {
            // User is not permitted to see any details
            return false;
        }
        $plagiarismfile = $DB->get_record_sql(
                    "SELECT * FROM {plagiarism_urkund_files}
                    WHERE cm = ? AND userid = ? AND " .
                    "identifier = ?",
                    array($cmid, $userid, $filehash));
        if (empty($plagiarismfile)) {
            // no record of that submitted file
            return false;
        }

        // Returns after this point will include a result set describing information about
        // interactions with urkund servers.
        $results = array('statuscode' => '', 'error' => '', 'reporturl' => '',
                'score' => '', 'pid' => '', 'optoutlink' => '', 'renamed' => '',
                'analyzed' => 0,
                );
        if ($plagiarismfile->statuscode == 'pending') {
            $results['statuscode'] = 'pending';
            return $results;
        }

        //now check for differing filename and display info related to it.
        $previouslysubmitted = '';
        if ($file->get_filename() !== $plagiarismfile->filename) {
            $previouslysubmitted = '('.get_string('previouslysubmitted','plagiarism_urkund').': '.$plagiarismfile->filename.')';
        }

        $results['statuscode'] = $plagiarismfile->statuscode;
        $results['pid'] = $plagiarismfile->id;
        $results['error'] = $plagiarismfile->errorresponse;
        if ($plagiarismfile->statuscode=='Analyzed') {
            $results['analyzed'] = 1;
            // File has been successfully analyzed - return all appropriate details:
            if ($viewscore || $viewreport) {
                // If user can see the report, they can see the score on the report
                // So make it directly available.
                $results['score'] = $plagiarismfile->similarityscore;
            }
            if ($viewreport) {
                $results['reporturl'] = $plagiarismfile->reporturl;
            }
            if (!empty($plagiarismfile->optout) && $selfreport) {
                $results['optoutlink'] = $plagiarismfile->optout;
            }
            $results['renamed'] = $previouslysubmitted;
        }
        return $results;
    }
    /* hook to save plagiarism specific settings on a module settings page
     * @param object $data - data from an mform submission.
    */
    public function save_form_elements($data) {
        global $DB;
        if (!$this->get_settings()) {
            return;
        }
        if (isset($data->use_urkund)) {
            //array of possible plagiarism config options.
            $plagiarismelements = $this->config_options();
            //first get existing values
            $existingelements = $DB->get_records_menu('plagiarism_urkund_config', array('cm'=>$data->coursemodule), '', 'name, id');
            foreach ($plagiarismelements as $element) {
                $newelement = new object();
                $newelement->cm = $data->coursemodule;
                $newelement->name = $element;
                $newelement->value = (isset($data->$element) ? $data->$element : 0);
                if (isset($existingelements[$element])) { //update
                    $newelement->id = $existingelements[$element];
                    $DB->update_record('plagiarism_urkund_config', $newelement);
                } else { //insert
                    $DB->insert_record('plagiarism_urkund_config', $newelement);
                }

            }
            if (!empty($data->urkund_receiver)) {
                set_user_preference('urkund_receiver', $data->urkund_receiver);
            }
        }
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
        $cmid = optional_param('update', 0, PARAM_INT); //$this->_cm is not available here.
        if (!empty($cmid)) {
            $plagiarismvalues = $DB->get_records_menu('plagiarism_urkund_config', array('cm'=>$cmid), '', 'name, value');
        }
        $plagiarismdefaults = $DB->get_records_menu('plagiarism_urkund_config', array('cm'=>0), '', 'name, value'); //cmid(0) is the default list.
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
            //check if files have been submitted and we need to disable the receiver address:
            if ($DB->record_exists('plagiarism_urkund_files', array('cm' => $cmid, 'statuscode' => 'pending'))) {
                $mform->disabledIf('urkund_receiver','use_urkund');
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
            } else if ($element=='urkund_receiver') {
                $def = get_user_preferences($element);
                if (!empty($def)) {
                    $mform->setDefault($element, $def);
                } else if (isset($plagiarismdefaults[$element])) {
                    $mform->setDefault($element, $plagiarismdefaults[$element]);
                }
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
        global $OUTPUT,$DB;
        $urkunduse = urkund_cm_use($cmid);
        $plagiarismsettings = $this->get_settings();
        if (!empty($plagiarismsettings['urkund_student_disclosure']) &&
            !empty($urkunduse)) {
                echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
                $formatoptions = new stdClass;
                $formatoptions->noclean = true;
                echo format_text($plagiarismsettings['urkund_student_disclosure'], FORMAT_MOODLE, $formatoptions);
                echo $OUTPUT->box_end();
            }
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
        global $CFG;
        //do any scheduled task stuff
        urkund_update_allowed_filetypes();
        //weird hack to include filelib correctly before allowing use in event_handler.
        require_once("$CFG->dirroot/mod/assignment/lib.php");
        if ($plagiarismsettings = $this->get_settings()) {
            urkund_get_scores($plagiarismsettings);
        }
    }
    /**
     * generic handler function for all events - triggers sending of files.
     * @return boolean
     */
    public function event_handler($eventdata) {
        global $DB, $CFG;

        if ($eventdata->eventtype != "file_uploaded") {
            return true; //Don't need to handle this event
        }

        $plagiarismsettings = $this->get_settings();
        if(!$plagiarismsettings) {
            return true;
        }
        $cmid = (!empty($eventdata->cm->id)) ? $eventdata->cm->id : $eventdata->cmid;
        $plagiarismvalues = $DB->get_records_menu('plagiarism_urkund_config', array('cm'=>$cmid), '', 'name, value');
        if (empty($plagiarismvalues['use_urkund'])) {
            // Urkund not in use for this cm - return.
            return true;
        }

        // check if the module associated with this event still exists
        if (!$DB->record_exists('course_modules', array('id' => $eventdata->cmid))) {
            return true;
        }

        if (!empty($eventdata->file) && empty($eventdata->files)) { //single assignment type passes a single file
            $eventdata->files[] = $eventdata->file;
        }

        if (empty($eventdata->files)) {
            // Assignment-specific functionality:
            // This is a 'finalize' event. No files from this event itself,
            // but need to check if files from previous events need to be submitted for processing
            mtrace("finalise");
            $result = true;
            if (isset($plagiarismvalues['plagiarism_draft_submit']) &&
                $plagiarismvalues['plagiarism_draft_submit'] == PLAGIARISM_URKUND_DRAFTSUBMIT_FINAL) {
                // Any files attached to previous events were not submitted.
                // These files are now finalized, and should be submitted for processing.

                require_once("$CFG->dirroot/mod/assignment/lib.php"); //hack to include filelib so that file_storage class is available
                // we need to get a list of files attached to this assignment and put them in an array, so that
                // we can submit each of them for processing.
                $assignmentbase = new assignment_base($cmid);
                $submission = $assignmentbase->get_submission($eventdata->userid);
                $modulecontext = get_context_instance(CONTEXT_MODULE, $eventdata->cmid);
                $fs = get_file_storage();
                if ($files = $fs->get_area_files($modulecontext->id, 'mod_assignment', 'submission', $submission->id, "timemodified", false)) {
                    foreach ($files as $file) {
                        $sendresult = urkund_send_file($cmid, $eventdata->userid, $file, $plagiarismsettings);
                        $result = $result && $sendresult;
                    }
                }
            }
            return $result;
        }

        if (isset($plagiarismvalues['plagiarism_draft_submit']) &&
            $plagiarismvalues['plagiarism_draft_submit'] == PLAGIARISM_URKUND_DRAFTSUBMIT_FINAL) {
            // Assignment-specific functionality:
            // Files should only be sent for checking once "finalized"
            return true;
        }

        // Normal situation: 1 or more assessable files attached to event, ready to be checked:
        $result = true;
        foreach ($eventdata->files as $efile) {
            if ($efile->get_filename() ==='.') {
                // This 'file' is actually a directory - nothing to submit.
                continue;
            }
            // Check file still exists
            $fs = get_file_storage();
            $fileid = $fs->get_file_by_id($efile->get_id());
            if (empty($fileid)) {
                mtrace("nofilefound!");
                continue;
            }

            $sendresult = urkund_send_file($cmid, $eventdata->userid, $efile, $plagiarismsettings);
            $result = $result && $sendresult;
        }
        return $result;
    }

    function urkund_send_student_email($plagiarism_file) {
        global $DB, $CFG;
        if (empty($plagiarism_file->userid)) { //sanity check.
            return false;
        }
        $user = $DB->get_record('user', array('id'=>$plagiarism_file->userid));
        $site = get_site();
        $a = new stdClass();
        $cm = get_coursemodule_from_id('', $plagiarism_file->cm);
        $a->modulename = format_string($cm->name);
        $a->modulelink = $CFG->wwwroot.'/mod/'.$cm->modname.'/view.php?id='.$cm->id;
        $a->coursename = format_string($DB->get_field('course','fullname', array('id'=>$cm->course)));
        $a->optoutlink = $plagiarism_file->optout;
        $emailsubject = get_string('studentemailsubject', 'plagiarism_urkund');
        $emailcontent = get_string('studentemailcontent', 'plagiarism_urkund', $a);
        email_to_user($user, $site->shortname, $emailsubject, $emailcontent);
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
    $urkunddraftoptions = array(
            PLAGIARISM_URKUND_DRAFTSUBMIT_IMMEDIATE => get_string("submitondraft", "plagiarism_urkund"),
            PLAGIARISM_URKUND_DRAFTSUBMIT_FINAL => get_string("submitonfinal", "plagiarism_urkund")
            );

    $mform->addElement('header', 'plagiarismdesc');
    $mform->addElement('select', 'use_urkund', get_string("useurkund", "plagiarism_urkund"), $ynoptions);
    $mform->addElement('text', 'urkund_receiver', get_string("urkund_receiver", "plagiarism_urkund"));
    $mform->addHelpButton('urkund_receiver', 'urkund_receiver', 'plagiarism_urkund');
    $mform->addElement('select', 'urkund_show_student_score', get_string("urkund_show_student_score", "plagiarism_urkund"), $tiioptions);
    $mform->addHelpButton('urkund_show_student_score', 'urkund_show_student_score', 'plagiarism_urkund');
    $mform->addElement('select', 'urkund_show_student_report', get_string("urkund_show_student_report", "plagiarism_urkund"), $tiioptions);
    $mform->addHelpButton('urkund_show_student_report', 'urkund_show_student_report', 'plagiarism_urkund');
    if ($mform->elementExists('var4')) {
        $mform->addElement('select', 'urkund_draft_submit', get_string("urkund_draft_submit", "plagiarism_urkund"), $urkunddraftoptions);
    }
    $mform->addElement('select', 'urkund_studentemail', get_string("urkund_studentemail", "plagiarism_urkund"), $ynoptions);
    $mform->addHelpButton('urkund_studentemail', 'urkund_studentemail', 'plagiarism_urkund');
}

/**
 * updates a urkund_files record
 *
 * @param int $cmid - course module id
 * @param int $userid - user id
 * @param varied $identifier - identifier for this plagiarism record - hash of file, id of quiz question etc
 * @return int - id of urkund_files record
 */
function urkund_get_plagiarism_file($cmid, $userid, $file) {
    global $DB;

    //now update or insert record into urkund_files
    $plagiarism_file = $DB->get_record_sql(
                                "SELECT * FROM {plagiarism_urkund_files}
                                 WHERE cm = ? AND userid = ? AND " .
                                "identifier = ?",
                                array($cmid, $userid, $file->get_contenthash()));
    if (!empty($plagiarism_file)) {
            return $plagiarism_file;
    } else {
        $plagiarism_file = new object();
        $plagiarism_file->cm = $cmid;
        $plagiarism_file->userid = $userid;
        $plagiarism_file->identifier = $file->get_contenthash();
        $plagiarism_file->filename = $file->get_filename();
        $plagiarism_file->statuscode = 'pending';
        $plagiarism_file->attempt = 0;
        $plagiarism_file->timesubmitted = time();
        if (!$pid = $DB->insert_record('plagiarism_urkund_files', $plagiarism_file)) {
            debugging("insert into urkund_files failed");
        }
        $plagiarism_file->id = $pid;
        return $plagiarism_file;
    }
}
function urkund_send_file($cmid, $userid, $file, $plagiarismsettings) {
    global $DB;
    $plagiarism_file = urkund_get_plagiarism_file($cmid, $userid, $file);

    //check if $plagiarism_file actually needs to be submitted.
    if ($plagiarism_file->statuscode <> 'pending') {
        return true;
    }
    if ($plagiarism_file->filename !== $file->get_filename()) {
        //this is a file that was previously submitted and not sent to urkund but the filename has changed so fix it.
        $plagiarism_file->filename = $file->get_filename();
        $DB->update_record('plagiarism_urkund_files', $plagiarism_file);
    }
    //check to see if this is a valid file
    $mimetype = urkund_check_file_type($file->get_filename());
    if (empty($mimetype)) {
        $plagiarism_file->statuscode = URKUND_STATUSCODE_UNSUPPORTED;
        $DB->update_record('plagiarism_urkund_files', $plagiarism_file);
        return true;
    }
    //check if we need to delay this submission
    $attemptallowed = urkund_check_attempt_timeout($plagiarism_file);
    if (!$attemptallowed) {
        return false;
    }
    //increment attempt number.
    $plagiarism_file->attempt = $plagiarism_file->attempt++;
    $DB->update_record('plagiarism_urkund_files', $plagiarism_file);

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
    //set some initial defaults.
    $submissiondelay = 15;
    $maxsubmissiondelay = 60;
    $maxattempts = 4;
    if ($plagiarism_file->statuscode == 'pending') {
        $submissiondelay = URKUND_SUBMISSION_DELAY; //initial wait time - doubled each time a check is made until the max delay is met.
        $maxsubmissiondelay = URKUND_MAX_SUBMISSION_DELAY; //maximum time to wait between submissions
        $maxattempts = URKUND_MAX_SUBMISSION_ATTEMPTS; //maximum number of times to try and send a submission.
    } else if ($plagiarism_file->statuscode ==URKUND_STATUSCODE_ACCEPTED) {
        $submissiondelay = URKUND_STATUS_DELAY; //initial wait time - this is doubled each time a check is made until the max delay is met.
        $maxsubmissiondelay = URKUND_MAX_STATUS_DELAY; //maximum time to wait between checks
        $maxattempts = URKUND_MAX_STATUS_ATTEMPTS; //maximum number of times to try and send a submission.
    }
    $wait = $submissiondelay;
    //check if we have exceeded the max attempts
    if ($plagiarism_file->attempt > $maxattempts) {
        $plagiarism_file->statuscode = 'timeout';
        $DB->update_record('plagiarism_urkund_files', $plagiarism_file);
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
    $wait = (int)$wait*60;
    $timetocheck = (int)($plagiarism_file->timesubmitted +$wait);
    //calculate when this should be checked next

    if ($timetocheck < $now) {
        return true;
    } else {
        return false;
    }
}

function urkund_send_file_to_urkund($plagiarism_file, $plagiarismsettings, $file) {
    global $DB, $CFG;

    $allowedstatus = array(URKUND_STATUSCODE_ACCEPTED,
                           URKUND_STATUSCODE_NOT_FOUND,
                           URKUND_STATUSCODE_TOO_LARGE,
                           URKUND_STATUSCODE_BAD_REQUEST,
                           URKUND_STATUSCODE_UNSUPPORTED);

    $mimetype = urkund_check_file_type($file->get_filename());
    if (empty($mimetype)) {//sanity check on filetype - this should already have been checked.
        debugging("no mime type for this file found.");
        return false;
    }
    mtrace("sendfile".$plagiarism_file->id);
    $useremail = $DB->get_field('user', 'email', array('id'=>$plagiarism_file->userid));
    //get url of api
    $url = urkund_get_url($plagiarismsettings['urkund_api'], $plagiarism_file);
    if (empty($url)) {
        mtrace('ERROR: no receiver address found for this cm: '.$plagiarism_file->cm. ' Skipping file');
        $plagiarism_file->statuscode = URKUND_STATUSCODE_NORECEIVER;
        $plagiarism_file->errorresponse = get_string('noreceiver', 'plagiarism_urkund');
        $DB->update_record('plagiarism_urkund_files', $plagiarism_file);
        return true;
    }

    $headers = array('x-urkund-submitter: '.$useremail,
                    'Accept-Language: '.$plagiarismsettings['urkund_lang'],
                    'x-urkund-filename: '.base64_encode(utf8_encode($file->get_filename())),
                    'Content-Type: '.$mimetype);

    //use Moodle curl wrapper to send file.
    $c = new curl(array('proxy'=>true));
    $c->setopt(array());
    $c->setopt(array('CURLOPT_RETURNTRANSFER'=> 1,
                     'CURLOPT_HTTPAUTH' => CURLAUTH_BASIC,
                     'CURLOPT_USERPWD'=>$plagiarismsettings['urkund_username'].":".$plagiarismsettings['urkund_password']));

    $c->setHeader($headers);
    $response = $c->post($url, $file->get_content());
    $status = $c->info['http_code'];
    if (!empty($status)) {
        if (in_array($status, $allowedstatus)) {
            if ($status == URKUND_STATUSCODE_ACCEPTED) {
                $plagiarism_file->attempt = 0; //reset attempts for status checks.
            } else {
                $plagiarism_file->errorresponse = $response;
            }
            $plagiarism_file->statuscode = $status;
            $DB->update_record('plagiarism_urkund_files', $plagiarism_file);
            return true;
        }
    }
    //invalid response returned - increment attempt value and return false to allow this to be called again.
    return false;
}

//function to check for the allowed file types, returns the mimetype that URKUND expects.
function urkund_check_file_type($filename, $checkdb=true) {
    $pathinfo = pathinfo($filename);

    if (empty($pathinfo['extension'])) {
        return '';
    }
    $ext = strtolower($pathinfo['extension']);
    $filetypes = array('doc'  => 'application/msword',
                       'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                       'sxw'  => 'application/vnd.sun.xml.writer',
                       'pdf'  => 'application/pdf',
                       'txt'  => 'text/plain',
                       'rtf'  => 'application/rtf',
                       'html' => 'text/html',
                       'htm'  => 'text/html',
                       'wps'  => 'application/vnd.ms-works',
                       'odt'  => 'application/vnd.oasis.opendocument.text');

    if (!empty($filetypes[$ext])) {
        return $filetypes[$ext];
    }
    //check for updated allowed filetypes.
    if ($checkdb) {
        return get_config('plagiarism_urkund','ext_'.$ext);
    } else {
        return false;
    }
}

/**
* used to obtain similarity scores from URKUND for submitted files.
*
* @param object $plagiarismsettings - from a call to plagiarism_get_settings
*
*/
function urkund_get_scores($plagiarismsettings) {
    global $DB;
    $allowedstatus = array(URKUND_STATUSCODE_PROCESSED,
                           URKUND_STATUSCODE_NOT_FOUND,
                           URKUND_STATUSCODE_BAD_REQUEST);
    $successfulstates = array('Analyzed', 'Rejected', 'Error');
    $count = 0;
    mtrace("getting URKUND similarity scores");
    //get all files set that have been submitted
    $files = $DB->get_records('plagiarism_urkund_files',array('statuscode'=>URKUND_STATUSCODE_ACCEPTED));
    if (!empty($files)) {
        foreach($files as $plagiarism_file) {
            //check if we need to delay this submission
            $attemptallowed = urkund_check_attempt_timeout($plagiarism_file);
            if (!$attemptallowed) {
                continue;
            }
            $url = urkund_get_url($plagiarismsettings['urkund_api'], $plagiarism_file);
            if (empty($url)) {
                mtrace('ERROR: no receiver address found for this cm: '.$plagiarism_file->cm);
                $plagiarism_file->statuscode = URKUND_STATUSCODE_NORECEIVER;
                $plagiarism_file->errorresponse = get_string('noreceiver', 'plagiarism_urkund');
                $DB->update_record('plagiarism_urkund_files', $plagiarism_file);
                continue;
            }
            $headers = array('Accept-Language: '.$plagiarismsettings['urkund_lang']);

            //use Moodle curl wrapper to send file.
            $c = new curl(array('proxy'=>true));
            $c->setopt(array());
            $c->setopt(array('CURLOPT_RETURNTRANSFER'=> 1,
                             'CURLOPT_HTTPAUTH' => CURLAUTH_BASIC,
                             'CURLOPT_USERPWD'=>$plagiarismsettings['urkund_username'].":".$plagiarismsettings['urkund_password']));

            $c->setHeader($headers);
            $response = $c->get($url);
            $httpstatus = $c->info['http_code'];
            if (!empty($httpstatus)) {
                if (in_array($httpstatus, $allowedstatus)) {
                    if ($httpstatus == URKUND_STATUSCODE_PROCESSED) {
                        //get similarity score from xml.
                        $xml = new SimpleXMLElement($response);
                        $status = (string)$xml->SubmissionData[0]->Status[0]->State[0];
                        if (!empty($status) && in_array($status, $successfulstates)) {
                            $plagiarism_file->statuscode = $status;
                        }
                        if (!empty($status) && $status=='Analyzed') {
                            $plagiarism_file->reporturl = (string)$xml->SubmissionData[0]->Report[0]->ReportUrl[0];
                            $plagiarism_file->similarityscore = (int)$xml->SubmissionData[0]->Report[0]->Significance[0];
                            $plagiarism_file->optout = (string)$xml->SubmissionData[0]->Document[0]->OptOutInfo[0]->Url[0];
                            //now send e-mail to user
                            $emailstudents = $DB->get_field('plagiarism_urkund_config', 'value', array('cm'=>$plagiarism_file->cm, 'name'=>'urkund_studentemail'));
                            if (!empty($emailstudents)) {
                                $urkund = new plagiarism_plugin_urkund();
                                $urkund->urkund_send_student_email($plagiarism_file);
                            }
                        }
                    } else {
                        $plagiarism_file->statuscode = $httpstatus;
                    }
                }
            }
            $plagiarism_file->attempt = $plagiarism_file->attempt+1;
            $DB->update_record('plagiarism_urkund_files', $plagiarism_file);
        }
    }
}

function urkund_get_url($baseurl, $plagiarism_file) {
    //get url of api
    global $DB;
    $receiver = $DB->get_field('plagiarism_urkund_config', 'value', array('cm'=>$plagiarism_file->cm,'name'=>'urkund_receiver'));
    if (empty($receiver)) {
        return;
    }
    return $baseurl.'/' .$receiver.'/'.md5(get_site_identifier()).
           '_'.$plagiarism_file->cm.'_'.$plagiarism_file->id;
}
//helper function to save multiple db calls.
function urkund_cm_use($cmid) {
    global $DB;
    static $useurkund = array();
    if (!isset($useurkund[$cmid])) {
        $pvalues = $DB->get_records_menu('plagiarism_urkund_config', array('cm'=>$cmid),'','name,value');
        if (!empty($pvalues['use_urkund'])) {
            $useurkund[$cmid] = $pvalues;
        } else {
            $useurkund[$cmid] = false;
        }
    }
    return $useurkund[$cmid];
}

/**
* Function that returns the name of the css class to use for a given similarity score
* @param integer $score - the similarity score
* @return string - string name of css class
*/
function urkund_get_css_rank ($score) {
    $rank = "none";
    if($score > 90) { $rank = "1"; }
    else if($score > 80) { $rank = "2"; }
    else if($score > 70) { $rank = "3"; }
    else if($score > 60) { $rank = "4"; }
    else if($score > 50) { $rank = "5"; }
    else if($score > 40) { $rank = "6"; }
    else if($score > 30) { $rank = "7"; }
    else if($score > 20) { $rank = "8"; }
    else if($score > 10) { $rank = "9"; }
    else if($score >= 0) { $rank = "10"; }

    return "rank$rank";
}

/**
* Function that checks Urkund to see if there are any newly supported filetypes
*
*/
function urkund_update_allowed_filetypes() {
    global $CFG, $DB;
    $configvars = get_config('plagiarism_urkund');
    $now = time();
    $wait = (int)URKUND_FILETYPE_URL_UPDATE*60*60;
    $timetocheck = (int)($configvars->lastupdatedfiletypes+$wait);

    if (empty($configvars->lastupdatedfiletypes) ||
        $timetocheck < $now ) {
        //need to update filetypes
        //get list of existing options.
        $existing = array();
        foreach($configvars as $name =>$value) {
            if (strpos($name,'ext_') !==false) {
                $existing[$name] = $value;
            }
        }

        require_once($CFG->libdir.'/filelib.php');
        $url = URKUND_FILETYPE_URL;
        $c = new curl(array('proxy'=>true));
        $response = $c->get($url);
        $xml = new SimpleXMLElement($response);
        foreach ($xml->format as $format) {
            $type = (string)$format->attributes()->type;
            $suffix = (string)$format->attributes()->suffix;
            unset($existing['ext_'.$suffix]);
            if (!urkund_check_file_type('test.'.$suffix, false)) {
                set_config('ext_'.$suffix, $type, 'plagiarism_urkund');
            }
        }
        //clean up old vars.
        if (!empty($existing)) {
            foreach($existing as $name => $value) {
                $DB->delete_records('config_plugins', array('plugin'=>'plagiarism_urkund', 'name'=> $name));
            }
        }
        set_config('lastupdatedfiletypes', $now, 'plagiarism_urkund');
    }
}

//function to check for invalid event_handlers
function urkund_check_event_handlers() {
    global $DB, $CFG;
    $invalidhandlers = array();
    $eventhandlers = $DB->get_records('events_handlers');
    foreach ($eventhandlers as $handler) {
        $function = unserialize($handler->handlerfunction);

        if (is_callable($function)) { //this function is fine.
            continue;
        } else if (file_exists($CFG->dirroot.$handler->handlerfile)) {
            include_once($CFG->dirroot.$handler->handlerfile);
            if (is_callable($function)) { //this function is fine.
                continue;
            }
        }
        $invalidhandlers[] = $handler; //this function can't be found.
    }
    return $invalidhandlers;
}
