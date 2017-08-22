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
 * Custom rule form validation, no hooks into the validation function of activity editing page.
 *
 * @package   plagiarism_urkund
 * @author    Dan Marsden <dan@danmarsden.com>
 * @copyright 2014 onwards Dan Marsden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class plagiarism_urkund_validatereceiver
 *
 * @package   plagiarism_urkund
 * @copyright 2017 Dan Marsden
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plagiarism_urkund_validatereceiver extends HTML_QuickForm_Rule {
    /**
     * Validate receiver address.
     *
     * @param string $receiver - the receiver address passed.
     * @param array $options
     */
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