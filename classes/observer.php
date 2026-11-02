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
 * Event observers: record which limited sections a user has opened.
 *
 * @package    availability_sectionlimit
 * @copyright  2026 Ahmad Fadli
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_sectionlimit;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observers.
 */
class observer {

    /**
     * Records a pick when a user does the work in an activity that carries the condition
     * (starts a quiz attempt, creates a forum discussion or posts a forum reply).
     *
     * Merely opening the page of an activity never records a pick.
     *
     * @param \core\event\base $event Quiz attempt started, forum discussion created or forum post created
     */
    public static function activity_done(\core\event\base $event) {
        global $DB;

        $userid = (int)$event->userid;
        $courseid = (int)$event->courseid;
        if (!$userid || !$courseid || $event->contextlevel != CONTEXT_MODULE || isguestuser($userid)) {
            return;
        }

        // Do not record anything while someone is logged in as the user.
        $data = $event->get_data();
        if (!empty($data['realuserid'])) {
            return;
        }

        $cm = $DB->get_record('course_modules', ['id' => $event->contextinstanceid, 'course' => $courseid],
            'id, section, availability');
        if (!$cm || !$cm->section) {
            return;
        }
        $sectionid = (int)$cm->section;

        // Only activities that carry this condition (or sit in a section that does) count.
        // Free activities such as URL resources never record a pick.
        $limited = strpos((string)$cm->availability, '"sectionlimit"') !== false;
        if (!$limited) {
            $secavailability = (string)$DB->get_field('course_sections', 'availability', ['id' => $sectionid]);
            $limited = strpos($secavailability, '"sectionlimit"') !== false;
        }
        if (!$limited) {
            return;
        }

        // Teachers and admins bypass availability, so they must not consume picks.
        $context = \context_course::instance($courseid);
        if (has_capability('moodle/course:ignoreavailabilityrestrictions', $context, $userid)) {
            return;
        }

        if ($DB->record_exists('availability_sectionlimit', ['sectionid' => $sectionid, 'userid' => $userid])) {
            return;
        }

        try {
            $DB->insert_record('availability_sectionlimit', (object)[
                'courseid' => $courseid,
                'sectionid' => $sectionid,
                'userid' => $userid,
                'timecreated' => time(),
            ]);
        } catch (\dml_exception $e) {
            // A parallel request already recorded the same pick (unique index): nothing to do.
            debugging('availability_sectionlimit: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        condition::clear_cache();
    }

    /**
     * Removes picks of a deleted section.
     *
     * @param \core\event\course_section_deleted $event
     */
    public static function section_deleted(\core\event\course_section_deleted $event) {
        global $DB;
        $DB->delete_records('availability_sectionlimit', ['sectionid' => $event->objectid]);
        condition::clear_cache();
    }

    /**
     * Removes picks of a deleted course.
     *
     * @param \core\event\course_deleted $event
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        global $DB;
        $DB->delete_records('availability_sectionlimit', ['courseid' => $event->objectid]);
        condition::clear_cache();
    }

    /**
     * Removes picks of a deleted user.
     *
     * @param \core\event\user_deleted $event
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        global $DB;
        $DB->delete_records('availability_sectionlimit', ['userid' => $event->objectid]);
        condition::clear_cache();
    }
}
