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
 * checkreceiver.php - Checks to make sure passed receiver address is valid.
 *
 * @since 2.0
 * @package    plagiarism_urkund
 * @subpackage plagiarism
 * @author     Dan Marsden <dan@danmarsden.com>
 * @copyright  2014 Dan Marsden http://danmarsden.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once($CFG->libdir.'/plagiarismlib.php');
require_once($CFG->dirroot.'/plagiarism/urkund/lib.php');
require_once($CFG->libdir.'/filelib.php');

$receiver = required_param('ur', PARAM_TEXT);
$contextinstance = required_param('c', PARAM_INT);
$coursecontext = context_course::instance($contextinstance);

require_login();
require_capability('plagiarism/urkund:enable', $coursecontext);

require_sesskey();
// Now make call to check receiver address is valid.

$urkund = new plagiarism_plugin_urkund();
echo json_encode($urkund->validate_receiver($receiver));