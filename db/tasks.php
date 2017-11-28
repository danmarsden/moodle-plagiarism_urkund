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
 * URKUND tasks.
 *
 * @package    plagiarism_urkund
 * @author     Dan Marsden http://danmarsden.com
 * @copyright  2017 Dan Marsden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'plagiarism_urkund\task\get_scores',
        'blocking' => 0,
        'minute' => '*/5',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'plagiarism_urkund\task\send_files',
        'blocking' => 0,
        'minute' => '*/5',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'plagiarism_urkund\task\update_allowed_filetypes',
        'blocking' => 0,
        'minute' => '5',
        'hour' => '3',
        'day' => '*',
        'dayofweek' => '0',
        'month' => '*'
    ),
    array(
        'classname' => 'plagiarism_urkund\task\resubmit_on_close',
        'blocking' => 0,
        'minute' => '10',
        'hour' => '2',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    )
);
