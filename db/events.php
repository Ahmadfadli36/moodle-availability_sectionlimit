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
 * Event observers for the section limit availability condition.
 *
 * @package    availability_sectionlimit
 * @copyright  2026 Ahmad Fadli
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    // A section is picked when the student really works in it: starts a quiz attempt or posts in a forum.
    [
        'eventname' => '\mod_quiz\event\attempt_started',
        'callback' => '\availability_sectionlimit\observer::activity_done',
    ],
    [
        'eventname' => '\mod_forum\event\discussion_created',
        'callback' => '\availability_sectionlimit\observer::activity_done',
    ],
    [
        'eventname' => '\mod_forum\event\post_created',
        'callback' => '\availability_sectionlimit\observer::activity_done',
    ],
    [
        'eventname' => '\core\event\course_section_deleted',
        'callback' => '\availability_sectionlimit\observer::section_deleted',
    ],
    [
        'eventname' => '\core\event\course_deleted',
        'callback' => '\availability_sectionlimit\observer::course_deleted',
    ],
    [
        'eventname' => '\core\event\user_deleted',
        'callback' => '\availability_sectionlimit\observer::user_deleted',
    ],
];
