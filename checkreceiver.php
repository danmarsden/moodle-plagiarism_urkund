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
// Now make call to check reciever address is valid.

$urkund = new plagiarism_plugin_urkund();
$plagiarismsettings = $urkund->get_settings();
$url = URKUND_INTEGRATION_SERVICE .'/receivers'.'/'. trim($receiver);;

$headers = array('Accept-Language: '.$plagiarismsettings['urkund_lang']);

$allowedstatus = array(URKUND_STATUSCODE_PROCESSED,
    URKUND_STATUSCODE_NOT_FOUND,
    URKUND_STATUSCODE_BAD_REQUEST,
    URKUND_STATUSCODE_GONE);

// Use Moodle curl wrapper to send file.
$c = new curl(array('proxy' => true));
$c->setopt(array());
$c->setopt(array('CURLOPT_RETURNTRANSFER' => 1,
    'CURLOPT_HTTPAUTH' => CURLAUTH_BASIC,
    'CURLOPT_USERPWD' => $plagiarismsettings['urkund_username'].":".$plagiarismsettings['urkund_password']));

$c->setHeader($headers);
$response = $c->get($url);
$httpstatus = $c->info['http_code'];
if (!empty($httpstatus)) {
    if (in_array($httpstatus, $allowedstatus)) {
        if ($httpstatus == URKUND_STATUSCODE_PROCESSED) {
            // Valid address found, return true.
            echo json_encode(true);
            exit;
        } else {
            echo json_encode($httpstatus);
            exit;
        }
    }
}
echo json_encode(false);