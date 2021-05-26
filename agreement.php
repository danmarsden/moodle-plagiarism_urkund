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
 * agreement.php - Displays Ouriginal agreement to student
 *
 * @package    plagiarism_urkund
 * @author     Dan Marsden
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once($CFG->libdir . '/plagiarismlib.php');
require_once($CFG->dirroot . '/plagiarism/urkund/lib.php');
require_once($CFG->libdir . '/formslib.php');

$id = required_param('id', PARAM_INT);  // Course Module ID.
$cm = get_coursemodule_from_id('', $id, 0, false, MUST_EXIST);

require_login($cm->course, true, $cm);
$context = context_module::instance($cm->id);
$PAGE->set_context($context);

$url = new moodle_url('/plagiarism/urkund/agreement.php', ['id' => $cm->id]);
$PAGE->set_url($url);

$courseurl = course_get_url($cm->course);
$assignurl = new \moodle_url('/mod/assign/view.php', [
    'id' => $cm->id,
    'action' => 'editsubmission',
    'ouriginalagreement' => true
]);

$form = new plagiarism_urkund\form\agreement(null, ['id' => $cm->id]);
if ($form->is_cancelled()) {
    redirect($courseurl);
}
if ($data = $form->get_data()) {
    if (!empty($data->ouriginalagreement)) {
        redirect($assignurl);
    }
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('submissionagreement', 'plagiarism_urkund'));
$form->display();
echo $OUTPUT->footer();
