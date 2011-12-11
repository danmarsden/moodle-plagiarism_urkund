<?php

defined('MOODLE_INTERNAL') || die();


class backup_plagiarism_urkund_plugin extends backup_plagiarism_plugin {
    function define_module_plugin_structure() {
        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define the virtual plugin element without conditions as the global class checks already.
        $plugin = $this->get_plugin_element();

        // Create one standard named plugin element (the visible container)
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        // connect the visible container ASAP
        $plugin->add_child($pluginwrapper);

        $urkundconfigs = new backup_nested_element('urkund_configs');
        $urkundconfig = new backup_nested_element('urkund_config', array('id'), array('name', 'value'));
        $pluginwrapper->add_child($urkundconfigs);
        $urkundconfigs->add_child($urkundconfig);
        $urkundconfig->set_source_table('plagiarism_urkund_config', array('cm' => backup::VAR_PARENTID));

        //now information about files to module
        $urkundfiles = new backup_nested_element('urkund_files');
        $urkundfile = new backup_nested_element('urkund_file', array('id'),
                            array('userid', 'identifier','filename','reporturl','optout','statuscode','similarityscore','errorresponse','timesubmitted'));

        $pluginwrapper->add_child($urkundfiles);
        $urkundfiles->add_child($urkundfile);
        if ($userinfo) {
            $urkundfile->set_source_table('plagiarism_urkund_files', array('cm' => backup::VAR_PARENTID));
        }
        return $plugin;
    }

    function define_course_plugin_structure() {
        // Define the virtual plugin element without conditions as the global class checks already.
        $plugin = $this->get_plugin_element();

        // Create one standard named plugin element (the visible container)
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        // connect the visible container ASAP
        $plugin->add_child($pluginwrapper);
        //save id from urkund course
        $urkundconfigs = new backup_nested_element('urkund_configs');
        $urkundconfig = new backup_nested_element('urkund_config', array('id'), array('plugin', 'name', 'value'));
        $pluginwrapper->add_child($urkundconfigs);
        $urkundconfigs->add_child($urkundconfig);
        $urkundconfig->set_source_table('config_plugins', array('name'=> backup::VAR_PARENTID, 'plugin' => backup_helper::is_sqlparam('plagiarism_urkund_course')));
        return $plugin;
    }
}