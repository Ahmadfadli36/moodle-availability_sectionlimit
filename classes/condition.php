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
 * Section limit condition.
 *
 * A section carrying this condition is "picked" by a student the first time the student opens any
 * activity in it (see observer). Once the student has picked the maximum number of sections in the
 * course, every section with this condition that the student has not picked stays locked.
 *
 * @package    availability_sectionlimit
 * @copyright  2026 Ahmad Fadli
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_sectionlimit;

use core_availability\info;
use core_availability\info_module;
use core_availability\info_section;

defined('MOODLE_INTERNAL') || die();

/**
 * Section limit condition.
 */
class condition extends \core_availability\condition {

    /** @var int Maximum number of limited sections a user may open in one course. */
    protected $max;

    /** @var array[] Per-request cache of picked section ids, keyed "courseid_userid". */
    protected static $cache = [];

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data
     */
    public function __construct($structure) {
        if (!isset($structure->max) || !is_numeric($structure->max) || (int)$structure->max < 1) {
            throw new \coding_exception('Missing or invalid ->max for sectionlimit condition');
        }
        $this->max = (int)$structure->max;
    }

    public function save() {
        return (object)['type' => 'sectionlimit', 'max' => $this->max];
    }

    public function is_available($not, info $info, $grabthelot, $userid) {
        $allow = true;
        $sectionid = $this->get_section_id($info);
        if ($sectionid) {
            $picks = self::get_user_picks($info->get_course()->id, $userid);
            // Already picked sections stay open; new ones open only while below the limit.
            $allow = in_array($sectionid, $picks) || count($picks) < $this->max;
        }
        if ($not) {
            $allow = !$allow;
        }
        return $allow;
    }

    public function get_description($full, $not, info $info) {
        global $USER;

        if ($full) {
            return get_string($not ? 'requires_max_not_full' : 'requires_max_full',
                'availability_sectionlimit', $this->max);
        }

        $names = $this->get_pick_names($info, $USER->id);
        $a = (object)['max' => $this->max, 'names' => implode(', ', $names)];
        if ($not) {
            return get_string('requires_max_not', 'availability_sectionlimit', $a);
        }
        return get_string($names ? 'requires_max' : 'requires_max_none', 'availability_sectionlimit', $a);
    }

    protected function get_debug_string() {
        return 'max ' . $this->max;
    }

    public function is_applied_to_user_lists() {
        return false;
    }

    /**
     * Returns the id of the section this condition is evaluated for (0 for non-section items).
     *
     * @param info $info Item being evaluated
     * @return int Section id, or 0
     */
    protected function get_section_id(info $info) {
        if ($info instanceof info_section) {
            return (int)$info->get_section()->id;
        }
        if ($info instanceof info_module) {
            // For an activity, the section is the one the activity lives in.
            return (int)$info->get_course_module()->section;
        }
        return 0;
    }

    /**
     * Gets the ids of the limited sections a user has picked in a course.
     *
     * Only sections that currently carry a sectionlimit condition are counted, so removing the
     * condition from a section frees the slot it used.
     *
     * @param int $courseid Course id
     * @param int $userid User id
     * @return int[] Section ids
     */
    public static function get_user_picks($courseid, $userid) {
        global $DB;

        $key = $courseid . '_' . $userid;
        if (!array_key_exists($key, self::$cache)) {
            // A pick counts while the section itself, or any activity in it, still carries the condition.
            $sql = "SELECT p.sectionid
                      FROM {availability_sectionlimit} p
                      JOIN {course_sections} s ON s.id = p.sectionid
                     WHERE p.courseid = :courseid AND p.userid = :userid
                           AND (" . $DB->sql_like('s.availability', ':marker1') . "
                                OR EXISTS (SELECT 1
                                             FROM {course_modules} cm
                                            WHERE cm.section = s.id
                                                  AND " . $DB->sql_like('cm.availability', ':marker2') . "))";
            $params = ['courseid' => $courseid, 'userid' => $userid,
                'marker1' => '%"sectionlimit"%', 'marker2' => '%"sectionlimit"%'];
            self::$cache[$key] = array_map('intval', $DB->get_fieldset_sql($sql, $params));
        }
        return self::$cache[$key];
    }

    /**
     * Clears the per-request cache (called whenever picks change).
     */
    public static function clear_cache() {
        self::$cache = [];
    }

    /**
     * Gets display names of the sections a user has picked.
     *
     * @param info $info Item being evaluated
     * @param int $userid User id
     * @return string[] Section names
     */
    protected function get_pick_names(info $info, $userid) {
        global $DB;

        $course = $info->get_course();
        $picks = self::get_user_picks($course->id, $userid);
        if (!$picks) {
            return [];
        }
        // Read the sections directly instead of building modinfo, which could recurse into availability.
        $sections = $DB->get_records_list('course_sections', 'id', $picks, 'section');
        $names = [];
        foreach ($sections as $section) {
            $names[] = get_section_name($course, $section);
        }
        return $names;
    }
}
