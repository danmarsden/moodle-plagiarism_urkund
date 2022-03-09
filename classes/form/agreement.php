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
 * Contains agreement class form.
 *
 * @package   plagiarism_urkund
 * @copyright 2021 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_urkund\form;
use \moodleform;

/**
 * Class agreement
 *
 * @package   plagiarism_urkund
 * @copyright 2021 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class agreement extends \moodleform {
    /**
     * Form definition
     */
    public function definition () {
        $mform =& $this->_form;
        $plagiarismsettings = \plagiarism_plugin_urkund::get_settings();

        $formatoptions = new \stdClass();
        $formatoptions->noclean = true;
        $mform->addElement('static', '', '', format_text($plagiarismsettings['student_disclosure'], FORMAT_MOODLE, $formatoptions));

        $mform->addElement('checkbox', 'ouriginalagreement', get_string('iagreetostatement', 'plagiarism_urkund'));

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submit', get_string('iagree', 'plagiarism_urkund'));
        $buttonarray[] = &$mform->createElement('cancel', get_string('idisagree', 'plagiarism_urkund'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        $mform->disabledif('submit', 'ouriginalagreement', 'notchecked');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $this->set_data(array('id' => $this->_customdata['id']));
    }
}
