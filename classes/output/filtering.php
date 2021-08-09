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
 * plagiarism_urkund filtering class
 *
 * @package    plagiarism_urkund
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_urkund\output;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/user/filters/lib.php');

/**
 * Class filtering based on core user_filtering class, with extra filters.
 *
 * @package    plagiarism_urkund
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filtering extends \user_filtering {

    /**
     * Adds handling for custom fieldnames.
     * @param string $fieldname
     * @param boolean $advanced
     * @return object filter
     */
    public function get_field($fieldname, $advanced) {
        global $DB;
        if ($fieldname == 'filename') {
            return new \user_filter_text('filename', get_string('filename', 'report_allbackups'), $advanced, 'filename');
        }
        if ($fieldname == 'timesubmitted') {
            return new \user_filter_date('timesubmitted', get_string('date'), $advanced, 't.timesubmitted');
        }
        if ($fieldname == 'statuscode') {
            return new \user_filter_simpleselect('statuscode', get_string('status', 'plagiarism_urkund'),
                $advanced, 't.statuscode', plagiarism_urkund_statuscodes());
        }
        if ($fieldname == 'errorcode') {
            return new \user_filter_simpleselect('errorcode', get_string('errorcode', 'plagiarism_urkund'),
                $advanced, 't.errorcode', plagiarism_urkund_error_codes());
        }
        if ($fieldname == 'course') {
            return new \user_filter_text('course', get_string('courseshortname', 'plagiarism_urkund'),
                $advanced, 'c.shortname');
        }
        return parent::get_field($fieldname, $advanced);
    }

}
