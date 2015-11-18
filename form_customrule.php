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
 * form_customrule.php - allows custom rule form validation
 * We use this because the plagiarism api doesn't hook into the validation function of activity editing page.
 *
 * @package   plagiarism_urkund
 * @author    Dan Marsden <dan@danmarsden.com>
 * @copyright 2014 onwards Dan Marsden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

// Get global class.
global $CFG;

require_once($CFG->libdir. '/pear/HTML/QuickForm/Rule.php');
require_once($CFG->dirroot.'/plagiarism/urkund/lib.php');

// Returns false if email already exists, true if it doesn't.
class urkundvalidatereceiver extends HTML_QuickForm_Rule {

    // Var $receiver will be the receiver address passed.
    public function validate($receiver, $options = null) {
        $urkund = new plagiarism_plugin_urkund();
        $valid = $urkund->validate_receiver($receiver);
        if ($valid === true) {
            return true;
        } else {
            return false;
        }
    }
}