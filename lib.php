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
 * Library functions for the section limit availability condition.
 *
 * @package    availability_sectionlimit
 * @copyright  2026 Ahmad Fadli
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Adds a link to the page where teachers release picked sections to the course "More" menu.
 *
 * The link only shows for users with the manage capability, and only in courses where the
 * section limit is used on a section or on an activity.
 *
 * @param navigation_node $navigation The course administration navigation node
 * @param stdClass $course The course
 * @param context $context The course context
 */
function availability_sectionlimit_extend_navigation_course(navigation_node $navigation, stdClass $course,
        context $context) {
    global $DB;

    if (!has_capability('availability/sectionlimit:manage', $context)) {
        return;
    }

    $params = ['course' => $course->id, 'marker' => '%"sectionlimit"%'];
    $used = $DB->record_exists_select('course_sections', 'course = :course AND ' .
            $DB->sql_like('availability', ':marker'), $params)
        || $DB->record_exists_select('course_modules', 'course = :course AND ' .
            $DB->sql_like('availability', ':marker'), $params);
    if (!$used) {
        return;
    }

    $url = new moodle_url('/availability/condition/sectionlimit/reset.php', ['id' => $course->id]);
    $navigation->add(
        get_string('resetpagetitle', 'availability_sectionlimit'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'availability_sectionlimit',
        new pix_icon('i/unlock', '')
    );
}
