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
 * This file contains an event for when an assessment is resubmitted to URKUND.
 *
 * @package    plagiarism_urkund
 * @author     Oliver Redding
 * @copyright  2017 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_urkund\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event for when an assessment is resubmitted to URKUND.
 *
 * @property-read array $other {
 *      Extra information about event properties.
 *
 *    string mode Mode of the report viewed.
 * }
 * @package    plagiarism_urkund
 * @since      Moodle 3.4
 * @author     Oliver Redding
 * @copyright  2017 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assessment_resubmitted extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'plagiarism_urkund_files';
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return 'User with id ' . $this->userid . ' resubmitted all assessments for cmid ' .
            $this->objectid;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('assessmentresubmitted', 'plagiarism_urkund');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        return null;
    }

    /**
     * Get objectid mapping
     *
     * @return array of parameters for object mapping.
     */
    public static function get_objectid_mapping() {
        return base::NOT_MAPPED;
    }
}
