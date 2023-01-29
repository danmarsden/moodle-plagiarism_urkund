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
 * URKUND task - Fetch queued portfolios from Mahara.
 *
 * @package   plagiarism_urkund
 * @author    David Balch <david.balch@catalyst-eu.net>
 * @copyright 2021 onwards Catalyst IT Europe <http://catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_urkund\task;

defined('MOODLE_INTERNAL') || die();

/**
 * fetch_mahara class,  fetches queued portfolios from Mahara.
 *
 * @package   plagiarism_urkund
 * @author    David Balch <david.balch@catalyst-eu.net>
 * @copyright 2021 onwards Catalyst IT Europe <http://catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fetch_mahara extends \core\task\scheduled_task {
    /**
     * Returns the name of this task.
     */
    public function get_name() {
        // Shown in admin screens.
        return get_string('fetchmahara', 'plagiarism_urkund');
    }

    /**
     * Execute task.
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot.'/plagiarism/urkund/lib.php');
        plagiarism_urkund_fetch_mahara();
    }
}
