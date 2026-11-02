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
 * Privacy provider for the section limit availability condition.
 *
 * @package    availability_sectionlimit
 * @copyright  2026 Ahmad Fadli
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_sectionlimit\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\userlist_provider {

    /**
     * Describes the stored data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('availability_sectionlimit', [
            'userid' => 'privacy:metadata:availability_sectionlimit:userid',
            'courseid' => 'privacy:metadata:availability_sectionlimit:courseid',
            'sectionid' => 'privacy:metadata:availability_sectionlimit:sectionid',
            'timecreated' => 'privacy:metadata:availability_sectionlimit:timecreated',
        ], 'privacy:metadata:availability_sectionlimit');
        return $collection;
    }

    /**
     * Gets the contexts containing data for a user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {availability_sectionlimit} p ON p.courseid = ctx.instanceid
                 WHERE ctx.contextlevel = :level AND p.userid = :userid";
        $contextlist->add_from_sql($sql, ['level' => CONTEXT_COURSE, 'userid' => $userid]);
        return $contextlist;
    }

    /**
     * Gets the users with data in a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_course) {
            return;
        }
        $userlist->add_from_sql('userid',
            "SELECT userid FROM {availability_sectionlimit} WHERE courseid = :courseid",
            ['courseid' => $context->instanceid]);
    }

    /**
     * Exports a user's data.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (!$contextlist->count()) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_course) {
                continue;
            }
            $records = $DB->get_records('availability_sectionlimit',
                ['courseid' => $context->instanceid, 'userid' => $userid], 'timecreated');
            if (!$records) {
                continue;
            }
            $sections = [];
            foreach ($records as $record) {
                $sections[] = (object)[
                    'sectionid' => $record->sectionid,
                    'timecreated' => transform::datetime($record->timecreated),
                ];
            }
            writer::with_context($context)->export_data(
                [get_string('pluginname', 'availability_sectionlimit')],
                (object)['sections' => $sections]
            );
        }
    }

    /**
     * Deletes all data in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context instanceof \context_course) {
            $DB->delete_records('availability_sectionlimit', ['courseid' => $context->instanceid]);
        }
    }

    /**
     * Deletes a user's data in the approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_course) {
                $DB->delete_records('availability_sectionlimit',
                    ['courseid' => $context->instanceid, 'userid' => $userid]);
            }
        }
    }

    /**
     * Deletes the data of several users in one context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if (!$context instanceof \context_course) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params['courseid'] = $context->instanceid;
        $DB->delete_records_select('availability_sectionlimit', "courseid = :courseid AND userid $insql", $params);
    }
}
