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
 * plagiarism.php - allows the admin to configure plagiarism stuff
 *
 * @package   plagiarism_urkund
 * @author    Dan Marsden <dan@danmarsden.com>
 * @copyright 2011 onwards Dan Marsden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/plagiarismlib.php');
require_once($CFG->dirroot.'/plagiarism/urkund/lib.php');

require_login();
admin_externalpage_setup('plagiarismurkund');

plagiarism_urkund_checkcronhealth();

$context = context_system::instance();
require_capability('moodle/site:config', $context, $USER->id, true, "nopermissions");

$mform = new plagiarism_urkund_setup_form();
$plagiarismplugin = new plagiarism_plugin_urkund();

if ($mform->is_cancelled()) {
    redirect('');
}

echo $OUTPUT->header();
$currenttab = 'urkundsettings';
require_once('urkund_tabs.php');
if (($data = $mform->get_data()) && confirm_sesskey()) {
    if (!isset($data->enabled)) {
        $data->enabled = 0;
    }

    $supportedmodules = urkund_supported_modules();
    foreach ($supportedmodules as $mod) {
        if (plugin_supports('mod', $mod, FEATURE_PLAGIARISM)) {
            $modstring = 'enable_mod_' . $mod;
            if (!isset($data->$modstring)) {
                $data->$modstring = 0;
            }
        }
    }
    // This form contains some checkboxes - set them to 0 if needed.
    $checkboxes = ['optout', 'hidefilename', 'userpref', 'assignforcesubmissionstatement',
                   'assignpreventexistingenable', 'assignforcedisclosureagreement'];
    foreach ($checkboxes as $c) {
        if (!isset($data->$c)) {
            $data->$c = 0;
        }
    }

    foreach ($data as $field => $value) {
        if ($field != 'submitbutton') { // Ignore the button.
            $value = trim($value); // Strip trailing spaces to help prevent copy/paste issues with username/password.
            if ($field == 'api') { // Strip trailing slash from api.
                $value = rtrim($value, '/');
            }
            if ($field == 'unitid' && $value != 0) {
                // Unset receiver address defaults.
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
            }
            // If previous value of assignforcesubmissionstatement was empty and it's being set now, update the existing records.
            if ($field == 'assignforcesubmissionstatement' && $value == 1
                && empty(get_config('plagiarism_urkund', 'assignforcesubmissionstatement'))) {
                // SQL for all assign activities that have urkund enabled.
                $allassign = "SELECT a.id
                                FROM {assign} a
                                JOIN {course_modules} cm ON cm.instance = a.id
                                JOIN {modules} m ON m.id = cm.module
                                JOIN {plagiarism_urkund_config} uc ON uc.cm = cm.id AND uc.name = 'use_urkund' AND uc.value = '1'
                                WHERE a.requiresubmissionstatement = 0";
                $sql = "UPDATE {assign} SET requiresubmissionstatement = 1 WHERE id IN ($allassign)";
                $DB->execute($sql);

                // Clear course cache due to new assign settings.
                rebuild_course_cache(0, true);
            }
            set_config($field, $value, 'plagiarism_urkund');
        }
    }
    if (!defined('BEHAT_SITE_RUNNING')) {
        $c = new curl(array('proxy' => true));
        $c->setopt(array());
        $c->setopt(array('CURLOPT_RETURNTRANSFER' => 1,
            'CURLOPT_TIMEOUT' => 60, // Set to 60seconds just in case.
            'CURLOPT_HTTPAUTH' => CURLAUTH_BASIC,
            'CURLOPT_USERPWD' => $data->username . ":" . $data->password));

        $html = $c->get($data->api . '/api/receivers');
        $response = $c->getResponse();
    } else {
        // Fake a success for unit tests.
        $c = new stdClass();
        $c->info['http_code'] = '200';
    }
    // Now check to see if username/password is correct. - this check could probably be improved further.
    if ($c->info['http_code'] != '200') {
        // Disable urkund as this config isn't correct.
        set_config('enabled', 0, 'plagiarism_urkund');
        echo $OUTPUT->notification(get_string('savedconfigfailed', 'plagiarism_urkund'), 'notifyproblem');
        debugging("invalid httpstatuscode returned: ".$c->info['http_code']);
    } else {
        echo $OUTPUT->notification(get_string('savedconfigsuccess', 'plagiarism_urkund'), 'notifysuccess');
    }
}

$plagiarismsettings = (array)get_config('plagiarism_urkund');
$mform->set_data($plagiarismsettings);

echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
$mform->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
