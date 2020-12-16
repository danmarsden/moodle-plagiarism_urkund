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
 * Contains class plagiarism_urkund_setup_form
 *
 * @package   plagiarism_urkund
 * @copyright 2017 Dan Marsden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class lagiarism_urkund_setup_form
 *
 * @package   plagiarism_urkund
 * @copyright 2017 Dan Marsden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plagiarism_urkund_setup_form extends moodleform {
    /**
     * Form definition
     */
    public function definition () {
        $mform =& $this->_form;
        $mform->addElement('html', get_string('urkundexplain', 'plagiarism_urkund'));
        $mform->addElement('checkbox', 'enabled', get_string('useurkund', 'plagiarism_urkund'));

        $mform->addElement('text', 'api', get_string('urkund_api', 'plagiarism_urkund'));
        $mform->addHelpButton('api', 'urkund_api', 'plagiarism_urkund');
        $mform->addRule('api', null, 'required', null, 'client');
        $mform->setDefault('api', 'https://secure.urkund.com');
        $mform->setType('api', PARAM_URL);

        $mform->addElement('text', 'username', get_string('urkund_username', 'plagiarism_urkund'));
        $mform->addHelpButton('username', 'urkund_username', 'plagiarism_urkund');
        $mform->addRule('username', null, 'required', null, 'client');
        $mform->setType('username', PARAM_TEXT);

        $mform->addElement('passwordunmask', 'password', get_string('urkund_password', 'plagiarism_urkund'));
        $mform->addHelpButton('password', 'urkund_password', 'plagiarism_urkund');
        $mform->addRule('password', null, 'required', null, 'client');
        $mform->setType('password', PARAM_TEXT);

        $mform->addElement('text', 'unitid', get_string('urkund_unitid', 'plagiarism_urkund'));
        $mform->addHelpButton('unitid', 'urkund_unitid', 'plagiarism_urkund');
        $mform->setType('unitid', PARAM_INT);

        $mform->addElement('text', 'lang', get_string('urkund_lang', 'plagiarism_urkund'));
        $mform->addHelpButton('lang', 'urkund_lang', 'plagiarism_urkund');
        $mform->addRule('lang', null, 'required', null, 'client');
        $mform->setDefault('lang', 'en-US');
        $mform->setType('lang', PARAM_TEXT);

        $mform->addElement('textarea', 'student_disclosure', get_string('studentdisclosure', 'plagiarism_urkund'),
            'wrap="virtual" rows="6" cols="50"');
        $mform->addHelpButton('student_disclosure', 'studentdisclosure', 'plagiarism_urkund');
        $mform->setDefault('student_disclosure', get_string('studentdisclosuredefault', 'plagiarism_urkund'));
        $mform->setType('student_disclosure', PARAM_TEXT);

        $mform->addElement('checkbox', 'optout', get_string('urkund_enableoptout', 'plagiarism_urkund'),
            '<br/>'.get_string('urkund_enableoptoutdesc', 'plagiarism_urkund'));
        $mform->setDefault('optout', true);

        $mform->addElement('checkbox', 'hidefilename', get_string('urkund_hidefilename', 'plagiarism_urkund'),
            '<br/>'.get_string('urkund_hidefilenamedesc', 'plagiarism_urkund'));
        $mform->setDefault('hidefilename', false);

        $mform->addElement('text', 'charcount', get_string('charcount', 'plagiarism_urkund'));
        $mform->addHelpButton('charcount', 'charcount', 'plagiarism_urkund');
        $mform->setType('charcount', PARAM_INT);
        $mform->addRule('charcount', null, 'required', null, 'client');
        $mform->setDefault('charcount', '450');

        $mform->addElement('checkbox', 'userpref', get_string('urkund_userpref', 'plagiarism_urkund'),
            '<br/>'.get_string('urkund_userprefdesc', 'plagiarism_urkund'));
        $mform->setDefault('userpref', true);

        $mods = core_component::get_plugin_list('mod');
        foreach ($mods as $mod => $modname) {
            if (plugin_supports('mod', $mod, FEATURE_PLAGIARISM)) {
                $modstring = 'enable_mod_' . $mod;
                $mform->addElement('checkbox', $modstring, get_string('urkund_enableplugin', 'plagiarism_urkund', $mod));
                if ($modname == 'assign') {
                    $mform->setDefault($modstring, 1);
                }
            }
        }
        if (!empty($mods['assign'])) {
            $mform->addElement('header', 'plagiarismdesc_assign', get_string('assignsettings', 'plagiarism_urkund'));
            $mform->addElement('checkbox', 'assignforcesubmissionstatement',
                get_string('assignforcesubmissionstatement', 'plagiarism_urkund').
                '<br/>'.get_string('assignforcesubmissionstatement_desc', 'plagiarism_urkund'));
        }

        $this->add_action_buttons(true);
    }
}
