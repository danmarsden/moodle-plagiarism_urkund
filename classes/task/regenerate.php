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
 * Resubmit all files for a cm.
 *
 * @package    plagiarism_urkund
 * @author     Dan Marsden
 * @copyright  2020 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace plagiarism_urkund\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Adhoc class, used to Resubmit all files for a cm.
 *
 * @package    plagiarism_urkund
 * @author     Dan Marsden
 * @copyright  2020 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class regenerate extends \core\task\adhoc_task {

    /**
     * Send out messages.
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/plagiarism/urkund/lib.php');
        $data = $this->get_custom_data();

        $now = time();

        // Get plagiarism settings for module.
        $plagiarismvalues = $DB->get_records_menu('plagiarism_urkund_config', array('cm' => $data->cmid), '', 'name, value');

        // Rests all plagiarism files that match cmid, have not exceeded their max attempts and not already in the queue.
        $sql = "UPDATE {plagiarism_urkund_files}
               SET statuscode = :newstatus, revision = revision + 1
             WHERE cm = :cmid AND attempt <= :maxa AND statuscode <> :pending AND statuscode <> :waiting";
        $params = array('newstatus' => URKUND_STATUSCODE_PENDING,
            'cmid' => $data->cmid,
            'maxa' => PLAGIARISM_URKUND_MAXATTEMPTS,
            'pending' => URKUND_STATUSCODE_PENDING,
            'waiting' => URKUND_STATUSCODE_ACCEPTED);
        $DB->execute($sql, $params);

        if (isset($plagiarismvalues['timeresubmitted'])) {
            $DB->set_field('plagiarism_urkund_config', 'value', $now,
                array('name' => 'timeresubmitted', 'cm' => $data->cmid));
        } else {
            $newvalue = new \stdClass();
            $newvalue->cm = $data->cmid;
            $newvalue->name = 'timeresubmitted';
            $newvalue->value = $now;
            $DB->insert_record('plagiarism_urkund_config', $newvalue);
        }
    }
}
