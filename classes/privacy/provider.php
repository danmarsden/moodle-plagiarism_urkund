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
 * Privacy Subsystem implementation for plagiarism_urkund
 *
 * @package   plagiarism_urkund
 * @author    Dan Marsden <dan@danmarsden.com>
 * @copyright 2019 Dan Marsden http://danmarsden.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace plagiarism_urkund\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\{writer, helper, contextlist, approved_contextlist, approved_userlist, userlist};

/**
 * Privacy subsystem for plagiarism_urkund.
 *
 * @copyright 2019 Dan Marsden http://danmarsden.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_plagiarism\privacy\plagiarism_provider {

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $collection a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->link_subsystem('core_files', 'privacy:metadata:core_files');

        $collection->add_database_table(
            'plagiarism_urkund_files',
            [
                'userid' => 'privacy:metadata:plagiarism_urkund_files:userid',
                'similarityscore' => 'privacy:metadata:plagiarism_urkund_files:similarityscore',
                'timesubmitted' => 'privacy:metadata:plagiarism_urkund_files:lastmodified',
            ], 'privacy:metadata:plagiarism_urkund_files'
        );

        $collection->link_external_location('plagiarism_urkund', [
            'email' => 'privacy:metadata:plagiarism_urkund_client:email',
            'filename' => 'privacy:metadata:plagiarism_urkund_client:filename',
            'file' => 'privacy:metadata:plagiarism_urkund_client:file',
        ], 'privacy:metadata:plagiarism_urkund_client');

        return $collection;
    }
    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid($userid) : contextlist {
        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'userid'       => $userid
        ];
        $sql = "SELECT DISTINCT c.id
                FROM {context} c
                JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                JOIN {plagiarism_urkund_files} puf ON cm.id = puf.cm
                WHERE puf.userid = :userid";
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);
    }
    /**
     * Export all plagiarism data from each plagiarism plugin for the specified userid and context.
     *
     * @param   int         $userid The user to export.
     * @param   \context    $context The context to export.
     * @param   array       $subcontext The subcontext within the context to export this information to.
     * @param   array       $linkarray The weird and wonderful link array used to display information for a specific item
     */
    public static function export_plagiarism_user_data(int $userid, \context $context, array $subcontext, array $linkarray) {
        global $DB;
        if (empty($userid)) {
            return;
        }
        $user = $DB->get_record('user', array('id' => $userid));
        $params = ['userid' => $user->id, 'cm' => $context->instanceid];
        $sql = "SELECT id,
                cm,
                similarityscore,
                attempt,
                timesubmitted
                  FROM {plagiarism_urkund_files}
                 WHERE userid = :userid AND cm = :cm";
        $submissions = $DB->get_records_sql($sql, $params);
        foreach ($submissions as $submission) {
            $context = \context_module::instance($submission->cm);
            $contextdata = helper::get_context_data($context, $user);
            // Merge with module data and write it.
            $contextdata = (object)array_merge((array)$contextdata, (array)$submission);
            writer::with_context($context)->export_data([], $contextdata);
            // Write generic module intro files.
            helper::export_context_files($context, $user);
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_plagiarism_for_context(\context $context) {
        global $DB;
        if (empty($context)) {
            return;
        }
        if (!$context instanceof \context_module) {
            return;
        }
        // Delete all submissions.
        $DB->delete_records('plagiarism_urkund_files', ['cm' => $context->instanceid]);
    }

    /**
     * Delete all user information for the provided user and context.
     *
     * @param  int      $userid    The user to delete
     * @param  \context $context   The context to refine the deletion.
     */
    public static function delete_plagiarism_for_user(int $userid, \context $context) {
        global $DB;

        $DB->delete_records('plagiarism_urkund_files', ['userid' => $userid,  'cm' => $context->instanceid]);
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $sql = "SELECT userid
                FROM {plagiarism_urkund_files}
                WHERE cm = :cm";
        $params = ['cm' => $context->instanceid];
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }
        // Prepare SQL to gather all completed IDs.
        $userids = $userlist->get_userids();
        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $inparams['cm'] = $context->instanceid;

        $DB->delete_records_select(
            'plagiarism_urkund_files',
            "cm = :cm AND userid $insql",
            $inparams
        );
    }
}
