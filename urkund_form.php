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
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}
require_once($CFG->dirroot.'/lib/formslib.php');

class urkund_setup_form extends moodleform {

    // Define the form.
    function definition () {
        global $CFG;

        $mform =& $this->_form;
        $mform->addElement('html', get_string('urkundexplain', 'plagiarism_urkund'));
        $mform->addElement('checkbox', 'urkund_use', get_string('useurkund', 'plagiarism_urkund'));

        $mform->addElement('text', 'urkund_api', get_string('urkund_api', 'plagiarism_urkund'));
        $mform->addHelpButton('urkund_api', 'urkund_api', 'plagiarism_urkund');
        $mform->addRule('urkund_api', null, 'required', null, 'client');
        $mform->setDefault('urkund_api', 'https://secure.urkund.com/api/submissions');
        $mform->setType('urkund_api', PARAM_URL);

        $mform->addElement('text', 'urkund_username', get_string('urkund_username', 'plagiarism_urkund'));
        $mform->addHelpButton('urkund_username', 'urkund_username', 'plagiarism_urkund');
        $mform->addRule('urkund_username', null, 'required', null, 'client');
        $mform->setType('urkund_username', PARAM_TEXT);

        $mform->addElement('passwordunmask', 'urkund_password', get_string('urkund_password', 'plagiarism_urkund'));
        $mform->addHelpButton('urkund_password', 'urkund_password', 'plagiarism_urkund');
        $mform->addRule('urkund_password', null, 'required', null, 'client');
        $mform->setType('urkund_password', PARAM_TEXT);

        $mform->addElement('text', 'urkund_lang', get_string('urkund_lang', 'plagiarism_urkund'));
        $mform->addHelpButton('urkund_lang', 'urkund_lang', 'plagiarism_urkund');
        $mform->addRule('urkund_lang', null, 'required', null, 'client');
        $mform->setDefault('urkund_lang', 'en-US');
        $mform->setType('urkund_lang', PARAM_TEXT);

        $mform->addElement('textarea', 'urkund_student_disclosure', get_string('studentdisclosure', 'plagiarism_urkund'),
                           'wrap="virtual" rows="6" cols="50"');
        $mform->addHelpButton('urkund_student_disclosure', 'studentdisclosure', 'plagiarism_urkund');
        $mform->setDefault('urkund_student_disclosure', get_string('studentdisclosuredefault', 'plagiarism_urkund'));
        $mform->setType('urkund_student_disclosure', PARAM_TEXT);

        $mform->addElement('checkbox', 'urkund_optout', get_string('urkund_enableoptout', 'plagiarism_urkund'), '<br/>'.get_string('urkund_enableoptoutdesc', 'plagiarism_urkund'));
        $mform->setDefault('urkund_optout', true);

        $mform->addElement('text', 'urkund_wordcount', get_string('wordcount', 'plagiarism_urkund'));
        $mform->addHelpButton('urkund_wordcount', 'wordcount', 'plagiarism_urkund');
        $mform->setType('urkund_wordcount', PARAM_INT);
        $mform->addRule('urkund_wordcount', null, 'required', null, 'client');
        $mform->setDefault('urkund_wordcount', '50');


        $mods = core_component::get_plugin_list('mod');
        foreach ($mods as $mod => $modname) {
            if (plugin_supports('mod', $mod, FEATURE_PLAGIARISM)) {
                $modstring = 'urkund_enable_mod_' . $mod;
                $mform->addElement('checkbox', $modstring, get_string('urkund_enableplugin', 'plagiarism_urkund', $mod));
            }
        }

        $this->add_action_buttons(true);
    }
}

class urkund_defaults_form extends moodleform {

    // Define the form.
    function definition () {
        $mform =& $this->_form;
        urkund_get_form_elements($mform);
        $this->add_action_buttons(true);
    }
}
