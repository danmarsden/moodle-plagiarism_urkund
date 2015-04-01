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

defined('MOODLE_INTERNAL') || die();


class restore_plagiarism_urkund_plugin extends restore_plagiarism_plugin {
    /**
     * Returns the paths to be handled by the plugin at question level.
     */
    protected function define_course_plugin_structure() {
        $paths = array();

        // Add own format stuff.
        $elename = 'urkundconfig';
        $elepath = $this->get_pathfor('urkund_configs/urkund_config'); // We used get_recommended_name() so this works.
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths; // And we return the interesting paths.
    }

    public function process_urkundconfig($data) {
        $data = (object)$data;

        set_config($this->task->get_courseid(), $data->value, $data->plugin);
    }

    /**
     * Returns the paths to be handled by the plugin at module level.
     */
    protected function define_module_plugin_structure() {
        $paths = array();

        // Add own format stuff.
        $elename = 'urkundconfigmod';
        $elepath = $this->get_pathfor('urkund_configs/urkund_config'); // We used get_recommended_name() so this works.
        $paths[] = new restore_path_element($elename, $elepath);

        $elename = 'urkundfiles';
        $elepath = $this->get_pathfor('/urkund_files/urkund_file'); // We used get_recommended_name() so this works.
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths; // And we return the interesting paths.

    }

    public function process_urkundconfigmod($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->cm = $this->task->get_moduleid();

        $DB->insert_record('plagiarism_urkund_config', $data);
    }

    public function process_urkundfiles($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->cm = $this->task->get_moduleid();
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('plagiarism_urkund_files', $data);
    }
}