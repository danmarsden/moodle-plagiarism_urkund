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
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
require_once($CFG->dirroot.'/lib/formslib.php');

class urkund_setup_form extends moodleform {

    /// Define the form
    function definition () {
        global $CFG;

        $mform =& $this->_form;
        $mform->addElement('html', get_string('urkundexplain', 'plagiarism_urkund'));
        $mform->addElement('checkbox', 'urkund_use', get_string('useurkund', 'plagiarism_urkund'));

        $mform->addElement('text', 'urkund_api', get_string('urkund_api', 'plagiarism_urkund'));
        $mform->addHelpButton('urkund_api', 'urkund_api', 'plagiarism_urkund');
        $mform->addRule('urkund_api', null, 'required', null, 'client');
        $mform->setDefault('urkund_api', 'https://secure.urkund.com/ws/integration/1.0/rest/submissions/');

        $mform->addElement('text', 'urkund_username', get_string('urkund_username', 'plagiarism_urkund'));
        $mform->addHelpButton('urkund_username', 'urkund_username', 'plagiarism_urkund');
        $mform->addRule('urkund_username', null, 'required', null, 'client');

        $mform->addElement('passwordunmask', 'urkund_password', get_string('urkund_password', 'plagiarism_urkund'));
        $mform->addHelpButton('urkund_password', 'urkund_password', 'plagiarism_urkund');
        $mform->addRule('urkund_password', null, 'required', null, 'client');

        $mform->addElement('text', 'urkund_lang', get_string('urkund_lang', 'plagiarism_urkund'));
        $mform->addHelpButton('urkund_lang', 'urkund_lang', 'plagiarism_urkund');
        $mform->addRule('urkund_lang', null, 'required', null, 'client');
        $mform->setDefault('urkund_lang', 'en-US');

        $mform->addElement('textarea', 'urkund_student_disclosure', get_string('studentdisclosure', 'plagiarism_urkund'),
                           'wrap="virtual" rows="6" cols="50"');
        $mform->addHelpButton('urkund_student_disclosure', 'studentdisclosure', 'plagiarism_urkund');
        $mform->setDefault('urkund_student_disclosure', get_string('studentdisclosuredefault', 'plagiarism_urkund'));

        $this->add_action_buttons(true);
    }
}

class urkund_defaults_form extends moodleform {

    /// Define the form
    function definition () {
        $mform =& $this->_form;
        urkund_get_form_elements($mform);
        $this->add_action_buttons(true);
    }
}