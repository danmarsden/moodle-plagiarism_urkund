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
 * Language strings for Urkund plugin.
 *
 * @package    plagiarism_urkund
 * @author     Dan Marsden <dan@danmarsden.com>
 * @copyright  2011 Dan Marsden http://danmarsden.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['allowallsupportedfiles'] = 'Allow all supported file types';
$string['allowallsupportedfiles_help'] = 'This allows the teacher to restrict which file types will be sent to URKUND for processing. It does not prevent students from uploading different file types to the assignment.';
$string['assessmentresubmitted'] = 'Assessment resubmitted';
$string['attempts'] = 'Attempts made';
$string['confirmresetall'] = 'This will reset all files in the state: {$a}';
$string['cronwarning'] = 'The <a href="../../admin/cron.php">cron.php</a> maintenance script has not been run for at least 30 min - Cron must be configured to allow URKUND to function correctly.';
$string['debugfilter'] = 'Filter files by';
$string['defaultsdesc'] = 'The following settings are the defaults set when enabling URKUND within an Activity Module';
$string['defaultupdated'] = 'Default values updated';
$string['deletedwarning'] = 'This file could not be found - it may have been deleted by the user';
$string['explainerrors'] = 'This page lists any files that are currently in an error state. <br/>When files are deleted on this page they will not be able to be resubmitted and errors will no longer display to teachers or students';
$string['file'] = 'File';
$string['filedeleted'] = 'File deleted from queue';
$string['filereset'] = 'A file has been reset for re-submission to URKUND';
$string['fileresubmitted'] = 'File Queued for resubmission';
$string['filesresubmitted'] = '{$a} files resubmitted';
$string['filter7'] = 'Exclude older than 7 days';
$string['filter30'] = 'Exclude older than 30 days';
$string['filter90'] = 'Exclude older than 90 days';
$string['getallscores'] = 'Get all scores';
$string['getscore'] = 'Get score';
$string['getscores'] = 'Get scores';
$string['heldevents'] = 'Held events';
$string['heldeventsdescription'] = 'These are events that did not complete on the first attempt and were queued for resubmission - these prevent subsequent events from completing and may need further investigation. Some of these events may not be relevant to URKUND.';
$string['id'] = 'ID';
$string['identifier'] = 'Identifier';
$string['module'] = 'Module';
$string['modulenotfound'] = 'Could not find module for {$a->module} with id {$a->modid}.';
$string['name'] = 'Name';
$string['nofilter'] = 'No filter';
$string['noreceiver'] = 'No receiver address was specified';
$string['optout'] = 'Opt-out';
$string['pending'] = 'This file is pending submission to URKUND';
$string['pluginname'] = 'URKUND plagiarism plugin';
$string['previouslysubmitted'] = 'Previously submitted as';
$string['processing'] = 'This file has been submitted to URKUND, now waiting for the analysis to be available';
$string['receivernotvalid'] = 'This is not a valid receiver address.';
$string['report'] = 'report';
$string['restrictcontent'] = 'Submit attached files and in-line text';
$string['restrictcontent_help'] = 'URKUND can process uploaded files but can also process in-line text from forum posts and text from the online text assignment submission type. You can decide which components to send to URKUND.';
$string['restrictcontentfiles'] = 'Only submit attached files';
$string['restrictcontentno'] = 'Submit everything';
$string['restrictcontenttext'] = 'Only submit in-line text';
$string['restrictfiles'] = 'File types to submit';
$string['resubmit'] = 'Resubmit';
$string['resubmitall'] = 'Resubmit all with status: {$a}';
$string['savedconfigfailed'] = 'An incorrect username/password combination has been entered, URKUND has been disabled, please try again.';
$string['savedconfigsuccess'] = 'Plagiarism Settings Saved';
$string['scoreavailable'] = 'This file has been processed by URKUND and a report is now available.';
$string['scorenotavailableyet'] = 'This file has not been processed by URKUND yet.';
$string['sendfiles'] = 'Send queued files';
$string['showall'] = 'Show all errors';
$string['showwhenclosed'] = 'When Activity closed';
$string['similarity'] = 'URKUND';
$string['status'] = 'Status';
$string['studentdisclosure'] = 'Student Disclosure';
$string['studentdisclosure_help'] = 'This text will be displayed to all students on the file upload page.';
$string['studentdisclosuredefault']  = 'All files uploaded will be submitted to the plagiarism detection service URKUND,
If you wish to prevent your document from being used as a source for analysis outside this site by other organisations you can use the opt-out link provided after the report has been generated.';
$string['studentemailcontent'] = 'The file you submitted to {$a->modulename} in {$a->coursename} has now been processed by the Plagiarism tool URKUND.
{$a->modulelink}

If you wish to prevent your document from being used as a source for analysis outside this site by other organisations you can use this link to opt-out:.
{$a->optoutlink}';
$string['studentemailcontentnoopt'] = 'The file you submitted to {$a->modulename} in {$a->coursename} has now been processed by the Plagiarism tool URKUND.
{$a->modulelink}';

$string['studentemailsubject'] = 'File processed by URKUND';
$string['submitondraft'] = 'Submit file when first uploaded';
$string['submitonfinal'] = 'Submit file when student sends for marking';
$string['toolarge'] = 'This file is too large for URKUND to process';
$string['unknownwarning'] = 'An error occurred trying to send this file to URKUND';
$string['unsupportedfiletype'] = 'This filetype is not supported by URKUND';
$string['updateallowedfiletypes'] = 'Update allowed file types and delete urkund records associated with deleted activities.';
$string['urkund'] = 'URKUND plagiarism plugin';
$string['urkund:enable'] = 'Allow the teacher to enable/disable URKUND inside an activity';
$string['urkund:resetfile'] = 'Allow the teacher to resubmit the file to URKUND after an error';
$string['urkund:viewreport'] = 'Allow the teacher to view the full report from URKUND';
$string['urkund_advanceditems'] = 'Set of settings to consider advanced';
$string['urkund_advanceditems_help'] = 'The list of settings set as advanced here, will be shown as advanced in module settings. If so, they will be also hidden from teachers if they do not have capability \'urkund:advancedsettings\'.';
$string['urkund_api'] = 'URKUND Integration Address';
$string['urkund_api_help'] = 'This is the address of the URKUND API';
$string['urkund_draft_submit'] = 'When should the file be submitted';
$string['urkund_enableoptout'] = 'Show opt-out link';
$string['urkund_enableoptoutdesc'] = 'Disabling this will remove the option for students to unhide or hide (depending on the default setting) the content of their texts should they be found as a match in other clients’ students’ papers (“opt-in” and “opt-out”). By deactivating this feature, you certify that you will take responsibility for managing the copyright of your students’ submissions and that this does not contravene laws applicable in your country.';
$string['urkund_enableplugin'] = 'Enable URKUND for {$a}';
$string['urkund_hidefilename'] = 'Hide submission filename';
$string['urkund_hidefilenamedesc'] = 'Enabling this will pass a generic filename to URKUND so students cannot see the filename of any sources that match an existing submission.';
$string['urkund_lang'] = 'Language';
$string['urkund_lang_help'] = 'Language code provided by URKUND';
$string['urkund_password'] = 'Password';
$string['urkund_password_help'] = 'Password provided by URKUND to access the API';
$string['urkund_receiver'] = 'Receiver address';
$string['urkund_receiver_help'] = 'This is the unique address provided from URKUND for the teacher';
$string['urkund_resubmit_on_close'] = 'Resubmit on close';
$string['urkund_resubmit_on_close_desc'] = 'Resubmit work to URKUND upon closure of submission period.';
$string['urkund_resubmit_on_close_help'] = 'Enabling this option will cause submissions to be resent to URKUND on closure of the assignment submission period.';
$string['urkund_show_student_report'] = 'Show similarity report to student';
$string['urkund_show_student_report_help'] = 'The similarity report gives a breakdown on what parts of the submission were plagiarised and the location that URKUND first saw this content';
$string['urkund_show_student_score'] = 'Show similarity score to student';
$string['urkund_show_student_score_help'] = 'The similarity score is the percentage of the submission that has been matched with other content.';
$string['urkund_studentemail'] = 'Send student email';
$string['urkund_studentemail_help'] = 'This will send an e-mail to the student when a file has been processed to let them know that a report is available, the e-mail also includes the opt-out link.';
$string['urkund_username'] = 'Username';
$string['urkund_username_help'] = 'Username provided by URKUND to access the API';
$string['urkunddebug'] = 'Debugging';
$string['urkunddefaults'] = 'URKUND defaults';
$string['urkunddefaults_assign'] = 'Default assign settings';
$string['urkunddefaults_forum'] = 'Default forum settings';
$string['urkunddefaults_workshop'] = 'Default workshop settings';
$string['urkundexplain'] = 'For more information on this plugin see: <a href="http://www.urkund.com/en" target="_blank">http://www.urkund.com/int/en/</a>';
$string['urkundfiles'] = 'Urkund Files';
$string['useurkund'] = 'Enable URKUND';
$string['waitingevents'] = 'There are {$a->countallevents} events waiting for cron and {$a->countheld} events are being held for resubmission';
$string['wordcount'] = 'Minimum word count';
$string['wordcount_help'] = 'This sets a minimum limit on the number of words that are required for in-line text (forum posts and online assignment type) before the content will be sent to URKUND.';
$string['cannotupgradeunprocesseddata'] = '';
$string['cannotupgradeunprocesseddata'] = '<h1>Cannot upgrade to this version of the plugin due to existing unprocessed data, please revert to an earlier version of this plugin and clear old events.</h1><p>This version of the plugin relies on the new events API in Moodle but your installation contains un-processed events related to the old API.</p>
 You should revert to an older version of the URKUND plugin, put the site into maintenance mode, run the Moodle Cron process and make sure all old events are cleared. Then try upgrading to this version of the URKUND plugin again.</p>
 <p>For more information see: <a href="https://docs.moodle.org/en/Plagiarism_Prevention_URKUND_Settings#Installation_failed_due_to_unprocessed_data">URKUND Installation failed due to unprocessed data</a></p>';
$string['timesubmitted'] = 'Time submitted';
$string['urkund:advancedsettings'] = 'Allow the teacher to view advanced module settings from URKUND';
$string['resubmitdue'] = 'Resubmit after due date';
$string['resubmitclose'] = 'Resubmit after close date';
$string['resubmittourkund'] = 'Regenerate all URKUND reports';
$string['regenerationrequested'] = 'All reports have been flagged for regeneration, it may take some time before updated reports are available.';
