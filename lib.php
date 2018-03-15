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
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

// Get global class.
global $CFG;
require_once($CFG->dirroot.'/plagiarism/lib.php');
require_once($CFG->dirroot . '/lib/filelib.php');

// There is a new URKUND API - The Integration Service - we only currently use this to verify the receiver address.
// If we convert the existing calls to send file/get score we should move this to a config setting.
define('URKUND_INTEGRATION_SERVICE', 'https://secure.urkund.com/api');

define('URKUND_STATUSCODE_PROCESSED', '200');
define('URKUND_STATUSCODE_ACCEPTED', '202');
define('URKUND_STATUSCODE_ACCEPTED_OLD', '202-old'); // File submitted before we changed the way the identifiers were stored.
define('URKUND_STATUSCODE_BAD_REQUEST', '400');
define('URKUND_STATUSCODE_NOT_FOUND', '404');
define('URKUND_STATUSCODE_GONE', '410'); // Receiver is inactive or deleted.
define('URKUND_STATUSCODE_UNSUPPORTED', '415');
define('URKUND_STATUSCODE_TOO_LARGE', '413');
define('URKUND_STATUSCODE_NORECEIVER', '444');
define('URKUND_STATUSCODE_INVALID_RESPONSE', '613'); // Invalid response received from URKUND.
define('URKUND_STATUSCODE_PENDING', 'pending');

// Url to external xml that states URKUNDS allowed file type list.
define('URKUND_FILETYPE_URL', 'https://secure.urkund.com/ws/integration/accepted-formats.xml');

define('URKUND_FILETYPE_URL_UPDATE', '168'); // How often to check for updated file types (defined in hours).

define('PLAGIARISM_URKUND_SHOW_NEVER', 0);
define('PLAGIARISM_URKUND_SHOW_ALWAYS', 1);
define('PLAGIARISM_URKUND_SHOW_CLOSED', 2);

define('PLAGIARISM_URKUND_DRAFTSUBMIT_IMMEDIATE', 0);
define('PLAGIARISM_URKUND_DRAFTSUBMIT_FINAL', 1);

// Used by content type restriction form - inline-text vs file attachments.
define('PLAGIARISM_URKUND_RESTRICTCONTENTNO', 0);
define('PLAGIARISM_URKUND_RESTRICTCONTENTFILES', 1);
define('PLAGIARISM_URKUND_RESTRICTCONTENTTEXT', 2);

// Used by resubmit form element.
define('PLAGIARISM_URKUND_RESUBMITNO', 0);
define('PLAGIARISM_URKUND_RESUBMITDUEDATE', 1);
define('PLAGIARISM_URKUND_RESUBMITCLOSEDATE', 2);

define('PLAGIARISM_URKUND_MAXATTEMPTS', 28);

/**
 * Class plagiarism_plugin_urkund
 *
 * @package   plagiarism_urkund
 * @copyright 2011 Dan Marsden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plagiarism_plugin_urkund extends plagiarism_plugin {
    /**
     * This function should be used to initialise settings and check if plagiarism is enabled.
     *
     * @return mixed - false if not enabled, or returns an array of relevant settings.
     */
    static public function get_settings() {
        static $plagiarismsettings;
        if (!empty($plagiarismsettings) || $plagiarismsettings === false) {
            return $plagiarismsettings;
        }
        $plagiarismsettings = (array)get_config('plagiarism');
        // Check if enabled.
        if (isset($plagiarismsettings['urkund_use']) && $plagiarismsettings['urkund_use']) {
            // Now check to make sure required settings are set!
            if (empty($plagiarismsettings['urkund_api'])) {
                debugging("URKUND API URL not set!");
                return false;
            }
            return $plagiarismsettings;
        } else {
            return false;
        }
    }
    /**
     * We return an array of all the module instance or administration settings, correspondingly.
     *
     * @param bool $adminsettings true if we get the admin settings.
     *
     * @return array
     */
    public static function config_options($adminsettings = false) {
        $options = array('use_urkund', 'urkund_show_student_score', 'urkund_show_student_report',
                     'urkund_draft_submit', 'urkund_resubmit_on_close', 'urkund_receiver', 'urkund_studentemail',
                     'urkund_allowallfile', 'urkund_selectfiletypes', 'urkund_restrictcontent');
        if ($adminsettings) {
            $options[] = 'urkund_advanceditems';
        }
        return $options;
    }

    /**
     * Hook to allow plagiarism specific information to be displayed beside a submission.
     * @param array $linkarray - contains all relevant information for the plugin to generate a link.
     * @return string
     */
    public function get_links($linkarray) {
        global $COURSE, $OUTPUT, $CFG, $DB;
        static $plagiarismvalues = array();

        $cmid = $linkarray['cmid'];
        $userid = $linkarray['userid'];

        if (empty($plagiarismvalues[$cmid])) {
            $plagiarismvalues[$cmid] = $DB->get_records_menu('plagiarism_urkund_config', array('cm' => $cmid), '', 'name,value');
        }
        $plagiarismsettings = $this->get_settings();
        if (!empty($plagiarismsettings['urkund_wordcount'])) {
            $wordcount = $plagiarismsettings['urkund_wordcount'];
        } else {
            // Set a sensible default if we can't find one.
            $wordcount = 50;
        }

        $showcontent = true;
        $showfiles = true;
        if (!empty($plagiarismvalues[$cmid]['urkund_restrictcontent'])) {
            if ($plagiarismvalues[$cmid]['urkund_restrictcontent'] == PLAGIARISM_URKUND_RESTRICTCONTENTFILES) {
                $showcontent = false;
            } else if ($plagiarismvalues[$cmid]['urkund_restrictcontent'] == PLAGIARISM_URKUND_RESTRICTCONTENTTEXT) {
                $showfiles = false;
            }
        }
        if (!empty($linkarray['content']) && $showcontent && str_word_count($linkarray['content']) > $wordcount) {
            $filename = "content-" . $COURSE->id . "-" . $cmid . "-". $userid . ".htm";
            $filepath = $CFG->tempdir."/urkund/" . $filename;
            $file = new stdclass();
            $file->type = "tempurkund";
            $file->filename = $filename;
            $file->timestamp = time();
            $file->identifier = sha1(plagiarism_urkund_format_temp_content($linkarray['content']));
            $file->altidentifier = sha1(plagiarism_urkund_format_temp_content($linkarray['content'], true));
            $file->oldidentifier = sha1($linkarray['content']);
            $file->filepath = $filepath;
            // TODO: Remove this when MDL-57886 is fixed.
            if (!empty($linkarray['assignment'])) {
                // Get raw content to calculate sha1.
                $sql = "SELECT a.id, o.onlinetext
                          FROM {assignsubmission_onlinetext} o
                          JOIN {assign_submission} a ON a.id = o.submission
                         WHERE a.userid = ? AND o.assignment = ?
                         ORDER BY a.id DESC";
                $moodletextsubmissions = $DB->get_records_sql($sql, array($userid, $linkarray['assignment']), 0, 1);
                $moodletextsubmission = end($moodletextsubmissions);
                $file->altidentifier = sha1(plagiarism_urkund_format_temp_content($moodletextsubmission->onlinetext));
            }
        } else if (!empty($linkarray['file']) && $showfiles) {
            $file = new stdclass();
            $file->filename = $linkarray['file']->get_filename();
            $file->timestamp = time();
            $file->identifier = $linkarray['file']->get_contenthash();
            $file->filepath = $linkarray['file']->get_filepath();
        } else {
            return '';
        }
        $results = $this->get_file_results($cmid, $userid, $file);
        if (empty($results)) {
            // Info about this file is not available to this user.
            return '';
        }
        $modulecontext = context_module::instance($cmid);

        $output = '';
        if ($results['statuscode'] == 'pending') {
            // TODO: check to make sure there is a pending event entry for this file - if not add one.
            $output .= '<span class="plagiarismreport">'.
                       '<img src="'.$OUTPUT->image_url('processing', 'plagiarism_urkund') .
                        '" alt="'.get_string('pending', 'plagiarism_urkund').'" '.
                        '" title="'.get_string('pending', 'plagiarism_urkund').'" />'.
                        '</span>';
            return $output;
        }
        if ($results['statuscode'] == 'Analyzed') {
            // Normal situation - URKUND has successfully analyzed the file.
            $rank = urkund_get_css_rank($results['score']);
            $output .= '<span class="plagiarismreport">';
            if (!empty($results['reporturl'])) {
                // User is allowed to view the report.
                // Score is contained in report, so they can see the score too.
                $output .= '<a href="'.$results['reporturl'].'" target="_blank">';
                $output .= get_string('similarity', 'plagiarism_urkund') . ':';
                $output .= '<span class="'.$rank.'">'.$results['score'].'%</span>';
                $output .= '</a>';
            } else if ($results['score'] !== '') {
                // User is allowed to view only the score.
                $output .= get_string('similarity', 'plagiarism_urkund') . ':';
                $output .= '<span class="' . $rank . '">' . $results['score'] . '%</span>';
            }
            if (!empty($results['optoutlink'])) {
                // Display opt-out link.
                $output .= '&nbsp;<span class="plagiarismoptout">' .
                        '<a href="' . $results['optoutlink'] . '" target="_blank">' .
                        get_string('optout', 'plagiarism_urkund') .
                        '</a></span>';
            }
            if (!empty($results['renamed'])) {
                $output .= $results['renamed'];
            }
            $output .= '</span>';
        } else if ($results['statuscode'] == URKUND_STATUSCODE_ACCEPTED) {
            $output .= '<span class="plagiarismreport">'.
                       '<img src="'.$OUTPUT->image_url('processing', 'plagiarism_urkund') .
                        '" alt="'.get_string('processing', 'plagiarism_urkund').'" '.
                        '" title="'.get_string('processing', 'plagiarism_urkund').'" />'.
                        '</span>';
        } else if ($results['statuscode'] == URKUND_STATUSCODE_UNSUPPORTED) {
            $output .= '<span class="plagiarismreport">'.
                       '<img src="'.$OUTPUT->image_url('warning', 'plagiarism_urkund') .
                        '" alt="'.get_string('unsupportedfiletype', 'plagiarism_urkund').'" '.
                        '" title="'.get_string('unsupportedfiletype', 'plagiarism_urkund').'" />'.
                        '</span>';
        } else if ($results['statuscode'] == URKUND_STATUSCODE_TOO_LARGE) {
            $output .= '<span class="plagiarismreport">'.
                       '<img src="'.$OUTPUT->image_url('warning', 'plagiarism_urkund') .
                        '" alt="'.get_string('toolarge', 'plagiarism_urkund').'" '.
                        '" title="'.get_string('toolarge', 'plagiarism_urkund').'" />'.
                        '</span>';
        } else {
            $title = get_string('unknownwarning', 'plagiarism_urkund');
            $reset = '';
            if (has_capability('plagiarism/urkund:resetfile', $modulecontext) &&
                !empty($results['error'])) { // This is a teacher viewing the responses.
                // Strip out some possible known text to tidy it up.
                $erroresponse = format_text($results['error'], FORMAT_PLAIN);
                $erroresponse = str_replace('{&quot;LocalisedMessage&quot;:&quot;', '', $erroresponse);
                $erroresponse = str_replace('&quot;,&quot;Message&quot;:null}', '', $erroresponse);
                $title .= ': ' . $erroresponse;
                $url = new moodle_url('/plagiarism/urkund/reset.php', array('cmid' => $cmid, 'pf' => $results['pid'],
                                                                            'sesskey' => sesskey()));
                $reset = "<a href='$url'>".get_string('reset')."</a>";
            }
            $output .= '<span class="plagiarismreport">'.
                       '<img src="'.$OUTPUT->image_url('warning', 'plagiarism_urkund') .
                        '" alt="'.get_string('unknownwarning', 'plagiarism_urkund').'" '.
                        '" title="'.$title.'" />'.$reset.'</span>';
        }
        return $output;
    }

    /**
     * returns array of plagiarism details about specified file
     *
     * @param int $cmid
     * @param int $userid
     * @param object $file moodle file object
     * @return array - sets of details about specified file, one array of details per plagiarism plugin
     *  - each set contains at least 'analyzed', 'score', 'reporturl'
     */
    public function get_file_results($cmid, $userid, $file) {
        global $DB, $USER, $CFG;
        $plagiarismsettings = $this->get_settings();
        if (empty($plagiarismsettings)) {
            // Urkund is not enabled.
            return false;
        }
        $plagiarismvalues = urkund_cm_use($cmid);
        if (empty($plagiarismvalues)) {
            // Urkund not enabled for this cm.
            return false;
        }

        // Collect detail about the specified coursemodule.
        $filehash = $file->identifier;
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
            // No such cmid.
            return false;
        }

        $modulecontext = context_module::instance($cmid);
        // If the user has permission to see result of all items in this course module.
        $viewscore = $viewreport = has_capability('plagiarism/urkund:viewreport', $modulecontext);

        // Determine if the activity is closed.
        // If report is closed, this can make the report available to more users.
        $assignclosed = false;
        $time = time();
        if (!empty($module->preventlate) && !empty($module->timedue)) {
            $assignclosed = ($module->timeavailable <= $time && $time <= $module->timedue);
        } else if (!empty($module->timeavailable)) {
            $assignclosed = ($module->timeavailable <= $time);
        }

        // Under certain circumstances, users are allowed to see plagiarism info
        // even if they don't have view report capability.
        if ($USER->id == $userid || // If this is a user viewing their own report, check if settings allow it.
            // In workshop and assign if the user can see the submission they might be allowed to see the urkund report.
            // If they are in the forum activity they should not see other users reports.
            (!$viewscore && $moduledetail->name <> 'forum')) { // Teamsubmisson or teacher submitted may be from different user.
            $selfreport = true;
            if (isset($plagiarismvalues['urkund_show_student_report']) &&
                    ($plagiarismvalues['urkund_show_student_report'] == PLAGIARISM_URKUND_SHOW_ALWAYS ||
                     $plagiarismvalues['urkund_show_student_report'] == PLAGIARISM_URKUND_SHOW_CLOSED && $assignclosed)) {
                $viewreport = true;
            }
            if (isset($plagiarismvalues['urkund_show_student_score']) &&
                    ($plagiarismvalues['urkund_show_student_score'] == PLAGIARISM_URKUND_SHOW_ALWAYS) ||
                    ($plagiarismvalues['urkund_show_student_score'] == PLAGIARISM_URKUND_SHOW_CLOSED && $assignclosed)) {
                $viewscore = true;
            }
        } else {
            $selfreport = false;
        }
        // End of rights checking.

        if (!$viewscore && !$viewreport && !$selfreport) {
            // User is not permitted to see any details.
            return false;
        }
        $params = array($cmid, $userid, $userid, $filehash);
        $extrasql = '';
        if (!empty($file->oldidentifier)) {
            $extrasql = ' OR identifier = ?';
            $params[] = $file->oldidentifier;
        }
        if (!empty($file->altidentifier)) {
            $extrasql .= ' OR identifier = ?';
            $params[] = $file->altidentifier;
        }
        $plagiarismfile = $DB->get_record_sql(
                    "SELECT * FROM {plagiarism_urkund_files}
                    WHERE cm = ? AND (userid = ? OR relateduserid = ?) AND " .
                    "(identifier = ? ".$extrasql.")", $params);
        if (empty($plagiarismfile)) {
            // No record of that submitted file.
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

        // Now check for differing filename and display info related to it.
        $previouslysubmitted = '';
        if ($file->filename !== $plagiarismfile->filename) {
            $previouslysubmitted = '('.get_string('previouslysubmitted', 'plagiarism_urkund').': '.$plagiarismfile->filename.')';
        }

        $results['statuscode'] = $plagiarismfile->statuscode;
        $results['pid'] = $plagiarismfile->id;
        $results['error'] = $plagiarismfile->errorresponse;
        if ($plagiarismfile->statuscode == 'Analyzed') {
            $results['analyzed'] = 1;
            // File has been successfully analyzed - return all appropriate details.
            if ($viewscore || $viewreport) {
                // If user can see the report, they can see the score on the report
                // so make it directly available.
                $results['score'] = $plagiarismfile->similarityscore;
            }
            if ($viewreport) {
                $results['reporturl'] = $plagiarismfile->reporturl;
            }
            if (!empty($plagiarismsettings['urkund_optout']) && !empty($plagiarismfile->optout) && $selfreport) {
                $results['optoutlink'] = $plagiarismfile->optout;
            }
            $results['renamed'] = $previouslysubmitted;
        }
        return $results;
    }
    /**
     * Hook to save plagiarism specific settings on a module settings page.
     * @param object $data - data from an mform submission.
     */
    public function save_form_elements($data) {
        global $DB;
        if (!$this->get_settings()) {
            return;
        }
        if (isset($data->use_urkund)) {
            if (empty($data->submissiondrafts)) {
                // Make sure draft_submit is not set if submissiondrafts not used.
                $data->urkund_draft_submit = 0;
            }
            // Array of possible plagiarism config options.
            $plagiarismelements = $this->config_options();
            // First get existing values.
            if (empty($data->coursemodule)) {
                debugging("URKUND settings failure - no coursemodule set in form data, URKUND could not be enabled.");
                return;
            }
            $existingelements = $DB->get_records_menu('plagiarism_urkund_config', array('cm' => $data->coursemodule),
                                                      '', 'name, id');
            foreach ($plagiarismelements as $element) {
                // Don't allow changes to receiver address if urkund is disabled.
                if (empty($data->use_urkund) && $element == 'urkund_receiver') {
                    continue;
                }
                $newelement = new stdClass();
                $newelement->cm = $data->coursemodule;
                $newelement->name = $element;
                if (isset($data->$element) && is_array($data->$element)) {
                    $newelement->value = implode(',', $data->$element);
                } else {
                    $newelement->value = (isset($data->$element) ? $data->$element : 0);
                }
                if (isset($existingelements[$element])) {
                    $newelement->id = $existingelements[$element];
                    $DB->update_record('plagiarism_urkund_config', $newelement);
                } else {
                    $DB->insert_record('plagiarism_urkund_config', $newelement);
                }

            }
            // Don't save user preference if this assignment doesn't use Urkund.
            if (!empty($data->urkund_receiver) && !empty($data->use_urkund)) {
                set_user_preference('urkund_receiver', trim($data->urkund_receiver));
            }
        }
    }

    /**
     * Hook to add plagiarism specific settings to a module settings page
     * @param stdClass $mform  - Moodle form
     * @param stdClass $context - current context
     * @param string $modulename - name of module.
     */
    public function get_form_elements_module($mform, $context, $modulename = "") {
        global $DB, $PAGE, $CFG;
        $plagiarismsettings = $this->get_settings();
        if (!$plagiarismsettings) {
            return;
        }
        $cmid = optional_param('update', 0, PARAM_INT); // Get cm as $this->_cm is not available here.
        if (!empty($modulename)) {
            $modname = 'urkund_enable_' . $modulename;
            if (empty($plagiarismsettings[$modname])) {
                return;             // Return if urkund is not enabled for the module.
            }
        }
        if (!empty($cmid)) {
            $plagiarismvalues = $DB->get_records_menu('plagiarism_urkund_config', array('cm' => $cmid), '', 'name, value');
        }
        // Get Defaults - cmid(0) is the default list.
        $plagiarismdefaults = $DB->get_records_menu('plagiarism_urkund_config', array('cm' => 0), '', 'name, value');
        $plagiarismelements = $this->config_options();
        if (has_capability('plagiarism/urkund:enable', $context)) {
            urkund_get_form_elements($mform);
            if ($mform->elementExists('urkund_draft_submit') && $mform->elementExists('submissiondrafts')) {
                $mform->hideif('urkund_draft_submit', 'submissiondrafts', 'eq', 0);
            }
            // Disable all plagiarism elements if use_plagiarism eg 0.
            foreach ($plagiarismelements as $element) {
                if ($element <> 'use_urkund') { // Ignore this var.
                    $mform->hideif($element, 'use_urkund', 'eq', 0);
                }
            }
            // Check if files have been submitted and we need to disable the receiver address.
            if ($DB->record_exists('plagiarism_urkund_files', array('cm' => $cmid, 'statuscode' => 'pending'))) {
                $mform->hideif('urkund_receiver', 'use_urkund');
            }
            $mform->hideif('urkund_selectfiletypes', 'urkund_allowallfile', 'eq', 1);
        } else { // Add plagiarism settings as hidden vars.
            foreach ($plagiarismelements as $element) {
                $mform->addElement('hidden', $element);
            }
        }
        $mform->setType('use_urkund', PARAM_INT);
        $mform->setType('urkund_show_student_score', PARAM_INT);
        $mform->setType('urkund_show_student_report', PARAM_INT);
        $mform->setType('urkund_draft_submit', PARAM_INT);
        $mform->setType('urkund_receiver', PARAM_TEXT);
        $mform->setType('urkund_studentemail', PARAM_INT);

        // Now set defaults.
        foreach ($plagiarismelements as $element) {
            $defaultelement = $element.'_'.str_replace('mod_', '', $modulename);
            if (isset($plagiarismvalues[$element])) {
                $mform->setDefault($element, $plagiarismvalues[$element]);
            } else if ($element == 'urkund_receiver') {
                $def = get_user_preferences($element);
                if (!empty($def)) {
                    $mform->setDefault($element, $def);
                } else if (isset($plagiarismdefaults[$defaultelement])) {
                    $mform->setDefault($element, $plagiarismdefaults[$defaultelement]);
                }
            } else if (isset($plagiarismdefaults[$defaultelement])) {
                $mform->setDefault($element, $plagiarismdefaults[$defaultelement]);
            }
        }
        $mform->registerRule('urkundvalidatereceiver', null, 'plagiarism_urkund_validatereceiver',
                             $CFG->dirroot.'/plagiarism/urkund/classes/validatereceiver.php');
        $mform->addRule('urkund_receiver', get_string('receivernotvalid', 'plagiarism_urkund'), 'urkundvalidatereceiver');

        // Now add JS to validate receiver indicator using Ajax.
        if (has_capability('plagiarism/urkund:enable', $context)) {
            $jsmodule = array(
                'name' => 'plagiarism_urkund',
                'fullpath' => '/plagiarism/urkund/checkreceiver.js',
                'requires' => array('json'),
            );
            $PAGE->requires->js_init_call('M.plagiarism_urkund.init', array($context->instanceid), true, $jsmodule);
        }

        // Show advanced elements only if allowed.
        $defaultelementadvanced = 'urkund_advanceditems_'.str_replace('mod_', '', $modulename);
        if (!empty($plagiarismdefaults[$defaultelementadvanced])) {
            $advancedsettings = explode(',', $plagiarismdefaults[$defaultelementadvanced]);
            if (has_capability('plagiarism/urkund:advancedsettings', $context)) {
                foreach ($advancedsettings as $name) {
                    if ($mform->elementExists($name)) {
                        $mform->setAdvanced($name, true);
                    }
                }
            } else {
                // Otherwise, put them as hidden elements.
                foreach ($advancedsettings as $name) {
                    if ($mform->elementExists($name)) {
                        $element = $mform->removeElement($name);
                        $mform->addElement('hidden', $name, $element->getValue());
                    }
                }
            }
        }

        // Now handle content restriction settings.
        if ($modulename == 'mod_assign' && $mform->elementExists("submissionplugins")) { // This should be mod_assign
            // I can't see a way to check if a particular checkbox exists
            // elementExists on the checkbox name doesn't work.
            $mform->hideif('urkund_restrictcontent', 'assignsubmission_onlinetext_enabled');
        } else if ($modulename != 'mod_forum') {
            // Forum doesn't need any changes but all other modules should disable this.
            $mform->setDefault('urkund_restrictcontent', 0);
            $mform->hardFreeze('urkund_restrictcontent');
        }
    }

    /**
     * Hook to allow a disclosure to be printed notifying users what will happen with their submission.
     * @param int $cmid - course module id
     * @return string
     */
    public function print_disclosure($cmid) {
        global $OUTPUT;

        $outputhtml = '';

        $urkunduse = urkund_cm_use($cmid);
        $plagiarismsettings = $this->get_settings();
        if (!empty($plagiarismsettings['urkund_student_disclosure']) &&
            !empty($urkunduse)) {
                $outputhtml .= $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
                $formatoptions = new stdClass;
                $formatoptions->noclean = true;
                $outputhtml .= format_text($plagiarismsettings['urkund_student_disclosure'], FORMAT_MOODLE, $formatoptions);
                $outputhtml .= $OUTPUT->box_end();
        }
        return $outputhtml;
    }

    /**
     * Generic handler function for all events - queues files for sending.
     * @param stdClass $eventdata
     * @return boolean
     */
    public function event_handler($eventdata) {
        global $DB, $CFG;

        $plagiarismsettings = $this->get_settings();
        if (!$plagiarismsettings) {
            return true;
        }
        $cmid = $eventdata['contextinstanceid'];
        $plagiarismvalues = $DB->get_records_menu('plagiarism_urkund_config', array('cm' => $cmid), '', 'name, value');
        if (empty($plagiarismvalues['use_urkund'])) {
            // Urkund not in use for this cm - return.
            return true;
        }

        // Check if the module associated with this event still exists.
        if (!$DB->record_exists('course_modules', array('id' => $cmid))) {
            return true;
        }

        $userid = $eventdata['userid'];
        $relateduserid = null;

        // Check if this is a submission on-behalf.
        if (!empty($eventdata['relateduserid'])) {
            $relateduserid = $eventdata['relateduserid'];
        }

        // Check to see if restrictcontent is in use.
        $showcontent = true;
        $showfiles = true;
        if (!empty($plagiarismvalues[$cmid]['urkund_restrictcontent'])) {
            if ($plagiarismvalues['urkund_restrictcontent'] == PLAGIARISM_URKUND_RESTRICTCONTENTFILES) {
                $showcontent = false;
            } else if ($plagiarismvalues['urkund_restrictcontent'] == PLAGIARISM_URKUND_RESTRICTCONTENTTEXT) {
                $showfiles = false;
            }
        }

        if (!empty($plagiarismsettings['urkund_wordcount'])) {
            $wordcount = $plagiarismsettings['urkund_wordcount'];
        } else {
            // Set a sensible default if we can't find one.
            $wordcount = 50;
        }

        if ($eventdata['eventtype'] == 'assignsubmission_submitted' && empty($eventdata['other']['submission_editable'])) {
            // Assignment-specific functionality:
            // This is a 'finalize' event. No files from this event itself,
            // but need to check if files from previous events need to be submitted for processing.
            $result = true;
            if (isset($plagiarismvalues['urkund_draft_submit']) &&
                $plagiarismvalues['urkund_draft_submit'] == PLAGIARISM_URKUND_DRAFTSUBMIT_FINAL) {
                // Any files attached to previous events were not submitted.
                // These files are now finalized, and should be submitted for processing.
                require_once("$CFG->dirroot/mod/assign/locallib.php");
                require_once("$CFG->dirroot/mod/assign/submission/file/locallib.php");

                $modulecontext = context_module::instance($cmid);

                if ($showfiles) { // If we should be handling files.
                    $fs = get_file_storage();
                    if ($files = $fs->get_area_files($modulecontext->id, 'assignsubmission_file',
                        ASSIGNSUBMISSION_FILE_FILEAREA, $eventdata['objectid'], "id", false)) {
                        foreach ($files as $file) {
                            urkund_queue_file($cmid, $userid, $file, $relateduserid);
                        }
                    }
                }

                if ($showcontent) { // If we should be handling in-line text.
                    $submission = $DB->get_record('assignsubmission_onlinetext', array('submission' => $eventdata['objectid']));
                    if (!empty($submission) && str_word_count($submission->onlinetext) > $wordcount) {
                        $content = trim(format_text($submission->onlinetext, $submission->onlineformat,
                            array('context' => $modulecontext)));
                        $file = urkund_create_temp_file($cmid, $eventdata['courseid'], $userid, $content);
                        urkund_queue_file($cmid, $userid, $file, $relateduserid);
                    }
                }
            }
            return $result;
        }

        if (isset($plagiarismvalues['urkund_draft_submit']) &&
            $plagiarismvalues['urkund_draft_submit'] == PLAGIARISM_URKUND_DRAFTSUBMIT_FINAL) {
            // Assignment-specific functionality:
            // Files should only be sent for checking once "finalized".
            return true;
        }

        // Text is attached.
        $result = true;
        if (!empty($eventdata['other']['content']) && $showcontent && str_word_count($eventdata['other']['content']) > $wordcount) {

            $file = urkund_create_temp_file($cmid, $eventdata['courseid'], $userid, $eventdata['other']['content']);
            urkund_queue_file($cmid, $userid, $file, $relateduserid);
        }

        // Normal situation: 1 or more assessable files attached to event, ready to be checked.
        if (!empty($eventdata['other']['pathnamehashes']) && $showfiles) {
            foreach ($eventdata['other']['pathnamehashes'] as $hash) {
                $fs = get_file_storage();
                $efile = $fs->get_file_by_hash($hash);

                if (empty($efile)) {
                    mtrace("nofilefound!");
                    continue;
                } else if ($efile->get_filename() === '.') {
                    // This 'file' is actually a directory - nothing to submit.
                    continue;
                }

                urkund_queue_file($cmid, $userid, $efile, $relateduserid);
            }
        }
        return $result;
    }

    /**
     * Send student e-mail when score available.
     *
     * @param stdClass $plagiarismfile - file record.
     */
    public function urkund_send_student_email($plagiarismfile) {
        global $DB, $CFG;
        if (empty($plagiarismfile->userid)) { // Sanity check.
            return false;
        }
        if (!empty($plagiarismfile->relateduserid)) {
            $user = $DB->get_record('user', array('id' => $plagiarismfile->relateduserid));
        } else {
            $user = $DB->get_record('user', array('id' => $plagiarismfile->userid));
        }
        $site = get_site();
        $a = new stdClass();
        $cm = get_coursemodule_from_id('', $plagiarismfile->cm);
        $a->modulename = format_string($cm->name);
        $a->modulelink = $CFG->wwwroot.'/mod/'.$cm->modname.'/view.php?id='.$cm->id;
        $a->coursename = format_string($DB->get_field('course', 'fullname', array('id' => $cm->course)));
        $plagiarismsettings = $this->get_settings();
        if (!empty($plagiarismsettings['urkund_optout'])) {
            $a->optoutlink = $plagiarismfile->optout;
            $emailcontent = get_string('studentemailcontent', 'plagiarism_urkund', $a);
        } else {
            $emailcontent = get_string('studentemailcontentnoopt', 'plagiarism_urkund', $a);
        }

        $emailsubject = get_string('studentemailsubject', 'plagiarism_urkund');

        email_to_user($user, $site->shortname, $emailsubject, $emailcontent);
    }

    /**
     * Validates receiver address.
     *
     * @param string $receiver
     */
    public function validate_receiver($receiver) {
        $plagiarismsettings = $this->get_settings();
        $url = URKUND_INTEGRATION_SERVICE .'/receivers'.'/'. trim($receiver);;

        $headers = array('Accept-Language: '.$plagiarismsettings['urkund_lang']);

        $allowedstatus = array(URKUND_STATUSCODE_PROCESSED,
                               URKUND_STATUSCODE_NOT_FOUND,
                               URKUND_STATUSCODE_BAD_REQUEST,
                               URKUND_STATUSCODE_GONE);

        // Use Moodle curl wrapper.
        $c = new curl(array('proxy' => true));
        $c->setopt(array());
        $c->setopt(array('CURLOPT_RETURNTRANSFER' => 1,
                         'CURLOPT_TIMEOUT' => 60, // Set to 60seconds just in case.
                         'CURLOPT_HTTPAUTH' => CURLAUTH_BASIC,
                         'CURLOPT_USERPWD' => $plagiarismsettings['urkund_username'].":".$plagiarismsettings['urkund_password']));

        $c->setHeader($headers);
        $response = $c->get($url);
        $httpstatus = $c->info['http_code'];
        if (!empty($httpstatus)) {
            if (in_array($httpstatus, $allowedstatus)) {
                if ($httpstatus == URKUND_STATUSCODE_PROCESSED) {
                    // Valid address found, return true.
                    return true;
                } else {
                    return $httpstatus;
                }
            }
        }
        return false;
    }
    /**
     * hook to allow status of submitted files to be updated - called on grading/report pages.
     *
     * @param object $course - full Course object
     * @param object $cm - full cm object
     */
    public function update_status($course, $cm) {
        global $OUTPUT, $DB;
        // Check if this is an assignment.
        if (!empty($cm->modname) && $cm->modname == 'assign') {
            // Check to see if the user can reset files.
            $modulecontext = context_module::instance($cm->id);
            if (!has_capability('plagiarism/urkund:resetfile', $modulecontext)) {
                return;
            }
            // Check to see if urkund enabled.
            $useurkund = $DB->get_field('plagiarism_urkund_config', 'value',
                array('cm' => $cm->id, 'name' => 'use_urkund'));
            if (!empty($useurkund)) {
                $url = new moodle_url('/plagiarism/urkund/reset.php', array('cmid' => $cm->id, 'resetall' => 1));
                return '<div class="urkundresubmit">'.
                    $OUTPUT->single_button($url, get_string('resubmittourkund', 'plagiarism_urkund'))
                    ."</div>";
            }
        }
    }
}

/**
 * Create temp file for text content
 *
 * @param int $cmid - coursemodule id.
 * @param int $courseid - course id.
 * @param int $userid - user id.
 * @param string $filecontent - raw content of file.
 *
 * @return string
 */
function urkund_create_temp_file($cmid, $courseid, $userid, $filecontent) {
    global $CFG;
    if (!check_dir_exists($CFG->tempdir."/urkund", true, true)) {
        mkdir($CFG->tempdir."/urkund", 0700);
    }
    $filename = "content-" . $courseid . "-" . $cmid . "-" . $userid ."-". random_string(8).".htm";
    $filepath = $CFG->tempdir."/urkund/" . $filename;
    $fd = fopen($filepath, 'wb');   // Create if not exist, write binary.

    // Write html and body tags as it seems that Urkund doesn't works well without them.
    $content = plagiarism_urkund_format_temp_content($filecontent);

    fwrite($fd, $content);
    fclose($fd);

    return $filepath;
}

/**
 * Helper function used to add extra html around file contents.
 *
 * @param string $content - raw content of file.
 * @param boolean $strippretag - should we strip tags first.
 *
 * @return string
 */
function plagiarism_urkund_format_temp_content($content, $strippretag = false) {
    // See MDL-57886.
    if ($strippretag) {
        $content = substr($content, 25, strlen($content) - 31);
    }
    return '<html>' .
           '<head>' .
           '<meta charset="UTF-8">' .
           '</head>' .
           '<body>' .
           $content .
           '</body></html>';

}

/**
 * Adds the list of plagiarism settings to a form.
 *
 * @param object $mform - Moodle form object.
 */
function urkund_get_form_elements($mform) {
    $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
    $tiioptions = array(0 => get_string("never"), 1 => get_string("always"),
                        2 => get_string("showwhenclosed", "plagiarism_urkund"));
    $urkunddraftoptions = array(
            PLAGIARISM_URKUND_DRAFTSUBMIT_IMMEDIATE => get_string("submitondraft", "plagiarism_urkund"),
            PLAGIARISM_URKUND_DRAFTSUBMIT_FINAL => get_string("submitonfinal", "plagiarism_urkund")
            );

    $mform->addElement('header', 'plagiarismdesc', get_string('urkund', 'plagiarism_urkund'));
    $mform->addElement('select', 'use_urkund', get_string("useurkund", "plagiarism_urkund"), $ynoptions);
    $mform->setType('urkund_receiver', PARAM_TEXT);
    $mform->addElement('select', 'urkund_show_student_score',
                       get_string("urkund_show_student_score", "plagiarism_urkund"), $tiioptions);
    $mform->addHelpButton('urkund_show_student_score', 'urkund_show_student_score', 'plagiarism_urkund');
    $mform->addElement('select', 'urkund_show_student_report',
                       get_string("urkund_show_student_report", "plagiarism_urkund"), $tiioptions);
    $mform->addHelpButton('urkund_show_student_report', 'urkund_show_student_report', 'plagiarism_urkund');
    if ($mform->elementExists('submissiondrafts')) {
        $mform->addElement('select', 'urkund_draft_submit',
                           get_string("urkund_draft_submit", "plagiarism_urkund"), $urkunddraftoptions);
    }
    if ($mform->elementExists('submissiondrafts')) { // Just show this on assignment submission page for now.
        $resubmitoptions = array(PLAGIARISM_URKUND_RESUBMITNO => get_string('no'),
            PLAGIARISM_URKUND_RESUBMITDUEDATE => get_string('resubmitdue', 'plagiarism_urkund'),
            PLAGIARISM_URKUND_RESUBMITCLOSEDATE => get_string('resubmitclose', 'plagiarism_urkund'));
        $mform->addElement('select', 'urkund_resubmit_on_close', get_string("urkund_resubmit_on_close", "plagiarism_urkund"), $resubmitoptions);
        $mform->addHelpButton('urkund_resubmit_on_close', 'urkund_resubmit_on_close', 'plagiarism_urkund');
        $mform->setType('urkund_resubmit_on_close', PARAM_INT);
    }

    $contentoptions = array(PLAGIARISM_URKUND_RESTRICTCONTENTNO => get_string('restrictcontentno', 'plagiarism_urkund'),
                            PLAGIARISM_URKUND_RESTRICTCONTENTFILES => get_string('restrictcontentfiles', 'plagiarism_urkund'),
                            PLAGIARISM_URKUND_RESTRICTCONTENTTEXT => get_string('restrictcontenttext', 'plagiarism_urkund'));
    $mform->addElement('select', 'urkund_restrictcontent', get_string('restrictcontent', 'plagiarism_urkund'), $contentoptions);
    $mform->addHelpButton('urkund_restrictcontent', 'restrictcontent', 'plagiarism_urkund');
    $mform->setType('urkund_restrictcontent', PARAM_INT);
    $mform->addElement('text', 'urkund_receiver', get_string("urkund_receiver", "plagiarism_urkund"), array('size' => 40));
    $mform->addHelpButton('urkund_receiver', 'urkund_receiver', 'plagiarism_urkund');
    $mform->setType('urkund_receiver', PARAM_EMAIL);
    $mform->addElement('select', 'urkund_studentemail', get_string("urkund_studentemail", "plagiarism_urkund"), $ynoptions);
    $mform->addHelpButton('urkund_studentemail', 'urkund_studentemail', 'plagiarism_urkund');
    $mform->setType('urkund_studentemail', PARAM_INT);

    $filetypes = urkund_default_allowed_file_types(true);

    $supportedfiles = array();
    foreach ($filetypes as $ext => $mime) {
        $supportedfiles[$ext] = $ext;
    }
    $mform->addElement('select', 'urkund_allowallfile', get_string('allowallsupportedfiles', 'plagiarism_urkund'), $ynoptions);
    $mform->addHelpButton('urkund_allowallfile', 'allowallsupportedfiles', 'plagiarism_urkund');
    $mform->setType('urkund_allowallfile', PARAM_INT);
    $mform->addElement('select', 'urkund_selectfiletypes', get_string('restrictfiles', 'plagiarism_urkund'),
                       $supportedfiles, array('multiple' => true));
    $mform->setType('urkund_selectfiletypes', PARAM_TAGLIST);
}

/**
 * Updates a urkund_files record.
 *
 * @param int $cmid - course module id
 * @param int $userid - user id
 * @param stored_file|string $file - identifier for this plagiarism record - hash of file, id of quiz question etc
 * @param int $relateduserid - relateduserid if passed.
 * @return int - id of urkund_files record
 */
function urkund_get_plagiarism_file($cmid, $userid, $file, $relateduserid = null) {
    global $DB;

    if (is_string($file)) { // This is a local file path.
        $filehash = $file;
        $filename = basename($file);
    } else {
        $filehash = (!empty($file->identifier)) ? $file->identifier : $file->get_contenthash();
        $filename = (!empty($file->filename)) ? $file->filename : $file->get_filename();
    }

    // Now update or insert record into urkund_files.
    $plagiarismfile = $DB->get_record_sql(
                                "SELECT * FROM {plagiarism_urkund_files}
                                 WHERE cm = ? AND userid = ? AND " .
                                "identifier = ?",
                                array($cmid, $userid, $filehash));
    if (!empty($plagiarismfile)) {
            return $plagiarismfile;
    } else {
        $plagiarismfile = new stdClass();
        $plagiarismfile->cm = $cmid;
        $plagiarismfile->userid = $userid;
        $plagiarismfile->relateduserid = $relateduserid;
        $plagiarismfile->identifier = $filehash;
        $plagiarismfile->filename = $filename;
        $plagiarismfile->statuscode = 'pending';
        $plagiarismfile->attempt = 0;
        $plagiarismfile->timesubmitted = time();
        $plagiarismfile->revision = 0;
        if (!$pid = $DB->insert_record('plagiarism_urkund_files', $plagiarismfile)) {
            debugging("insert into urkund_files failed");
        }
        $plagiarismfile->id = $pid;
        return $plagiarismfile;
    }
}

/**
 * Queue file for sending to URKUND
 *
 * @param int $cmid - course module id
 * @param int $userid - user id
 * @param varied $file string if path to temp file or full Moodle file object.
 * @param int $relateduserid - related user if if passed. (use when sending to URKUND.
 * @return boolean
 */
function urkund_queue_file($cmid, $userid, $file, $relateduserid = null) {
    global $DB;
    $plagiarismfile = urkund_get_plagiarism_file($cmid, $userid, $file, $relateduserid);
    $plagiarismvalues = $DB->get_records_menu('plagiarism_urkund_config', array('cm' => $cmid), '', 'name, value');

    // Check if $plagiarismfile actually needs to be submitted.
    if ($plagiarismfile->statuscode <> 'pending') {
        return '';
    }
    if (is_string($file)) {
        $filename = basename($file);
    } else {
        $filename = (!empty($file->filename)) ? $file->filename : $file->get_filename();
    }

    if ($plagiarismfile->filename !== $filename) {
        // This is a file that was previously submitted and not sent to urkund but the filename has changed so fix it.
        $plagiarismfile->filename = $filename;
        $DB->update_record('plagiarism_urkund_files', $plagiarismfile);
    }
    // Check to see if this is a valid file.
    $mimetype = urkund_check_file_type($filename);
    if (empty($mimetype)) {
        $plagiarismfile->statuscode = URKUND_STATUSCODE_UNSUPPORTED;
        $DB->update_record('plagiarism_urkund_files', $plagiarismfile);
        return '';
    }

    // Check to see if configured to only send certain file-types and if this file matches.
    if (isset($plagiarismvalues['urkund_allowallfile']) && empty($plagiarismvalues['urkund_allowallfile'])) {
        $allowedtypes = explode(',', $plagiarismvalues['urkund_selectfiletypes']);
        $allowedtypes[] = 'htm'; // Always allow htm files as they come from online submissions.

        $pathinfo = pathinfo($filename);
        if (!empty($pathinfo['extension'])) {
            $ext = strtolower($pathinfo['extension']);
            if (!in_array($ext, $allowedtypes)) {
                // This file is not allowed, delete it from the table.
                $DB->delete_records('plagiarism_urkund_files', array('id' => $plagiarismfile->id));
                debugging("File submitted to cm:".$cmid. " with an extension ". $ext.
                       " This assignment is configured to ignore this filetype, ".
                       "only files of type:".$plagiarismvalues['urkund_selectfiletypes']. " are accepted");
                return '';
            }
        } else {
            // No path found - this shouldn't happen but ignore this file.
            debugging("Could not obtain the extension for a file submitted to cm:".$cmid. " with the filename ". $filename.
                   "only files of type:".$plagiarismvalues['urkund_selectfiletypes']. " are accepted");
            $DB->delete_records('plagiarism_urkund_files', array('id' => $plagiarismfile->id));
            return '';
        }
    }

    return $plagiarismfile;
}

/**
 * Check timesubmitted and attempt to see if we need to delay check, checks max attempts to see if it has exceeded.
 *
 * @param stdClass $plagiarismfile - plagiarism file record.
 * @return boolean
 */
function urkund_check_attempt_timeout($plagiarismfile) {
    global $DB;

    // Maximum number of times to try and obtain the status of a submission.
    // Make sure the max attempt value in $statusdelay is higher than this.
    $maxstatusattempts = PLAGIARISM_URKUND_MAXATTEMPTS;

    // Time to wait between checks array(number of attempts-1, time delay in minutes)..
    $statusdelay = array(2 => 5, // Up to attempt 3 check every 5 minutes.
                         3 => 15, // Up to attempt 4 check every 15 minutes.
                         6 => 30,
                        11 => 120,
                        20 => 240,
                        100 => 480);

    // The first time a file is submitted we don't need to wait at all.
    if (empty($plagiarismfile->attempt) && $plagiarismfile->statuscode == 'pending') {
        return true;
    }

    // Check if we have exceeded the max attempts.
    if ($plagiarismfile->attempt > $maxstatusattempts) {
        $plagiarismfile->statuscode = 'timeout';
        $DB->update_record('plagiarism_urkund_files', $plagiarismfile);
        return true; // Return true to cancel the event.
    }

    $now = time();

    // Now calculate wait time.
    $i = 0;
    $wait = 0;
    while ($i < $plagiarismfile->attempt) {
        // Find what multiple we need to use for this attempt.
        foreach ($statusdelay as $att => $delay) {
            if ($att >= $i) {
                $wait = $wait + $delay;
                break;
            }
        }
        $i++;
    }
    $wait = (int)$wait * 60;
    $timetocheck = (int)($plagiarismfile->timesubmitted + $wait);
    // Calculate when this should be checked next.

    if ($timetocheck < $now) {
        return true;
    } else {
        return false;
    }
}

/**
 * Send file to urkund
 *
 * @param stdClass $plagiarismfile - plagiarism file record.
 * @param stdClass $plagiarismsettings - settings.
 * @param stored_file $file
 *
 * @return boolean
 */
function urkund_send_file_to_urkund($plagiarismfile, $plagiarismsettings, $file) {
    global $DB;

    $allowedstatus = array(URKUND_STATUSCODE_ACCEPTED,
                           URKUND_STATUSCODE_NOT_FOUND,
                           URKUND_STATUSCODE_TOO_LARGE,
                           URKUND_STATUSCODE_BAD_REQUEST,
                           URKUND_STATUSCODE_UNSUPPORTED);

    $filename = (!empty($file->filename)) ? $file->filename : $file->get_filename();
    $mimetype = urkund_check_file_type($filename);
    if (empty($mimetype)) {// Sanity check on filetype - this should already have been checked.
        $plagiarismfile->statuscode = URKUND_STATUSCODE_UNSUPPORTED;
        $DB->update_record('plagiarism_urkund_files', $plagiarismfile);
        return true;
    }
    mtrace("URKUND fileid:".$plagiarismfile->id. ' sending to URKUND');
    if (!empty($plagiarismfile->relateduserid)) {
        $useremail = $DB->get_field('user', 'email', array('id' => $plagiarismfile->relateduserid));
    } else {
        $useremail = $DB->get_field('user', 'email', array('id' => $plagiarismfile->userid));
    }

    // Get url of api.
    $url = urkund_get_url($plagiarismsettings['urkund_api'], $plagiarismfile);
    if (empty($url)) {
        mtrace('ERROR: no receiver address found for this cm: '.$plagiarismfile->cm. ' Skipping file');
        $plagiarismfile->statuscode = URKUND_STATUSCODE_NORECEIVER;
        $plagiarismfile->errorresponse = get_string('noreceiver', 'plagiarism_urkund');
        $DB->update_record('plagiarism_urkund_files', $plagiarismfile);
        return true;
    }
    if (!empty(get_config('plagiarism', 'urkund_hidefilename'))) {
        $pathinfo = pathinfo($filename);
        $filenametopass = base64_encode("submission.".$pathinfo['extension']);
    } else {
        $filenametopass = base64_encode($filename);
    }

    $headers = array('x-urkund-submitter: '.$useremail,
                    'Accept-Language: '.$plagiarismsettings['urkund_lang'],
                    'x-urkund-filename: '.$filenametopass,
                    'Content-Type: '.$mimetype);

    // Use Moodle curl wrapper to send file.
    $c = new curl(array('proxy' => true));
    $c->setopt(array());
    $c->setopt(array('CURLOPT_RETURNTRANSFER' => 1,
                     'CURLOPT_TIMEOUT' => 1800, // Set to 30min just in case.
                     'CURLOPT_HTTPAUTH' => CURLAUTH_BASIC,
                     'CURLOPT_USERPWD' => $plagiarismsettings['urkund_username'].":".$plagiarismsettings['urkund_password']));

    $c->setHeader($headers);
    $filecontents = (!empty($file->filepath)) ? file_get_contents($file->filepath) : $file->get_content();
    $response = $c->post($url, $filecontents);
    $status = $c->info['http_code'];
    if (!empty($status)) {
        if (in_array($status, $allowedstatus)) {
            $plagiarismfile->statuscode = $status;
            if ($status == URKUND_STATUSCODE_ACCEPTED) {
                $plagiarismfile->attempt = 0; // Reset attempts for status checks.
                plagiarism_urkund_fix_temp_hash($plagiarismfile); // Fix hash if temp file used and delete temp file.
            } else {
                $plagiarismfile->errorresponse = $response;
            }

            mtrace("URKUND fileid:".$plagiarismfile->id. ' returned status: '.$status);
            $DB->update_record('plagiarism_urkund_files', $plagiarismfile);
            return true;
        }
    }
    mtrace("URKUND fileid:".$plagiarismfile->id. ' returned error: '.$response);
    // Invalid response returned - increment attempt value and return false to allow this to be called again.
    $plagiarismfile->statuscode = URKUND_STATUSCODE_INVALID_RESPONSE;
    $plagiarismfile->errorresponse = $response;
    $plagiarismfile->attempt = $plagiarismfile->attempt + 1;
    $DB->update_record('plagiarism_urkund_files', $plagiarismfile);
    return false;
}

/**
 * Check for the allowed file types, returns the mimetype that URKUND expects.
 *
 * @param string $filename
 * @param boolean $checkdb
 *
 * @return boolean
 */
function urkund_check_file_type($filename, $checkdb = true) {
    $pathinfo = pathinfo($filename);

    if (empty($pathinfo['extension'])) {
        return '';
    }
    $ext = strtolower($pathinfo['extension']);
    $filetypes = urkund_default_allowed_file_types();

    if (!empty($filetypes[$ext])) {
        return $filetypes[$ext];
    }
    // Check for updated allowed filetypes.
    if ($checkdb) {
        return get_config('plagiarism_urkund', 'ext_'.$ext);
    } else {
        return false;
    }
}

/**
 * Used to obtain allowed file types
 *
 * @param boolean $checkdb
 * @return array()
 */
function urkund_default_allowed_file_types($checkdb = false) {
    global $DB;
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

    if ($checkdb) {
        // Get all filetypes from db as well.
        $sql = 'SELECT name, value FROM {config_plugins} WHERE plugin = :plugin AND ' . $DB->sql_like('name', ':name');
        $types = $DB->get_records_sql($sql, array('name' => 'ext_%', 'plugin' => 'plagiarism_urkund'));
        foreach ($types as $type) {
            $ext = strtolower(str_replace('ext_', '', $type->name));
            $filetypes[$ext] = $type->value;
        }
    }

    return $filetypes;

}

/**
 * Used to obtain similarity scores from URKUND for submitted files.
 *
 * @param object $plagiarismsettings - from a call to plagiarism_get_settings.
 *
 */
function urkund_get_scores($plagiarismsettings) {
    global $DB;

    mtrace("getting URKUND similarity scores");
    // Get all files set that have been submitted.
    $files = $DB->get_recordset('plagiarism_urkund_files', array('statuscode' => URKUND_STATUSCODE_ACCEPTED));
    foreach ($files as $plagiarismfile) {
        urkund_get_score($plagiarismsettings, $plagiarismfile);
    }
    $files->close();
    // Get all old files using the old identifier.
    $files = $DB->get_recordset('plagiarism_urkund_files', array('statuscode' => URKUND_STATUSCODE_ACCEPTED_OLD));
    foreach ($files as $plagiarismfile) {
        urkund_get_score($plagiarismsettings, $plagiarismfile);
    }
    $files->close();
}

/**
 * Used to obtain similarity score from URKUND for a file.
 *
 * @param stdClass $plagiarismsettings - from a call to plagiarism_get_settings.
 * @param stdClass $plagiarismfile
 * @param boolean $force
 *
 * @return stdClass
 */
function urkund_get_score($plagiarismsettings, $plagiarismfile, $force = false) {
    global $DB;
    // Check if we need to delay this submission.
    if (!$force) {
        $attemptallowed = urkund_check_attempt_timeout($plagiarismfile);
        if (!$attemptallowed) {
            return '';
        }
    }

    $allowedstatus = array(URKUND_STATUSCODE_PROCESSED,
                           URKUND_STATUSCODE_NOT_FOUND,
                           URKUND_STATUSCODE_BAD_REQUEST);
    $successfulstates = array('Analyzed', 'Rejected', 'Error');
    if ($plagiarismfile->statuscode == URKUND_STATUSCODE_ACCEPTED_OLD) {
        $url = old_urkund_get_url($plagiarismsettings['urkund_api'], $plagiarismfile);
    } else {
        $url = urkund_get_url($plagiarismsettings['urkund_api'], $plagiarismfile);
    }

    if (empty($url)) {
        mtrace('ERROR: no receiver address found for this cm: '.$plagiarismfile->cm);
        $plagiarismfile->statuscode = URKUND_STATUSCODE_NORECEIVER;
        $plagiarismfile->errorresponse = get_string('noreceiver', 'plagiarism_urkund');
        $DB->update_record('plagiarism_urkund_files', $plagiarismfile);
        return '';
    }
    $headers = array('Accept-Language: '.$plagiarismsettings['urkund_lang']);

    // Use Moodle curl wrapper to send file.
    $c = new curl(array('proxy' => true));
    $c->setopt(array());
    $c->setopt(array('CURLOPT_RETURNTRANSFER' => 1,
        'CURLOPT_TIMEOUT' => 300, // Set to 5min just in case.
        'CURLOPT_HTTPAUTH' => CURLAUTH_BASIC,
        'CURLOPT_USERPWD' => $plagiarismsettings['urkund_username'].":".$plagiarismsettings['urkund_password']));

    $c->setHeader($headers);
    $response = $c->get($url);
    $httpstatus = $c->info['http_code'];
    if (!empty($httpstatus)) {
        if (in_array($httpstatus, $allowedstatus)) {
            if ($httpstatus == URKUND_STATUSCODE_PROCESSED) {
                // Get similarity score from xml.
                $xml = new SimpleXMLElement($response);
                // When multiple results returned, the last one is the important one.
                $last = count($xml->SubmissionData) - 1;
                $status = (string)$xml->SubmissionData[$last]->Status[0]->State[0];
                if (!empty($status) && in_array($status, $successfulstates)) {
                    $plagiarismfile->statuscode = $status;
                }
                if (!empty($status) && $status == 'Analyzed') {
                    $plagiarismfile->reporturl = (string)$xml->SubmissionData[$last]->Report[0]->ReportUrl[0];
                    $plagiarismfile->similarityscore = (int)$xml->SubmissionData[$last]->Report[0]->Significance[0];
                    $plagiarismfile->optout = (string)$xml->SubmissionData[$last]->Document[0]->OptOutInfo[0]->Url[0];
                    // Now send e-mail to user.
                    $emailstudents = $DB->get_field('plagiarism_urkund_config', 'value',
                                                    array('cm' => $plagiarismfile->cm, 'name' => 'urkund_studentemail'));
                    if (!empty($emailstudents)) {
                        $urkund = new plagiarism_plugin_urkund();
                        $urkund->urkund_send_student_email($plagiarismfile);
                    }
                } else {
                    $plagiarismfile->errorresponse = $response;
                }
            } else {
                $plagiarismfile->statuscode = $httpstatus;
            }
        }
    }
    $plagiarismfile->attempt = $plagiarismfile->attempt + 1;
    $DB->update_record('plagiarism_urkund_files', $plagiarismfile);
    return $plagiarismfile;
}

/**
 * Get url of api.
 *
 * @param string $baseurl
 * @param stdClass $plagiarismfile
 *
 * @return string
 */
function urkund_get_url($baseurl, $plagiarismfile) {
    // Get url of api.
    global $DB, $CFG;
    $receiver = $DB->get_field('plagiarism_urkund_config', 'value', array('cm' => $plagiarismfile->cm,
                                                                          'name' => 'urkund_receiver'));
    if (empty($receiver)) {
        return;
    }

    // Site id passed to Urkund to identify this file is:
    // first 8 chars of this site_indentifier full id isn't used as Urkund has a 64 char limit on the identifier passed,
    // Then the course module id of this plugin,
    // Then the id from the plagiarism_files table,
    // Then the full contenthash of the file.
    if (strpos($plagiarismfile->identifier, $CFG->tempdir) !== false) {
        // In-line text files temporarily use the identifier field as the filepath.
        $identifier = sha1(file_get_contents($plagiarismfile->identifier));
    } else {
        $identifier = $plagiarismfile->identifier;
    }

    $siteid = substr(md5(get_site_identifier()), 0, 8);
    $urkundid = $siteid.'_'.$plagiarismfile->cm.'_'.$plagiarismfile->id.'_'.$identifier;
    // Check if we are over the 64 char limit and strip from $identifier sha1.
    // Collisions not likely as we are also passing cm and plagiarismfile_id.
    if (strlen($urkundid) > 64) {
        $numtoremove = strlen($urkundid) - 64;
        $identifier = substr($identifier, 0, -$numtoremove);

        $urkundid = $siteid.'_'.$plagiarismfile->cm.'_'.$plagiarismfile->id.'_'.$identifier;
    }

    return $baseurl.'/' .trim($receiver).'/'.$urkundid;
}

/**
 * Helper function with static var to save multiple db calls.
 *
 * @param int $cmid
 *
 * @return array
 */
function urkund_cm_use($cmid) {
    global $DB;
    static $useurkund = array();
    if (!isset($useurkund[$cmid])) {
        $pvalues = $DB->get_records_menu('plagiarism_urkund_config', array('cm' => $cmid), '', 'name,value');
        if (!empty($pvalues['use_urkund'])) {
            $useurkund[$cmid] = $pvalues;
        } else {
            $useurkund[$cmid] = false;
        }
    }
    return $useurkund[$cmid];
}

/**
 * Function that returns the name of the css class to use for a given similarity score.
 * @param integer $score - the similarity score
 * @return string - string name of css class
 */
function urkund_get_css_rank ($score) {
    $rank = "none";
    if ($score > 90) {
        $rank = "1";
    } else if ($score > 80) {
        $rank = "2";
    } else if ($score > 70) {
        $rank = "3";
    } else if ($score > 60) {
        $rank = "4";
    } else if ($score > 50) {
        $rank = "5";
    } else if ($score > 40) {
        $rank = "6";
    } else if ($score > 30) {
        $rank = "7";
    } else if ($score > 20) {
        $rank = "8";
    } else if ($score > 10) {
        $rank = "9";
    } else if ($score >= 0) {
        $rank = "10";
    }

    return "rank$rank";
}

/**
 * Function that checks Urkund to see if there are any newly supported filetypes.
 *
 */
function plagiarism_urkund_update_allowed_filetypes() {
    global $CFG, $DB;
    $configvars = get_config('plagiarism_urkund');
    $now = time();
    $wait = (int)URKUND_FILETYPE_URL_UPDATE * 60 * 60;

    if (!isset($configvars->lastupdatedfiletypes)) {
        // First time this has run.
        $configvars->lastupdatedfiletypes = 0;
    }

    $timetocheck = (int)($configvars->lastupdatedfiletypes + $wait);

    if (empty($configvars->lastupdatedfiletypes) ||
        $timetocheck < $now ) {
        // Need to update filetypes.
        // Get list of existing options.
        $existing = array();
        foreach ($configvars as $name => $value) {
            if (strpos($name, 'ext_') !== false) {
                $existing[$name] = $value;
            }
        }

        require_once($CFG->libdir.'/filelib.php');
        $url = URKUND_FILETYPE_URL;
        $c = new curl(array('proxy' => true));
        $c->setopt(array());
        $c->setopt(array('CURLOPT_TIMEOUT' => 300));

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
        // Clean up old vars.
        if (!empty($existing)) {
            foreach ($existing as $name => $value) {
                $DB->delete_records('config_plugins', array('plugin' => 'plagiarism_urkund', 'name' => $name));
            }
        }
        set_config('lastupdatedfiletypes', $now, 'plagiarism_urkund');
    }
}

/**
 * Function used to delete records associated with deleted activities.
 *
 */
function plagiarism_urkund_delete_old_records() {
    global $DB;
    $sql = "SELECT DISTINCT f.cm
              FROM {plagiarism_urkund_config} f
         LEFT JOIN {course_modules} c ON c.id = f.cm
             WHERE c.id IS null AND f.cm <> 0";
    $coursemodules = $DB->get_recordset_sql($sql);
    foreach ($coursemodules as $cm) {
        $DB->delete_records('plagiarism_urkund_config', array('cm' => $cm->cm));
        $DB->delete_records('plagiarism_urkund_files', array('cm' => $cm->cm));
    }
}

/**
 * Old function to get url using old method of generating an indentifier.
 * This function should only be used if the file is known to have been generated using old code.
 *
 * @param string $baseurl
 * @param stdClass $plagiarismfile
 *
 * @return string
 */
function old_urkund_get_url($baseurl, $plagiarismfile) {
    // Get url of api.
    global $DB;
    $receiver = $DB->get_field('plagiarism_urkund_config', 'value', array('cm' => $plagiarismfile->cm,
                                                                          'name' => 'urkund_receiver'));
    if (empty($receiver)) {
        return '';
    }
    return $baseurl.'/' .$receiver.'/'.md5(get_site_identifier()).
    '_'.$plagiarismfile->cm.'_'.$plagiarismfile->id;
}

/**
 * Reset file.
 *
 * @param int|stdClass $file
 * @param stdClass $plagiarismsettings
 *
 * @return boolean
 */
function urkund_reset_file($file, $plagiarismsettings = null) {
    global $DB;
    if (is_int($file)) {
        $plagiarismfile = $DB->get_record('plagiarism_urkund_files', array('id' => $file), '*', MUST_EXIST);
    } else {
        $plagiarismfile = $file;
    }

    if ($plagiarismfile->statuscode == 'Analyzed' ||
        $plagiarismfile->statuscode == URKUND_STATUSCODE_ACCEPTED) {
        // This function is for re-sending files, this file has already been sent.
        return true;
    }

    // Check to make sure cm exists. - delete record if cm has been deleted.
    if (!$DB->record_exists('course_modules', array('id' => $plagiarismfile->cm))) {
        // The coursemodule related to this file has been deleted, delete the urkund entry.
        mtrace("URKUND fileid:$plagiarismfile->id Course module id:".$plagiarismfile->cm. " does not exist, deleting record");
        $DB->delete_records('plagiarism_urkund_files', array('id' => $plagiarismfile->id));
        return true;
    }

    if (empty($plagiarismsettings)) {
        $plagiarismsettings = plagiarism_plugin_urkund::get_settings();
    }

    // Get file object for this submission.
    $fileobject = plagiarism_urkund_get_file_object($plagiarismfile);

    if (!empty($fileobject)) {
        // Set some new values.
        $plagiarismfile->statuscode = 'pending';
        $plagiarismfile->attempt = 0;
        $plagiarismfile->timesubmitted = time();
        $DB->update_record('plagiarism_urkund_files', $plagiarismfile); // Update before trying to send again.

        $plagiarismfile = urkund_queue_file($plagiarismfile->cm, $plagiarismfile->userid, $fileobject);
        if (!empty($plagiarismfile)) {
            // Send this file now.
            urkund_send_file_to_urkund($plagiarismfile, $plagiarismsettings, $fileobject);
        }
        return true;
    }
    mtrace("URKUND fileid:".$plagiarismfile->id. " no files found with this record");
    return false;
}

/**
 * Helper function used to get file record for given identifier.
 *
 * @param stdClass $plagiarismfile
 *
 * @return stdclass
 */
function plagiarism_urkund_get_file_object($plagiarismfile) {
    global $CFG, $DB;
    $userid = $plagiarismfile->userid;
    if (!empty($plagiarismfile->relateduserid)) {
        $userid = $plagiarismfile->relateduserid;
    }
    if (strpos($plagiarismfile->identifier, $CFG->tempdir) !== false) {
        // This is a stored text file in temp dir.
        $file = new stdclass();
        if (file_exists($plagiarismfile->identifier)) {
            $file->type = "tempurkund";
            $file->filename = basename($plagiarismfile->identifier);
            $file->timestamp = time();
            $file->identifier = sha1(file_get_contents($plagiarismfile->identifier));
            $file->filepath = $plagiarismfile->identifier;

            // Sanity check to see if the Sha1 for this file has already been sent to urkund using a different record.
            if ($DB->record_exists('plagiarism_urkund_files', array('identifier' => $file->identifier,
                'cm' => $plagiarismfile->cm,
                'userid' => $plagiarismfile->userid))) {
                // This file has already been sent and multiple records for this file were created
                // Delete plagiarism record and file.
                $DB->delete_records('plagiarism_urkund_files', array('id' => $plagiarismfile->id));
                debugging("This file has been duplicated, deleting the duplicate record. Identifier:".$file->identifier);
                unlink($plagiarismfile->identifier); // Delete temp file as we don't need it anymore.
                return false;
            }
            return $file;
        } else {
            debugging("The local version of this file has been deleted, and this file cannot be sent");
            return false;
        }
    } else {

        $cm = get_coursemodule_from_id('', $plagiarismfile->cm);
        $modulecontext = context_module::instance($plagiarismfile->cm);
        $fs = get_file_storage();
        if ($cm->modname == 'assign') {
            if (debugging()) {
                mtrace("URKUND fileid:" . $plagiarismfile->id . " assignment found");
            }
            require_once($CFG->dirroot . '/mod/assign/locallib.php');
            $assign = new assign($modulecontext, null, null);

            if ($assign->get_instance()->teamsubmission) {
                $submission = $assign->get_group_submission($userid, 0, false);
            } else {
                $submission = $assign->get_user_submission($userid, false);
            }
            $submissionplugins = $assign->get_submission_plugins();

            foreach ($submissionplugins as $submissionplugin) {
                $component = $submissionplugin->get_subtype() . '_' . $submissionplugin->get_type();
                $fileareas = $submissionplugin->get_file_areas();
                foreach ($fileareas as $filearea => $name) {
                    if (debugging()) {
                        mtrace("URKUND fileid:" . $plagiarismfile->id . " Check component:" . $component . " Filearea:" .
                               $filearea . " Submission" . $submission->id);
                    }
                    $files = $fs->get_area_files(
                        $assign->get_context()->id,
                        $component,
                        $filearea,
                        $submission->id,
                        "timemodified",
                        false
                    );

                    foreach ($files as $file) {
                        if (debugging()) {
                            mtrace("URKUND fileid:" . $plagiarismfile->id . " check fileid:" . $file->get_id());
                        }
                        if ($file->get_contenthash() == $plagiarismfile->identifier) {
                            if (debugging()) {
                                mtrace("URKUND fileid:" . $plagiarismfile->id . " found fileid:" . $file->get_id());
                            }
                            return $file;
                        }
                    }
                }
            }
        } else if ($cm->modname == 'workshop') {
            if (debugging()) {
                mtrace("URKUND fileid:" . $plagiarismfile->id . " workshop found");
            }
            require_once($CFG->dirroot . '/mod/workshop/locallib.php');
            $cm = get_coursemodule_from_id('workshop', $plagiarismfile->cm, 0, false, MUST_EXIST);
            $workshop = $DB->get_record('workshop', array('id' => $cm->instance), '*', MUST_EXIST);
            $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
            $workshop = new workshop($workshop, $cm, $course);
            $submissions = $workshop->get_submissions($userid);
            foreach ($submissions as $submission) {
                $files = $fs->get_area_files($workshop->context->id, 'mod_workshop', 'submission_attachment', $submission->id);
                foreach ($files as $file) {
                    if (debugging()) {
                        mtrace("URKUND fileid:" . $plagiarismfile->id . " check fileid:" . $file->get_id());
                    }
                    if ($file->get_contenthash() == $plagiarismfile->identifier) {
                        if (debugging()) {
                            mtrace("URKUND fileid:" . $plagiarismfile->id . " found fileid:" . $file->get_id());
                        }
                        return $file;
                    }
                }
            }
        } else if ($cm->modname == 'forum') {
            if (debugging()) {
                mtrace("URKUND fileid:" . $plagiarismfile->id . " forum found");
            }
            require_once($CFG->dirroot . '/mod/forum/lib.php');
            $cm = get_coursemodule_from_id('forum', $plagiarismfile->cm, 0, false, MUST_EXIST);
            $posts = forum_get_user_posts($cm->instance, $userid);
            foreach ($posts as $post) {
                $files = $fs->get_area_files($modulecontext->id, 'mod_forum', 'attachment', $post->id, "timemodified", false);
                foreach ($files as $file) {
                    if (debugging()) {
                        mtrace("URKUND fileid:" . $plagiarismfile->id . " check fileid:" . $file->get_id());
                    }
                    if ($file->get_contenthash() == $plagiarismfile->identifier) {
                        if (debugging()) {
                            mtrace("URKUND fileid:" . $plagiarismfile->id . " found fileid:" . $file->get_id());
                        }
                        return $file;
                    }
                }
            }
        }
    }
}

/**
 * Function called by scheduled tasks and sends queued files.
 *
 */
function plagiarism_urkund_send_files() {
    global $DB;

    $plagiarismsettings = plagiarism_plugin_urkund::get_settings();
    if (!empty($plagiarismsettings)) {
        // Get all files in a pending state.
        $sql = '(statuscode = ? or statuscode = ?) AND attempt <= ?';
        $plagiarismfiles = $DB->get_recordset_select("plagiarism_urkund_files", $sql,
            array(URKUND_STATUSCODE_PENDING, URKUND_STATUSCODE_INVALID_RESPONSE, PLAGIARISM_URKUND_MAXATTEMPTS));
        foreach ($plagiarismfiles as $pf) {
            // Check to make sure cm exists. - delete record if cm has been deleted.
            $sql = "SELECT m.name
                      FROM {modules} m
                      JOIN {course_modules} cm ON cm.module = m.id
                     WHERE cm.id = ?";
            $modulename = $DB->get_field_sql($sql, array($pf->cm));
            if (empty($modulename)) {
                // The coursemodule related to this file has been deleted, delete the urkund entry.
                mtrace("URKUND fileid:$pf->id Course module id:".$pf->cm. " does not exist, deleting record");
                $DB->delete_records('plagiarism_urkund_files', array('id' => $pf->id));
                continue;
            }
            mtrace("URKUND fileid:".$pf->id. ' sending for processing');
            $file = plagiarism_urkund_get_file_object($pf);
            if (empty($file)) {
                // If file has previously generated a report and it is an active assignment - we don't want to delete it.
                if ($modulename == "assign" && strpos($pf->filename, 'content-') === 0) {
                    $cm = get_coursemodule_from_id('assign', $pf->cm);
                    // This is probably an online file submission - check and regenerate the file if required.
                    $sql = "SELECT a.id, o.onlinetext
                              FROM {assignsubmission_onlinetext} o
                              JOIN {assign_submission} a ON a.id = o.submission
                             WHERE a.userid = ? AND o.assignment = ?
                          ORDER BY a.id DESC";
                    $moodletextsubmissions = $DB->get_records_sql($sql, array($pf->userid, $cm->instance), 0, 1);
                    $moodletextsubmission = end($moodletextsubmissions);
                    $tempfile = urkund_create_temp_file($cm->id, $cm->course, $pf->userid, $moodletextsubmission);

                    $pf->identifier = $tempfile;
                    $DB->update_record('plagiarism_urkund_files', $pf);
                    $file = plagiarism_urkund_get_file_object($pf);
                    if (empty($file)) {
                        mtrace("URKUND fileid:$pf->id online submission found, but unable to create new temp file");
                        continue;
                    }
                } else if (empty($pf->reporturl)) { // File not found and no existing report, so delete record.
                    mtrace("URKUND fileid:$pf->id File not found, this may have been replaced by a newer file - deleting record");
                    if (debugging()) {
                        mtrace(plagiarism_urkund_pretty_print($pf));
                    }
                    $DB->delete_records('plagiarism_urkund_files', array('id' => $pf->id));
                    continue;
                } else {
                    // No file found, but we have a report, so don't delete.
                    continue;
                }
            }
            if ($pf->statuscode == URKUND_STATUSCODE_INVALID_RESPONSE) {
                // Check if we can handle this attempt.
                if (!urkund_check_attempt_timeout($pf)) {
                    mtrace("URKUND fileid:$pf->id File, Attempt:$pf->attempt, failed, queued for resumbission after wait.");
                    continue;
                }
            }
            if ($modulename == "assign") {
                // Check for group assignment and adjust userid if required.
                // This prevents subsequent group submissions from flagging a previous submission as a match.
                $pf = plagiarism_urkund_check_group($pf);
            }
            urkund_send_file_to_urkund($pf, $plagiarismsettings, $file);
        }
    }
}

/**
 * Check if assign group submission is being used in assign activity.
 * This is based on old-code, there might be a more efficient way to do this.
 *
 * @param stdClass $plagiarismfile
 * @return stdClass
 */
function plagiarism_urkund_check_group($plagiarismfile) {
    global $DB, $CFG;

    require_once("$CFG->dirroot/mod/assign/locallib.php");

    $modulecontext = context_module::instance($plagiarismfile->cm);
    $assign = new assign($modulecontext, false, false);

    if (!empty($assign->get_instance()->teamsubmission)) {
        mtrace("URKUND fileid:".$plagiarismfile->id." Group submission detected.");
        $mygroups = groups_get_user_groups($assign->get_course()->id, $plagiarismfile->userid);
        if (count($mygroups) == 1) {
            $groupid = reset($mygroups)[0];
            // Only users with single groups are supported - otherwise just use the normal userid on this record.
            // Get all users from this group.
            $userids = array();
            $users = groups_get_members($groupid, 'u.id');
            foreach ($users as $u) {
                $userids[] = $u->id;
            }
            if (!empty($userids)) {
                // Find the earliest plagiarism record for this cm with any of these users.
                $sql = 'cm = ? AND userid IN (' . implode(',', $userids) . ')';
                $previousfiles = $DB->get_records_select('plagiarism_urkund_files', $sql,
                    array($plagiarismfile->cm), 'id');
                $sanitycheckusers = 10; // Search through this number of users to find a valid previous submission.
                $i = 0;
                foreach ($previousfiles as $pf) {
                    if ($pf->userid == $plagiarismfile->userid) {
                        return $plagiarismfile;
                    }
                    // Sanity Check to make sure the user isn't in multiple groups.
                    $pfgroups = groups_get_user_groups($assign->get_course()->id, $pf->userid);
                    if (count($pfgroups) == 1) {
                        // This user made the first valid submission so use their id when sending the file.
                        $plagiarismfile->userid = $pf->userid;
                        mtrace("URKUND: Group submission by newuser, modify to use original userid:".
                               $pf->userid." id:".$plagiarismfile->id);
                        return $plagiarismfile;
                    }
                    if ($i >= $sanitycheckusers) {
                        // Don't cause a massive loop here and break at a sensible limit.
                        return $plagiarismfile;
                    }
                    $i++;
                }
            }
        }
    }
    return $plagiarismfile;
}

/**
 * Function used to clean up after successful text based submission.
 * We only delete if the file was sucessfully sent to help a future reset.
 *
 * @param stdClass $plagiarismfile
 */
function plagiarism_urkund_fix_temp_hash($plagiarismfile) {
    global $DB, $CFG;
    // Text files temporarily use the filepath in the identifier field - convert this to contenthash.
    if ($plagiarismfile->statuscode == URKUND_STATUSCODE_ACCEPTED &&
        strpos($plagiarismfile->identifier, $CFG->tempdir) !== false) {
        mtrace("URKUND fileid:".$plagiarismfile->id ." online text submission succesful, deleting temp file.");
        // If this was a succesful submission, convert identifier and delete temp file.
        $filepath = $plagiarismfile->identifier;
        $plagiarismfile->identifier = sha1(file_get_contents($plagiarismfile->identifier));
        $DB->update_record('plagiarism_urkund_files', $plagiarismfile);

        unlink($filepath); // Delete temp file as we don't need it anymore.
    }
}

/**
 * We are not allowed to use print_object so use a hand-rolled function to help with debugging.
 *
 * @param stdClass|array $arr
 */
function plagiarism_urkund_pretty_print($arr) {
    if (is_object($arr)) {
        $arr = (array) $arr;
    }
    $retstr = '<table class="generaltable">';
    $retstr .= '<tr><th width=20%>Key</th><th width=80%>Value</th></tr>';
    if (is_array($arr)) {
        foreach ($arr as $key => $val) {
            if (is_object($val)) {
                $val = (array) $val;
            }
            if (is_array($val)) {
                $retstr .= '<tr><td>' . $key . '</td><td>' . plagiarism_urkund_pretty_print($val) . '</td></tr>';
            } else {
                $retstr .= '<tt><td>' . $key . '</td><td>' . ($val == '' ? '""' : $val) . '</td></tr>';
            }
        }
    }
    $retstr .= '</table>';
    return $retstr;
}

/**
 * Function called by scheduled tasks. Resubmits work to URKUND upon closure of submission period.
 *
 */
function plagiarism_urkund_resubmit_on_close() {
    global $DB;

    // Get all Assignments that use Urkund and have expired duedate that have not been run.
    $now = time();
    $sql = "SELECT a.*, cm.id as cmid from {assign} a
              JOIN {course_modules} cm ON cm.instance = a.id
              JOIN {modules} m ON m.id = cm.module
              JOIN {plagiarism_urkund_config} uc ON uc.cm = cm.id AND uc.name = 'use_urkund' AND uc.value = '1'
              JOIN {plagiarism_urkund_config} uc1 ON uc1.cm = cm.id AND uc1.name = 'urkund_resubmit_on_close' AND uc1.value = :resubmit
         LEFT JOIN {plagiarism_urkund_config} uc2 ON uc2.cm = cm.id AND uc2.name = 'timeresubmitted'
             WHERE m.name = 'assign' AND a.duedate > 1 AND a.duedate < :now
                   AND uc2.value IS NULL OR ". $DB->sql_cast_char2int('uc2.value'). " < a.duedate";
    $assignments = $DB->get_records_sql($sql, array('resubmit' => PLAGIARISM_URKUND_RESUBMITDUEDATE, 'now' => $now));

    // For each Assignment with close date.
    foreach ($assignments as $assignment) {
        plagiarism_urkund_resubmit_cm($assignment->cmid);
    }

    // Get all Assignments that use Urkund and have expired closedate that have not been run.
    $sql = "SELECT a.*, cm.id as cmid from {assign} a
              JOIN {course_modules} cm ON cm.instance = a.id
              JOIN {modules} m ON m.id = cm.module
              JOIN {plagiarism_urkund_config} uc ON uc.cm = cm.id AND uc.name = 'use_urkund' AND uc.value = '1'
              JOIN {plagiarism_urkund_config} uc1 ON uc1.cm = cm.id AND uc1.name = 'urkund_resubmit_on_close'
                                                     AND uc1.value = :resubmit
         LEFT JOIN {plagiarism_urkund_config} uc2 ON uc2.cm = cm.id AND uc2.name = 'timeresubmitted'
             WHERE m.name = 'assign' AND a.cutoffdate > 1 AND a.cutoffdate < :now
                   AND uc2.value IS NULL OR ". $DB->sql_cast_char2int('uc2.value'). " < a.cutoffdate";
    $assignments = $DB->get_records_sql($sql, array('resubmit' => PLAGIARISM_URKUND_RESUBMITCLOSEDATE, 'now' => $now));

    // For each Assignment with cut off date.
    foreach ($assignments as $assignment) {
        plagiarism_urkund_resubmit_cm($assignment->cmid);
    }
}


function plagiarism_urkund_resubmit_cm($cmid) {
    global $DB;

    $now = time();

    // Get plagiarism settings for module.
    $plagiarismvalues = $DB->get_records_menu('plagiarism_urkund_config', array('cm' => $cmid), '', 'name, value');

    // Rests all plagiarism files that match cmid, have not exceeded their max attempts and not already in the queue.
    $sql = "UPDATE {plagiarism_urkund_files}
               SET statuscode = :newstatus, revision = revision + 1
             WHERE cm = :cmid AND attempt <= :maxa AND statuscode <> :pending AND statuscode <> :waiting";
    $params = array('newstatus' => URKUND_STATUSCODE_PENDING,
                    'cmid' => $cmid,
                    'maxa' => PLAGIARISM_URKUND_MAXATTEMPTS,
                    'pending' => URKUND_STATUSCODE_PENDING,
                    'waiting' => URKUND_STATUSCODE_ACCEPTED);
    $DB->execute($sql, $params);

    if (isset($plagiarismvalues['timeresubmitted'])) {
        $DB->set_field('plagiarism_urkund_config', 'value', $now,
            array('name' => 'timeresubmitted', 'cm' => $cmid));
    } else {
        $newvalue = new stdClass();
        $newvalue->cm = $cmid;
        $newvalue->name = 'timeresubmitted';
        $newvalue->value = $now;
        $DB->insert_record('plagiarism_urkund_config', $newvalue);
    }
    // Trigger event to say this has been called.
    $context = context_module::instance($cmid);
    $event = \plagiarism_urkund\event\assessment_resubmitted::create(array(
        'objectid' => $cmid,
        'context' => $context,
        'other' => array()
    ));
    $event->trigger();
}
