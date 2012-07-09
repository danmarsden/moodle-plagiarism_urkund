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

$handlers = array (

/*
 * Event Handlers
 */
    'assessable_file_uploaded' => array (
        'handlerfile'      => '/plagiarism/urkund/lib.php',
        'handlerfunction'  => 'event_file_uploaded',
        'schedule'         => 'cron'
    ),
    'assessable_files_done' => array (
        'handlerfile'      => '/plagiarism/urkund/lib.php',
        'handlerfunction'  => 'event_files_done',
        'schedule'         => 'cron'
    ),
    'assessable_content_uploaded' => array (
        'handlerfile'      => '/plagiarism/urkund/lib.php',
        'handlerfunction'  => 'event_content_uploaded',
        'schedule'         => 'cron'
    ),
    'mod_created' => array (
        'handlerfile'      => '/plagiarism/urkund/lib.php',
        'handlerfunction'  => 'event_mod_created',
        'schedule'         => 'cron'
    ),
    'mod_updated' => array (
        'handlerfile'      => '/plagiarism/urkund/lib.php',
        'handlerfunction'  => 'event_mod_updated',
        'schedule'         => 'cron'
    ),
    'mod_deleted' => array (
        'handlerfile'      => '/plagiarism/urkund/lib.php',
        'handlerfunction'  => 'event_mod_deleted',
        'schedule'         => 'cron'
    ),

);
