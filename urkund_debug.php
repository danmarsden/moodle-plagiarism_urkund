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
require_once('urkund_form.php');

require_login();
admin_externalpage_setup('plagiarismurkund');

$context = context_system::instance();

$id = optional_param('id', 0, PARAM_INT);
$resetuser = optional_param('reset', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$sort = optional_param('tsort', '', PARAM_ALPHA);
$dir = optional_param('dir', '', PARAM_ALPHA);
$download = optional_param('download', '', PARAM_ALPHA);

$exportfilename = 'UrkundDebugOutput.csv';

$limit = 20;
$baseurl = new moodle_url('urkund_debug.php', array('page' => $page, 'sort' => $sort));

$table = new flexible_table('urkundfiles');

// Get list of Events in queue.
$a = new stdClass();
$a->countallevents = $DB->count_records('events_queue_handlers');
$a->countheld = $DB->count_records_select('events_queue_handlers', 'status > 0');

if (!$table->is_downloading($download, $exportfilename)) {
    echo $OUTPUT->header();
    $currenttab = 'urkunddebug';

    require_once('urkund_tabs.php');

    $lastcron = $DB->get_field_sql('SELECT MAX(lastcron) FROM {modules}');
    if ($lastcron < time() - 3600 * 0.5) { // Check if run in last 30min.
        echo $OUTPUT->box(get_string('cronwarning', 'plagiarism_urkund'), 'generalbox admin warning');
    }

    $warning = '';
    if (!empty($a->countallevents)) {
        $warning = ' warning';
    }

    echo $OUTPUT->box(get_string('waitingevents', 'plagiarism_urkund', $a), 'generalbox admin'.$warning)."<br/>";

    if ($resetuser == 1 && $id && confirm_sesskey()) {
        if (urkund_reset_file($id)) {
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
        } else if ($file->statuscode == URKUND_STATUSCODE_PROCESSED) {
            echo $OUTPUT->notification(get_string('scoreavailable', 'plagiarism_urkund'));
        } else {
            echo $OUTPUT->notification(get_string('unknownwarning', 'plagiarism_urkund'));
            print_object($file);
        }

    }

    if (!empty($delete) && confirm_sesskey()) {
        $DB->delete_records('plagiarism_urkund_files', array('id' => $id));
        echo $OUTPUT->notification(get_string('filedeleted', 'plagiarism_urkund'));

    }
}
$heldevents = array();
if (!empty($a->countheld)) {
    if (!$table->is_downloading()) {
        echo $OUTPUT->heading(get_string('heldevents', 'plagiarism_urkund'));
        echo $OUTPUT->box(get_string('heldeventsdescription', 'plagiarism_urkund'));
    }
    $sql = "SELECT qh.id, qh.status, qh.timemodified, eq.eventdata, eq.stackdump, eq.userid, eh.eventname,
                       eh.component, eh.handlerfile, eh.handlerfunction
                  FROM {events_queue_handlers} qh
                  JOIN {events_queue} eq ON eq.id = qh.queuedeventid
                  JOIN {events_handlers} eh ON eh.id = qh.handlerid
                 WHERE qh.status > 0";
    $heldevents = $DB->get_records_sql($sql);
    if (!$table->is_downloading()) {
        foreach ($heldevents as $e) {
            $e->eventdata = unserialize(base64_decode($e->eventdata));
            // Using print_object here as the data won't display nicely in a table and it's more useful in copy/paste, screenshot.
            print_object($e);
        }
    }
}
// Now show files in an error state.
$userfields = get_all_user_name_fields(true, 'u');
$sqlallfiles = "SELECT t.*, ".$userfields.", m.name as moduletype, ".
        "cm.course as courseid, cm.instance as cminstance FROM ".
        "{plagiarism_urkund_files} t, {user} u, {modules} m, {course_modules} cm ".
        "WHERE m.id=cm.module AND cm.id=t.cm AND t.userid=u.id ".
        "AND t.statuscode <> 'Analyzed' ";

$sqlcount = "SELECT COUNT(id) FROM {plagiarism_urkund_files} WHERE statuscode <> 'Analyzed'";

// Now do sorting if specified.
$orderby = '';
if (!empty($sort)) {
    if ($sort == "name") {
        $orderby = " ORDER BY u.firstname, u.lastname";
    } else if ($sort == "module") {
        $orderby = " ORDER BY cm.id";
    } else if ($sort == "status") {
        $orderby = " ORDER BY t.statuscode";
    } else if ($sort == "id") {
        $orderby = " ORDER BY t.id";
    }
    if (!empty($orderby) && ($dir == 'asc' || $dir == 'desc')) {
        $orderby .= " ".$dir;
    }
}

$count = $DB->count_records_sql($sqlcount);

$urkundfiles = $DB->get_records_sql($sqlallfiles.$orderby, null, $page * $limit, $limit);

$table->define_columns(array('id', 'name', 'module', 'identifier', 'status', 'attempts', 'action'));

$table->define_headers(array(get_string('id', 'plagiarism_urkund'),
                       get_string('user'),
                       get_string('module', 'plagiarism_urkund'),
                       get_string('identifier', 'plagiarism_urkund'),
                       get_string('status', 'plagiarism_urkund'),
                       get_string('attempts', 'plagiarism_urkund'),''));
$table->define_baseurl('urkund_debug.php');
$table->sortable(true);
$table->no_sorting('file', 'action');
$table->collapsible(true);

$table->set_attribute('cellspacing', '0');
$table->set_attribute('class', 'generaltable generalbox');

$table->show_download_buttons_at(array(TABLE_P_BOTTOM));
$table->setup();

$fs = get_file_storage();
foreach ($urkundfiles as $tf) {
    $modulecontext = context_module::instance($tf->cm);
    $coursemodule = get_coursemodule_from_id($tf->moduletype, $tf->cm);

    $user = "<a href='".$CFG->wwwroot."/user/profile.php?id=".$tf->userid."'>".fullname($tf)."</a>";
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
        $row = array($tf->id, $tf->userid, $tf->cm .' '. $tf->moduletype, $tf->identifier, $tf->statuscode, $tf->attempt, $tf->errorresponse);
    } else {
        $row = array($tf->id, $user, $cmlink, $tf->identifier, $tf->statuscode, $tf->attempt, $reset);
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
            // Using print_object here as the data won't display nicely in a table and it's more useful in copy/paste, screenshots.
            $table->add_data(array('heldevent', $e->status, $e->component, $e->eventname, var_export($e, true)));
        }
    }
}

if (!$table->is_downloading()) {
    echo $OUTPUT->heading(get_string('urkundfiles', 'plagiarism_urkund'));
    echo $OUTPUT->box(get_string('explainerrors', 'plagiarism_urkund'));
}
$table->finish_output();
if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}