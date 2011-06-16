<?php

require_once($CFG->dirroot.'/lib/formslib.php');

class urkund_setup_form extends moodleform {

/// Define the form
    function definition () {
        global $CFG;

        $mform =& $this->_form;
        $choices = array('No','Yes');
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

        $mform->addElement('textarea', 'urkund_student_disclosure', get_string('studentdisclosure','plagiarism_urkund'),'wrap="virtual" rows="6" cols="50"');
        $mform->addHelpButton('urkund_student_disclosure', 'studentdisclosure', 'plagiarism_urkund');
        $mform->setDefault('urkund_student_disclosure', get_string('studentdisclosuredefault','plagiarism_urkund'));

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