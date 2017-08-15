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
 * urkund_defaults.php - Displays default values to use inside assignments for URKUND
 *
 * @package plagiarism_urkund
 * @author Dan Marsden <dan@danmarsden.com>
 * @copyright 1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/plagiarismlib.php');
require_once($CFG->dirroot.'/plagiarism/urkund/lib.php');

$id = optional_param('id', 0, PARAM_INT);
$resetuser = optional_param('reset', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$sort = optional_param('tsort', '', PARAM_ALPHA);
$dir = optional_param('dir', '', PARAM_ALPHA);
$download = optional_param('download', '', PARAM_ALPHA);
$showall = optional_param('showall', 0, PARAM_INT);
$resetall = optional_param('resetall', '', PARAM_ALPHANUMEXT);
$confirm = optional_param('confirm', 0, PARAM_INT);
$filterdays = optional_param('filterdays', 0, PARAM_INT);

require_login();

$url = new moodle_url('/plagiarism/urkund/urkund_debug.php', array('showall' => $showall, 'filterdays' => $filterdays));
admin_externalpage_setup('plagiarismurkund', '', array(), $url);

$context = context_system::instance();

$exportfilename = 'UrkundDebugOutput.csv';

$limit = 30;

$baseurl = new moodle_url('urkund_debug.php', array('page' => $page, 'sort' => $sort, 'filterdays' => $filterdays));


$table = new flexible_table('urkundfiles');

if (!$table->is_downloading($download, $exportfilename)) {
    echo $OUTPUT->header();
    $currenttab = 'urkunddebug';

    require_once('urkund_tabs.php');

    $plagiarismsettings = plagiarism_plugin_urkund::get_settings();

    if (!empty($resetall) && confirm_sesskey()) {
        // Check to see if there are any files in this state.
        if (!$confirm) {
            // Show confirmation message.
            $confirmurl = $baseurl;
            $confirmurl->params(array('resetall' => $resetall, 'confirm' => 1));
            echo $OUTPUT->confirm(get_string('confirmresetall', 'plagiarism_urkund', $resetall), $baseurl, $confirmurl);
            echo $OUTPUT->footer();
            exit;

        } else {
            if ($resetall == '202') {
                // Reset attempt value so that we restart the attempt cycle for these records.
                // We don't do this for file submissions because if they fail sending the file it's as likely to change.
                $DB->set_field('plagiarism_urkund_files', 'attempt', 1, array('statuscode' => $resetall));
            }
            $files = $DB->get_records('plagiarism_urkund_files', array('statuscode' => $resetall));
            $i = 0;
            foreach ($files as $plagiarismfile) {
                if ($resetall == '202') {
                    $file = urkund_get_score($plagiarismsettings, $plagiarismfile, true);
                    // Reset attempts as this was a manual check.
                    $file->attempt = $file->attempt - 1;
                    $DB->update_record('plagiarism_urkund_files', $file);
                    if ($file->statuscode == URKUND_STATUSCODE_ACCEPTED) {
                        $response = get_string('scorenotavailableyet', 'plagiarism_urkund');
                    } else if ($file->statuscode == URKUND_STATUSCODE_PROCESSED ||
                               $file->statuscode == 'Analyzed') {
                        $response = get_string('scoreavailable', 'plagiarism_urkund');
                    } else {
                        $response = get_string('unknownwarning', 'plagiarism_urkund');
                        if (debugging()) {
                            echo plagiarism_urkund_pretty_print($file);
                        }
                    }
                    echo "<p>";
                    echo "id:".$file->id.' '. $response;
                    echo "</p>";
                } else {
                    urkund_reset_file($plagiarismfile, $plagiarismsettings);
                }

                $i++;
            }
            if (!empty($i)) {
                echo $OUTPUT->notification(get_string('filesresubmitted', 'plagiarism_urkund', $i), 'notifysuccess');
            }
        }
    }


    $lastcron = $DB->get_field_sql('SELECT MAX(lastcron) FROM {modules}');
    if ($lastcron < time() - 3600 * 0.5) { // Check if run in last 30min.
        echo $OUTPUT->box(get_string('cronwarning', 'plagiarism_urkund'), 'generalbox admin warning');
    }

    if ($resetuser == 1 && $id && confirm_sesskey()) {
        if (urkund_reset_file($id, $plagiarismsettings)) {
            echo $OUTPUT->notification(get_string('fileresubmitted', 'plagiarism_urkund'));
        }
    } else if ($resetuser == 2 && $id && confirm_sesskey()) {
        $plagiarismfile = $DB->get_record('plagiarism_urkund_files', array('id' => $id), '*', MUST_EXIST);
        $file = urkund_get_score(plagiarism_plugin_urkund::get_settings(), $plagiarismfile, true);
        // Reset attempts as this was a manual check.
        $file->attempt = $file->attempt - 1;
        $DB->update_record('plagiarism_urkund_files', $file);
        if ($file->statuscode == URKUND_STATUSCODE_ACCEPTED) {
            echo $OUTPUT->notification(get_string('scorenotavailableyet', 'plagiarism_urkund'));
        } else if ($file->statuscode == URKUND_STATUSCODE_PROCESSED || $file->statuscode == 'Analyzed') {
            echo $OUTPUT->notification(get_string('scoreavailable', 'plagiarism_urkund'));
        } else {
            echo $OUTPUT->notification(get_string('unknownwarning', 'plagiarism_urkund'));
            echo plagiarism_urkund_pretty_print($file);
        }

    }

    if (!empty($delete) && confirm_sesskey()) {
        $DB->delete_records('plagiarism_urkund_files', array('id' => $id));
        echo $OUTPUT->notification(get_string('filedeleted', 'plagiarism_urkund'));

    }
}
// Now show files in an error state.
$userfields = get_all_user_name_fields(true, 'u');
$oldest = time() - $filterdays * 24 * 3600;
$sqlallfiles = "SELECT t.*, ".$userfields.", m.name as moduletype, ".
        "cm.course as courseid, cm.instance as cminstance FROM ".
        "{plagiarism_urkund_files} t, {user} u, {modules} m, {course_modules} cm ".
        "WHERE m.id=cm.module AND cm.id=t.cm AND t.userid=u.id ".
        "AND t.statuscode <> 'Analyzed' ";
if ($filterdays) {
    $oldfilter = "AND t.timesubmitted > $oldest ";
    $sqlallfiles .= $oldfilter;
} else {
    $oldfilter = '';
}

$sqlcount = "SELECT COUNT(id) FROM {plagiarism_urkund_files} t WHERE t.statuscode <> 'Analyzed' $oldfilter";
$count = $DB->count_records_sql($sqlcount);
if (!$showall && !$table->is_downloading()) {
    $table->pagesize($limit, $count);
}

$table->define_columns(array('id', 'name', 'module', 'identifier', 'status', 'attempts', 'timesubmitted', 'action'));

$table->define_headers(array(get_string('id', 'plagiarism_urkund'),
                       get_string('user'),
                       get_string('module', 'plagiarism_urkund'),
                       get_string('identifier', 'plagiarism_urkund'),
                       get_string('status', 'plagiarism_urkund'),
                       get_string('attempts', 'plagiarism_urkund'),
                       get_string('timesubmitted', 'plagiarism_urkund'), ''));
$table->define_baseurl('urkund_debug.php?filterdays=' . $filterdays);
$table->sortable(true);
$table->no_sorting('file', 'action');
$table->collapsible(true);

$table->set_attribute('cellspacing', '0');
$table->set_attribute('class', 'generaltable generalbox');

$table->show_download_buttons_at(array(TABLE_P_BOTTOM));
$table->setup();

// Work out direction of sort required.
$sortcolumns = $table->get_sort_columns();

// Now do sorting if specified.
$orderby = '';
if (!empty($sort)) {
    $direction = ' DESC';
    if (!empty($sortcolumns[$sort]) && $sortcolumns[$sort] == SORT_ASC) {
        $direction = ' ASC';
    }

    if ($sort == "name") {
        $orderby = " ORDER BY u.firstname $direction, u.lastname $direction";
    } else if ($sort == "module") {
        $orderby = " ORDER BY cm.id $direction";
    } else if ($sort == "status") {
        $orderby = " ORDER BY t.statuscode $direction";
    } else if ($sort == "id") {
        $orderby = " ORDER BY t.id $direction";
    } else if ($sort == "timesubmitted") {
        $orderby = " ORDER BY t.timesubmitted $direction";
    }
    if (!empty($orderby) && ($dir == 'asc' || $dir == 'desc')) {
        $orderby .= " ".$dir;
    }
}


if ($showall or $table->is_downloading()) {
    $urkundfiles = $DB->get_records_sql($sqlallfiles.$orderby, null);
} else {
    $urkundfiles = $DB->get_records_sql($sqlallfiles.$orderby, null, $page * $limit, $limit);
}


$fs = get_file_storage();
foreach ($urkundfiles as $tf) {
    $modulecontext = context_module::instance($tf->cm);
    $coursemodule = get_coursemodule_from_id($tf->moduletype, $tf->cm);

    $user = "<a href='".$CFG->wwwroot."/user/profile.php?id=".$tf->userid."'>".fullname($tf)."</a>";
    if (!empty($tf->relateduserid)) {
        $user .= " (On behalf userid: ".$tf->relateduserid .")";
    }
    if ($tf->statuscode == 'Analyzed') { // Sanity check - don't show a resubmit link.
        $reset = '';
    } else if ($tf->statuscode == URKUND_STATUSCODE_ACCEPTED) { // Sanity Check.
        $reset = '<a href="urkund_debug.php?reset=2&id='.$tf->id.'&sesskey='.sesskey().'">'.
                 get_string('getscore', 'plagiarism_urkund').'</a> | ';
    } else {
        $reset = '<a href="urkund_debug.php?reset=1&id='.$tf->id.'&sesskey='.sesskey().'">'.
                 get_string('resubmit', 'plagiarism_urkund').'</a> | ';
    }
    $reset .= '<a href="urkund_debug.php?delete=1&id='.$tf->id.'&sesskey='.sesskey().'">'.get_string('delete').'</a>';
    $cmurl = new moodle_url($CFG->wwwroot.'/mod/'.$tf->moduletype.'/view.php', array('id' => $tf->cm));
    $cmlink = html_writer::link($cmurl, shorten_text($coursemodule->name, 40, true), array('title' => $coursemodule->name));
    if ($table->is_downloading()) {
        $row = array($tf->id, $tf->userid, $tf->cm .' '. $tf->moduletype, $tf->identifier, $tf->statuscode,
                     $tf->attempt, userdate($tf->timesubmitted), $tf->errorresponse);
    } else {
        $row = array($tf->id, $user, $cmlink, $tf->identifier, $tf->statuscode, $tf->attempt, userdate($tf->timesubmitted), $reset);
    }

    $table->add_data($row);
}

if ($table->is_downloading()) {
    // Include some extra debugging information in the table.
    // Add some extra lines first.
    $table->add_data(array());
    $table->add_data(array());
    $table->add_data(array());
    $table->add_data(array());
    $lastcron = $DB->get_field_sql('SELECT MAX(lastcron) FROM {modules}');
    if ($lastcron < time() - 3600 * 0.5) { // Check if run in last 30min.
        $table->add_data(array('', 'errorcron', 'lastrun: '.userdate($lastcron), 'not run in last 30min'));;
    }
    $table->add_data(array());
    $table->add_data(array());

    $configrecords = $DB->get_records('plagiarism_urkund_config');
    $table->add_data(array('id', 'cm', 'name', 'value'));
    foreach ($configrecords as $cf) {
        $table->add_data(array($cf->id, $cf->cm, $cf->name, $cf->value));
    }
    if (!empty($heldevents)) {
        $table->add_data(array());
        $table->add_data(array());
        foreach ($heldevents as $e) {
            $e->eventdata = unserialize(base64_decode($e->eventdata));
            $table->add_data(array('heldevent', $e->status, $e->component, $e->eventname, var_export($e, true)));
        }
    }
}

if (!$table->is_downloading()) {
    echo $OUTPUT->heading(get_string('urkundfiles', 'plagiarism_urkund'));
    echo $OUTPUT->box(get_string('explainerrors', 'plagiarism_urkund'));
    $filterchoices = array(
        0 => get_string('nofilter', 'plagiarism_urkund'),
        7 => get_string('filter7', 'plagiarism_urkund'),
        30 => get_string('filter30', 'plagiarism_urkund'),
        90 => get_string('filter90', 'plagiarism_urkund'),
    );
    echo '<form id="filterdays" action="urkund_debug.php" method="post">';
    echo '<label>' . get_string('debugfilter', 'plagiarism_urkund') . '&nbsp;';
    echo html_writer::select($filterchoices, 'filterdays', $filterdays, false);
    echo '<input type="submit" class="btn btn-sm" value="' . s(get_string('filter')) . '" />';
    echo '</label></form>';
}
$table->finish_output();
if (!$table->is_downloading()) {
    if (!$showall && $count >= $limit) {
        $url = $PAGE->url;
        $url->param('showall', 1);
        echo $OUTPUT->single_button($url, get_string('showall', 'plagiarism_urkund'), 'get');
    }
    $sql = "SELECT DISTINCT statuscode FROM {plagiarism_urkund_files} WHERE statuscode <> 'Analyzed' ORDER BY statuscode";
    $errortypes = $DB->get_records_sql($sql);
    // Display reset buttons.
    echo '<div class="urkundresetbuttons">';
    foreach ($errortypes as $type) {
        $url->param('resetall', $type->statuscode);
        $url->param('sesskey', sesskey());
        if ($type->statuscode == '202') {
            $buttonstr = get_string('getallscores', 'plagiarism_urkund');
        } else {
            $buttonstr = get_string('resubmitall', 'plagiarism_urkund', $type->statuscode);
        }
        echo $OUTPUT->single_button($url, $buttonstr, 'get');
    }

    echo "</div>";
    echo $OUTPUT->footer();
}

