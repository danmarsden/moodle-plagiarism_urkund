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
require_once($CFG->libdir.'/plagiarismlib.php');
require_once($CFG->dirroot.'/plagiarism/urkund/lib.php');

require_login();
admin_externalpage_setup('plagiarismurkund');

$context = context_system::instance();

$mform = new plagiarism_urkund_defaults_form(null);
$plagiarismdefaults = $DB->get_records_menu('plagiarism_urkund_config',
    array('cm' => 0), '', 'name, value'); // The cmid(0) is the default list.
if (!empty($plagiarismdefaults)) {
    $mform->set_data($plagiarismdefaults);
}
echo $OUTPUT->header();
$currenttab = 'urkunddefaults';
require_once('urkund_tabs.php');
if (($data = $mform->get_data()) && confirm_sesskey()) {
    $plagiarismplugin = new plagiarism_plugin_urkund();

    $plagiarismelements = $plagiarismplugin->config_options(true);
    $supportedmodules = urkund_supported_modules();
    foreach ($supportedmodules as $sm) {
        foreach ($plagiarismelements as $element) {
            $element .= "_".$sm;
            if (isset($data->$element)) {
                $newelement = new Stdclass();
                $newelement->cm = 0;
                $newelement->name = $element;
                if (is_array($data->$element)) {
                    $newelement->value = implode(',', $data->$element);
                } else {
                    $newelement->value = $data->$element;
                }

                if (isset($plagiarismdefaults[$element])) {
                    $newelement->id = $DB->get_field('plagiarism_urkund_config', 'id', (array('cm' => 0, 'name' => $element)));
                    $DB->update_record('plagiarism_urkund_config', $newelement);
                } else {
                    $DB->insert_record('plagiarism_urkund_config', $newelement);
                }
            }
        }
    }
    echo $OUTPUT->notification(get_string('defaultupdated', 'plagiarism_urkund'), 'notifysuccess');
}
echo $OUTPUT->box(get_string('defaultsdesc', 'plagiarism_urkund'));

$mform->display();
echo $OUTPUT->footer();