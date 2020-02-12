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
 * Contains class plagiarism_urkund_defaults_form
 *
 * @package   plagiarism_urkund
 * @copyright 2017 Dan Marsden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class plagiarism_urkund_defaults_form
 *
 * @package   plagiarism_urkund
 * @copyright 2017 Dan Marsden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plagiarism_urkund_defaults_form extends moodleform {
    /**
     * Form definition
     */
    public function definition () {
        $mform =& $this->_form;

        $supportedmodules = urkund_supported_modules();

        foreach ($supportedmodules as $sm) {
            $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
            $tiioptions = array(0 => get_string("never"), 1 => get_string("always"),
                2 => get_string("showwhendue", "plagiarism_urkund"),
                3 => get_string("showwhencutoff", "plagiarism_urkund"));
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
            if ($sm == 'assign') {
                $resubmitoptions = array(PLAGIARISM_URKUND_RESUBMITNO => get_string('no'),
                    PLAGIARISM_URKUND_RESUBMITDUEDATE => get_string('resubmitdue', 'plagiarism_urkund'),
                    PLAGIARISM_URKUND_RESUBMITCLOSEDATE => get_string('resubmitclose', 'plagiarism_urkund'));
                $mform->addElement('select', 'urkund_resubmit_on_close_' . $sm,
                    get_string("urkund_resubmit_on_close", "plagiarism_urkund"), $resubmitoptions);
            }
            $contentoptions = array(PLAGIARISM_URKUND_RESTRICTCONTENTNO => get_string('restrictcontentno', 'plagiarism_urkund'),
                PLAGIARISM_URKUND_RESTRICTCONTENTFILES => get_string('restrictcontentfiles', 'plagiarism_urkund'),
                PLAGIARISM_URKUND_RESTRICTCONTENTTEXT => get_string('restrictcontenttext', 'plagiarism_urkund'));
            $mform->addElement('select', 'urkund_restrictcontent_'.$sm,
                get_string('restrictcontent', 'plagiarism_urkund'), $contentoptions);
            $mform->addHelpButton('urkund_restrictcontent_'.$sm, 'restrictcontent', 'plagiarism_urkund');
            $mform->setType('urkund_restrictcontent_'.$sm, PARAM_INT);
            $mform->addElement('text', 'urkund_receiver_'.$sm,
                get_string("urkund_receiver", "plagiarism_urkund"), array('size' => 40));
            $mform->addHelpButton('urkund_receiver_'.$sm, 'urkund_receiver', 'plagiarism_urkund');
            $mform->setType('urkund_receiver_'.$sm, PARAM_EMAIL);
            $mform->addElement('select', 'urkund_studentemail_'.$sm,
                get_string("urkund_studentemail", "plagiarism_urkund"), $ynoptions);
            $mform->addHelpButton('urkund_studentemail_'.$sm, 'urkund_studentemail', 'plagiarism_urkund');
            $mform->setType('urkund_studentemail_'.$sm, PARAM_INT);

            $filetypes = urkund_default_allowed_file_types(true);

            $supportedfiles = array();
            foreach ($filetypes as $ext => $mime) {
                $supportedfiles[$ext] = $ext;
            }
            $mform->addElement('select', 'urkund_allowallfile_'.$sm,
                get_string('allowallsupportedfiles', 'plagiarism_urkund'), $ynoptions);
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
            $mform->addElement('select', 'urkund_advanceditems_'.$sm,
                get_string('urkund_advanceditems', 'plagiarism_urkund'), $items);
            $mform->getElement('urkund_advanceditems_'.$sm)->setMultiple(true);
            $mform->addHelpButton('urkund_advanceditems_'.$sm, 'urkund_advanceditems', 'plagiarism_urkund');
            $mform->setType('urkund_advanceditems_'.$sm, PARAM_TAGLIST);
        }

        $this->add_action_buttons(true);
    }
}
