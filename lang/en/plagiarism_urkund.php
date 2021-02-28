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
 * Language strings for Ouriginal plugin.
 *
 * @package    plagiarism_urkund
 * @author     Dan Marsden <dan@danmarsden.com>
 * @copyright  2011 Dan Marsden http://danmarsden.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['allowallsupportedfiles'] = 'Allow all supported file types';
$string['allowallsupportedfiles_help'] = 'This allows the teacher to restrict which file types will be sent to Ouriginal for processing. It does not prevent students from uploading different file types to the assignment.';
$string['areyousurebulk'] = 'Are you sure you want to delete the {$a} file(s) selected?';
$string['areyousurefiltereddelete'] = 'Are you sure you want to delete the {$a} file(s) that match the current filter?';
$string['areyousurefilteredresubmit'] = 'Are you sure you want to resubmit the {$a} file(s) that match the current filter?';
$string['assessmentresubmitted'] = 'Assessment resubmitted';
$string['assignforcesubmissionstatement'] = 'Force assignment submission statement';
$string['assignforcesubmissionstatement_desc'] = 'If enabled, this will force the assignment submission statement to be enabled when Ouriginal is enabled in an assignment activity.<br/>
<strong>NOTE: ticking this box will find all existing assignments in your site with Ouriginal enabled and will turn the submission statement on.</strong>';
$string['assignsettings'] = 'Assign settings';
$string['attempts'] = 'Attempts made';
$string['confirmresetall'] = 'This will reset all files in the state: {$a}';
$string['courseshortname'] = 'Course shortname';
$string['cronwarningscores'] = 'The Ouriginal get scores task has not been run for at least 30 min - Cron must be configured to allow Ouriginal to function correctly.';
$string['cronwarningsendfiles'] = 'The Ouriginal send files task has not been run for at least 30 min - Cron must be configured to allow Ouriginal to function correctly.';
$string['debugfilter'] = 'Filter files by';
$string['defaultsdesc'] = 'The following settings are the defaults set when enabling Ouriginal within an Activity Module';
$string['defaultupdated'] = 'Default values updated';
$string['deletedwarning'] = 'This file could not be found - it may have been deleted by the user';
$string['deleteselectedfiles'] = 'Delete selected';
$string['deleteallfiltered'] = 'Delete all files that match the current filter';
$string['explainerrors'] = 'This page lists any files that are currently in an error state. <br/>When files are deleted on this page they will not be able to be resubmitted and errors will no longer display to teachers or students';
$string['file'] = 'File';
$string['filedeleted'] = 'File deleted from queue';
$string['filereset'] = 'A file has been reset for re-submission to Ouriginal';
$string['fileresubmitted'] = 'File Queued for resubmission';
$string['filesresubmitted'] = '{$a} files resubmitted';
$string['filter7'] = 'Exclude older than 7 days';
$string['filter30'] = 'Exclude older than 30 days';
$string['filter90'] = 'Exclude older than 90 days';
$string['getallscores'] = 'Get all scores';
$string['getscore'] = 'Get score';
$string['getscores'] = 'Get scores';
$string['heldevents'] = 'Held events';
$string['heldeventsdescription'] = 'These are events that did not complete on the first attempt and were queued for resubmission - these prevent subsequent events from completing and may need further investigation. Some of these events may not be relevant to Ouriginal.';
$string['id'] = 'ID';
$string['identifier'] = 'Identifier';
$string['module'] = 'Module';
$string['modulenotfound'] = 'Could not find module for {$a->module} with id {$a->modid}.';
$string['name'] = 'Name';
$string['nofilter'] = 'No filter';
$string['noreceiver'] = 'No receiver address was specified';
$string['optout'] = 'Opt-out';
$string['pending'] = 'This file is pending submission to Ouriginal';
$string['pluginname'] = 'Ouriginal plagiarism plugin';
$string['previouslysubmitted'] = 'Previously submitted as';
$string['processing'] = 'This file has been submitted to Ouriginal, now waiting for the analysis to be available';
$string['receivernotvalid'] = 'This is not a valid receiver address.';
$string['recordsdeleted'] = '{$a} error(s) were deleted';
$string['report'] = 'report';
$string['restrictcontent'] = 'Submit attached files and in-line text';
$string['restrictcontent_help'] = 'Ouriginal can process uploaded files but can also process in-line text from forum posts and text from the online text assignment submission type. You can decide which components to send to Ouriginal.';
$string['restrictcontentfiles'] = 'Only submit attached files';
$string['restrictcontentno'] = 'Submit everything';
$string['restrictcontenttext'] = 'Only submit in-line text';
$string['restrictfiles'] = 'File types to submit';
$string['resubmit'] = 'Resubmit';
$string['resubmitall'] = 'Resubmit all with status: {$a}';
$string['resubmitallfiltered'] = 'Resubmit all files that match the current filter';
$string['savedconfigfailed'] = 'An incorrect username/password combination or api address has been entered, Ouriginal has been disabled, please try again.';
$string['savedconfigsuccess'] = 'Plagiarism Settings Saved';
$string['scoreavailable'] = 'This file has been processed by Ouriginal and a report is now available.';
$string['scorenotavailableyet'] = 'This file has not been processed by Ouriginal yet.';
$string['sendfiles'] = 'Send queued files';
$string['showall'] = 'Show all errors';
$string['showwhencutoff'] = 'After activity cut off date';
$string['showwhendue'] = 'After activity due date';
$string['similarity'] = 'Ouriginal';
$string['status'] = 'Status';
$string['storedocuments'] = 'Add submissions to Ouriginal database';
$string['storedocuments_help'] = 'If set to yes, submissions will be added to the Ouriginal database for future comparison with other submissions, if set to No the document will be deleted from Ouriginal after anaylsis is complete.';
$string['studentdisclosure'] = 'Student disclosure';
$string['studentdisclosure_help'] = 'This text will be displayed to all students on the file upload page.';
$string['studentdisclosuredefault']  = 'All files uploaded will be submitted to the plagiarism detection service Ouriginal,
If you wish to prevent your document from being used as a source for analysis outside this site by other organisations you can use the opt-out link provided after the report has been generated.';
$string['studentemailcontent'] = 'The file you submitted to {$a->modulename} in {$a->coursename} has now been processed by the Plagiarism tool Ouriginal.
{$a->modulelink}

If you wish to prevent your document from being used as a source for analysis outside this site by other organisations you can use this link to opt-out:.
{$a->optoutlink}';
$string['studentemailcontentnoopt'] = 'The file you submitted to {$a->modulename} in {$a->coursename} has now been processed by the Plagiarism tool Ouriginal.
{$a->modulelink}';

$string['studentemailsubject'] = 'File processed by Ouriginal';
$string['submitondraft'] = 'Submit file when first uploaded';
$string['submitonfinal'] = 'Submit file when student sends for marking';
$string['toolarge'] = 'This file is too large for Ouriginal to process';
$string['unknownwarning'] = 'An error occurred trying to send this file to Ouriginal';
$string['unknownwarninggetscore'] = 'An error occurred trying to get the score for this file from Ouriginal';
$string['unsupportedfiletype'] = 'This filetype is not supported by Ouriginal';
$string['updateallowedfiletypes'] = 'Update allowed file types and delete Ouriginal records associated with deleted activities.';
$string['urkund'] = 'Ouriginal plagiarism plugin';
$string['urkund:enable'] = 'Allow the teacher to enable/disable Ouriginal inside an activity';
$string['urkund:resetfile'] = 'Allow the teacher to resubmit the file to Ouriginal after an error';
$string['urkund:viewreport'] = 'Allow the teacher to view the full report from Ouriginal';
$string['urkund_advanceditems'] = 'Set of settings to consider advanced';
$string['urkund_advanceditems_help'] = 'The list of settings set as advanced here, will be shown as advanced in module settings. If so, they will be also hidden from teachers if they do not have capability \'urkund:advancedsettings\'.';
$string['urkund_api'] = 'Ouriginal integration address';
$string['urkund_api_help'] = 'This is the address of the Ouriginal API, default: https://secure.urkund.com';
$string['urkund_draft_submit'] = 'When should the file be submitted';
$string['urkund_enableoptout'] = 'Show opt-out link';
$string['urkund_enableoptoutdesc'] = 'Disabling this will remove the option for students to unhide or hide (depending on the default setting) the content of their texts should they be found as a match in other clients’ students’ papers (“opt-in” and “opt-out”). By deactivating this feature, you certify that you will take responsibility for managing the copyright of your students’ submissions and that this does not contravene laws applicable in your country.';
$string['urkund_enableplugin'] = 'Enable Ouriginal for {$a}';
$string['urkund_hidefilename'] = 'Hide submission filename';
$string['urkund_hidefilenamedesc'] = 'Enabling this will pass a generic filename to Ouriginal so students cannot see the filename of any sources that match an existing submission.';
$string['urkund_lang'] = 'Language';
$string['urkund_lang_help'] = 'Language code provided by Ouriginal';
$string['urkund_password'] = 'Password';
$string['urkund_password_help'] = 'Password provided by Ouriginal to access the API';
$string['urkund_unitid'] = 'Unit ID';
$string['urkund_unitid_help'] = 'Ouriginal can automatically create receiver addresses for each user, leave this setting empty if you want to use the same default address for all assignments, contact Ouriginal for the unitid to use if you would like to use this feature. Warning: setting this value will also clear your site-level default receiver addresses as these will no longer apply.';
$string['urkund_receiver'] = 'Receiver address';
$string['urkund_receiver_help'] = 'This is the unique address provided from Ouriginal for the teacher';
$string['urkund_resubmit_on_close'] = 'Resubmit on close';
$string['urkund_resubmit_on_close_desc'] = 'Resubmit work to Ouriginal upon closure of submission period.';
$string['urkund_resubmit_on_close_help'] = 'Enabling this option will cause submissions to be resent to Ouriginal on closure of the assignment submission period.';
$string['urkund_show_student_report'] = 'Show similarity report to student';
$string['urkund_show_student_report_help'] = 'The similarity report gives a breakdown on what parts of the submission were plagiarised and the location that Ouriginal first saw this content';
$string['urkund_show_student_score'] = 'Show similarity score to student';
$string['urkund_show_student_score_help'] = 'The similarity score is the percentage of the submission that has been matched with other content.';
$string['urkund_studentemail'] = 'Send student email';
$string['urkund_studentemail_help'] = 'This will send an e-mail to the student when a file has been processed to let them know that a report is available, the e-mail also includes the opt-out link.';
$string['urkund_username'] = 'Username';
$string['urkund_username_help'] = 'Username provided by Ouriginal to access the API';
$string['urkund_userpref'] = 'Use last saved receiver';
$string['urkund_userprefdesc'] = 'If enabled, the last receiver address entered by the user will be saved as the default address to use when the user creates a new assignment. If not enabled the site default will always be used.';
$string['urkunddebug'] = 'Debugging';
$string['urkunddefaults'] = 'Ouriginal defaults';
$string['urkunddefaults_assign'] = 'Default assign settings';
$string['urkunddefaults_forum'] = 'Default forum settings';
$string['urkunddefaults_workshop'] = 'Default workshop settings';
$string['urkunddefaults_hsuforum'] = 'Default hsuforum settings';
$string['urkunddefaults_quiz'] = 'Default quiz settings';
$string['urkundexplain'] = 'For more information on this plugin see: <a href="https://ouriginal.com" target="_blank">ouriginal.com</a>';
$string['urkundfiles'] = 'Ouriginal Files';
$string['useurkund'] = 'Enable Ouriginal';
$string['waitingevents'] = 'There are {$a->countallevents} events waiting for cron and {$a->countheld} events are being held for resubmission';
$string['charcount'] = 'Minimum character count';
$string['charcount_help'] = 'This sets a minimum limit on the number of characters that are required for in-line text (forum posts and online assignment type) before the content will be sent to Ouriginal.';
$string['cannotupgradeunprocesseddata'] = '';
$string['cannotupgradeunprocesseddata'] = '<h1>Cannot upgrade to this version of the plugin due to existing unprocessed data, please revert to an earlier version of this plugin and clear old events.</h1><p>This version of the plugin relies on the new events API in Moodle but your installation contains un-processed events related to the old API.</p>
 You should revert to an older version of the Ouriginal plugin, put the site into maintenance mode, run the Moodle Cron process and make sure all old events are cleared. Then try upgrading to this version of the Ouriginal plugin again.</p>
 <p>For more information see: <a href="https://docs.moodle.org/en/Plagiarism_Prevention_Ouriginal_Settings#Installation_failed_due_to_unprocessed_data">Ouriginal Installation failed due to unprocessed data</a></p>';
$string['timesubmitted'] = 'Time submitted';
$string['urkund:advancedsettings'] = 'Allow the teacher to view advanced module settings from Ouriginal';
$string['resubmitdue'] = 'Resubmit after due date';
$string['resubmitclose'] = 'Resubmit after close date';
$string['resubmittourkund'] = 'Regenerate all Ouriginal reports';
$string['regenerationrequested'] = 'All reports have been flagged for regeneration, it may take some time before updated reports are available.';
$string['urkund:resubmitallfiles'] = 'Allow the teacher to resubmit ALL files to Ouriginal';
$string['urkund:resubmitonclose'] = 'Allow the teacher to resubmit files on close/due date to Ouriginal';
$string['errorcode_3'] = 'Error: Document too short';
$string['errorcode_4'] = 'Error: Deadline exceeded';
$string['errorcode_101'] = 'Error: Document cap reached';
$string['errorcode_5000'] = 'Error: Report generation failed';
$string['errorcode_7001'] = 'Error: Failed to index';
$string['errorcode_unknown'] = 'Error: {$a}';
$string['errorcode_403'] = 'Failed to create receiver address for your account, you must enter one manually.';
$string['errorcode_409'] = 'Your receiver address has been deleted and needs to be restored, please contact Ouriginal to restore this account';
$string['errorcreate'] = 'You must manually enter an analysis address.';

$string['privacy:metadata:core_files'] = 'Files and text attached to activity modules where the Ouriginal plugin is enabled.';
$string['privacy:metadata:plagiarism_urkund_files:userid'] = 'The Moodle userid';
$string['privacy:metadata:plagiarism_urkund_files:similarityscore'] = 'Similarity score returned from Ouriginal';
$string['privacy:metadata:plagiarism_urkund_files:lastmodified'] = 'Time when the record was last updated';
$string['privacy:metadata:plagiarism_urkund_files'] = 'Stores information on the submitted files.';
$string['privacy:metadata:plagiarism_urkund_client:email'] = 'User email';
$string['privacy:metadata:plagiarism_urkund_client:filename'] = 'Filename of submitted file.';
$string['privacy:metadata:plagiarism_urkund_client:file'] = 'Physical copy of text or file content sent to Ouriginal';
$string['privacy:metadata:plagiarism_urkund_client'] = 'User information sent to external Ouriginal API';

$string['status_404'] = "Analysis address doesn't exist";
$string['status_202'] = 'Received, waiting for report';
$string['status_444'] = 'No analysis address added';
$string['status_415'] = 'File format not supported';
$string['status_413'] = 'File too large';
$string['status_pending'] = 'Waiting to be sent';
