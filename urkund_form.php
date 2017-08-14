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
    public function definition () {

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

        $mform->addElement('checkbox', 'urkund_optout', get_string('urkund_enableoptout', 'plagiarism_urkund'),
            '<br/>'.get_string('urkund_enableoptoutdesc', 'plagiarism_urkund'));
        $mform->setDefault('urkund_optout', true);

        $mform->addElement('checkbox', 'urkund_hidefilename', get_string('urkund_hidefilename', 'plagiarism_urkund'),
            '<br/>'.get_string('urkund_hidefilenamedesc', 'plagiarism_urkund'));
        $mform->setDefault('urkund_hidefilename', false);

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
                if ($modname == 'assign') {
                    $mform->setDefault($modstring, 1);
                }
            }
        }

        $this->add_action_buttons(true);
    }
}

class urkund_defaults_form extends moodleform {

    // Define the form.
    public function definition () {
        $mform =& $this->_form;

        $supportedmodules = array('assign', 'forum', 'workshop');

        foreach ($supportedmodules as $sm) {
            $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
            $tiioptions = array(0 => get_string("never"), 1 => get_string("always"),
                2 => get_string("showwhenclosed", "plagiarism_urkund"));
            $urkunddraftoptions = array(
                PLAGIARISM_URKUND_DRAFTSUBMIT_IMMEDIATE => get_string("submitondraft", "plagiarism_urkund"),
                PLAGIARISM_URKUND_DRAFTSUBMIT_FINAL => get_string("submitonfinal", "plagiarism_urkund")
            );

            $mform->addElement('header', 'plagiarismdesc_'.$sm, get_string('urkunddefaults_'.$sm, 'plagiarism_urkund'));
            $mform->addElement('select', 'use_urkund_'.$sm, get_string("useurkund", "plagiarism_urkund"), $ynoptions);
            $mform->addElement('select', 'urkund_show_student_score_'.$sm,
                get_string("urkund_show_student_score", "plagiarism_urkund"), $tiioptions);
            $mform->addHelpButton('urkund_show_student_score_'.$sm, 'urkund_show_student_score', 'plagiarism_urkund');
            $mform->addElement('select', 'urkund_show_student_report_'.$sm,
                get_string("urkund_show_student_report", "plagiarism_urkund"), $tiioptions);
            $mform->addHelpButton('urkund_show_student_report_'.$sm, 'urkund_show_student_report', 'plagiarism_urkund');
            if ($sm == 'assign') {
                $mform->addElement('select', 'urkund_draft_submit_'.$sm,
                    get_string("urkund_draft_submit", "plagiarism_urkund"), $urkunddraftoptions);
            }
            $contentoptions = array(PLAGIARISM_URKUND_RESTRICTCONTENTNO => get_string('restrictcontentno', 'plagiarism_urkund'),
                PLAGIARISM_URKUND_RESTRICTCONTENTFILES => get_string('restrictcontentfiles', 'plagiarism_urkund'),
                PLAGIARISM_URKUND_RESTRICTCONTENTTEXT => get_string('restrictcontenttext', 'plagiarism_urkund'));
            $mform->addElement('select', 'urkund_restrictcontent_'.$sm, get_string('restrictcontent', 'plagiarism_urkund'), $contentoptions);
            $mform->addHelpButton('urkund_restrictcontent_'.$sm, 'restrictcontent', 'plagiarism_urkund');
            $mform->setType('urkund_restrictcontent_'.$sm, PARAM_INT);
            $mform->addElement('text', 'urkund_receiver_'.$sm, get_string("urkund_receiver", "plagiarism_urkund"), array('size' => 40));
            $mform->addHelpButton('urkund_receiver_'.$sm, 'urkund_receiver', 'plagiarism_urkund');
            $mform->setType('urkund_receiver_'.$sm, PARAM_EMAIL);
            $mform->addElement('select', 'urkund_studentemail_'.$sm, get_string("urkund_studentemail", "plagiarism_urkund"), $ynoptions);
            $mform->addHelpButton('urkund_studentemail_'.$sm, 'urkund_studentemail', 'plagiarism_urkund');
            $mform->setType('urkund_studentemail_'.$sm, PARAM_INT);

            $filetypes = urkund_default_allowed_file_types(true);

            $supportedfiles = array();
            foreach ($filetypes as $ext => $mime) {
                $supportedfiles[$ext] = $ext;
            }
            $mform->addElement('select', 'urkund_allowallfile_'.$sm, get_string('allowallsupportedfiles', 'plagiarism_urkund'), $ynoptions);
            $mform->addHelpButton('urkund_allowallfile_'.$sm, 'allowallsupportedfiles', 'plagiarism_urkund');
            $mform->setType('urkund_allowallfile_'.$sm, PARAM_INT);
            $mform->addElement('select', 'urkund_selectfiletypes_'.$sm, get_string('restrictfiles', 'plagiarism_urkund'),
                $supportedfiles, array('multiple' => true));
            $mform->setType('urkund_selectfiletypes_'.$sm, PARAM_TAGLIST);

            $items = array();
            $aliases = array(
                'use_urkund' => 'useurkund',
                'urkund_allowallfile' => 'allowallsupportedfiles',
                'urkund_selectfiletypes' => 'restrictfiles',
                'urkund_restrictcontent' => 'restrictcontent',
            );
            foreach (plagiarism_plugin_urkund::config_options() as $setting) {
                $key = isset($aliases[$setting]) ? $aliases[$setting] : $setting;
                $items[$setting] = get_string($key, 'plagiarism_urkund');
            }
            $mform->addElement('select', 'urkund_advanceditems_'.$sm, get_string('urkund_advanceditems', 'plagiarism_urkund'), $items);
            $mform->getElement('urkund_advanceditems_'.$sm)->setMultiple(true);
            $mform->addHelpButton('urkund_advanceditems_'.$sm, 'urkund_advanceditems', 'plagiarism_urkund');
            $mform->setType('urkund_advanceditems_'.$sm, PARAM_TAGLIST);
        }

        $this->add_action_buttons(true);
    }
}
