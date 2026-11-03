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
 * CLI script: add (or remove) the section limit restriction on sections or on chosen activity types in many courses.
 *
 * @package    availability_sectionlimit
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');

[$options, $unrecognized] = cli_get_params([
    'help' => false,
    'courseids' => '',
    'categoryid' => '',
    'all' => false,
    'max' => 4,
    'modules' => '',
    'skip-sections' => '0',
    'skip-section-names' => '',
    'exclude-name' => '',
    'exclude-courseids' => '',
    'hide' => false,
    'remove' => false,
    'dry-run' => false,
], ['h' => 'help']);

if ($unrecognized) {
    cli_error('Unknown option(s): ' . implode(', ', $unrecognized));
}

if ($options['help'] || (!$options['courseids'] && !$options['categoryid'] && !$options['all'])) {
    echo "Apply the section limit restriction to many courses.

Choose the courses (one of):
  --courseids=1,2,3     Comma separated course ids
  --categoryid=5        Every course in this category and its subcategories
  --all                 Every course on the site

Choose where the restriction goes:
  (default)             On the sections themselves: the whole section is locked
  --modules=forum,quiz  On these activity types only: other activities (e.g. URL) stay free.
                        Opening a forum or quiz of a section picks that section.

Options:
  --max=4               Maximum sections per student (default 4)
  --skip-sections=0     Comma separated section numbers to leave alone (default 0, the general section).
                        Example: --skip-sections=0,8,16 to also skip sections 8 and 16.
  --skip-section-names=UTS,UAS
                        Also skip every section whose name is one of these (case-insensitive), whatever its
                        number. Use this when exam sections sit at different positions in different courses.
  --exclude-name='Bahasa Inggris'
                        Leave out every course whose full or short name contains this text
                        (comma separated list, case-insensitive). Excluded courses are listed as EXCLUDED.
  --exclude-courseids=5,6
                        Leave out these course ids
  --hide                Hide locked items entirely instead of showing them greyed out
  --remove              Remove the condition instead of adding it (use the same --modules as when adding)
  --dry-run             Show what would change without saving
  -h, --help            Print this help

Examples:
  php availability/condition/sectionlimit/cli/apply.php --courseids=314 --modules=forum,quiz --max=4 --dry-run
  php availability/condition/sectionlimit/cli/apply.php --courseids=314 --modules=forum,quiz --skip-sections=0,8,16
  php availability/condition/sectionlimit/cli/apply.php --courseids=314 --remove
  php availability/condition/sectionlimit/cli/apply.php --courseids=314 --modules=forum,quiz --remove
  php availability/condition/sectionlimit/cli/apply.php --all --modules=forum,quiz --skip-section-names=UTS,UAS --exclude-name='Bahasa Inggris' --dry-run
";
    exit(0);
}

$max = (int)$options['max'];
if (!$options['remove'] && $max < 1) {
    cli_error('--max must be a whole number of at least 1');
}
$skip = array_filter(array_map('trim', explode(',', (string)$options['skip-sections'])), 'strlen');
$skip = array_map('intval', $skip);
$modulenames = array_values(array_filter(array_map('trim', explode(',', (string)$options['modules'])), 'strlen'));
$dryrun = (bool)$options['dry-run'];
$skipnames = array_map(function($name) {
    return core_text::strtolower(trim($name));
}, array_filter(explode(',', (string)$options['skip-section-names']), 'strlen'));
$excludenames = array_values(array_filter(array_map('trim', explode(',', (string)$options['exclude-name'])), 'strlen'));
$excludeids = array_map('intval', array_filter(explode(',', (string)$options['exclude-courseids']), 'strlen'));

// Work out the courses.
if ($options['courseids']) {
    $courseids = array_filter(array_map('intval', explode(',', $options['courseids'])));
} else if ($options['categoryid']) {
    $category = $DB->get_record('course_categories', ['id' => (int)$options['categoryid']], '*', MUST_EXIST);
    $like = $DB->sql_like('cc.path', ':path');
    $courseids = $DB->get_fieldset_sql(
        "SELECT c.id FROM {course} c JOIN {course_categories} cc ON cc.id = c.category
          WHERE c.id <> :siteid AND (cc.id = :catid OR $like)",
        ['siteid' => SITEID, 'catid' => $category->id, 'path' => $category->path . '/%']
    );
} else {
    $courseids = $DB->get_fieldset_select('course', 'id', 'id <> ?', [SITEID]);
}

/**
 * Whether a decoded availability node contains a sectionlimit condition.
 *
 * @param stdClass $node
 * @return bool
 */
function availability_sectionlimit_has($node) {
    if (isset($node->type)) {
        return $node->type === 'sectionlimit';
    }
    foreach ($node->c ?? [] as $child) {
        if (availability_sectionlimit_has($child)) {
            return true;
        }
    }
    return false;
}

/**
 * Removes every sectionlimit condition from a decoded availability node.
 *
 * @param stdClass $node
 * @return stdClass|null The cleaned node, or null if nothing is left
 */
function availability_sectionlimit_strip($node) {
    if (isset($node->type)) {
        return $node->type === 'sectionlimit' ? null : $node;
    }
    $children = [];
    $showc = [];
    foreach ($node->c ?? [] as $i => $child) {
        $clean = availability_sectionlimit_strip($child);
        if ($clean !== null) {
            $children[] = $clean;
            if (isset($node->showc[$i])) {
                $showc[] = $node->showc[$i];
            }
        }
    }
    if (!$children) {
        return null;
    }
    $node->c = $children;
    if (isset($node->showc)) {
        $node->showc = $showc;
    }
    return $node;
}

/**
 * Adds a sectionlimit condition to an availability JSON string.
 *
 * @param string|null $json Existing availability JSON
 * @param int $max Maximum sections
 * @param bool $show Whether locked items stay visible to students
 * @return string New availability JSON
 */
function availability_sectionlimit_add($json, $max, $show) {
    $condition = (object)['type' => 'sectionlimit', 'max' => $max];

    $root = empty($json) ? null : json_decode($json);
    if ($root === null || !isset($root->op)) {
        return json_encode((object)['op' => '&', 'c' => [$condition], 'showc' => [$show]]);
    }

    if ($root->op !== '&') {
        // Wrap an existing OR / NOT-AND / NOT-OR tree so the limit applies on top of it.
        $root = (object)['op' => '&', 'c' => [$root], 'showc' => [true]];
    }
    $root->c[] = $condition;
    $root->showc = $root->showc ?? array_fill(0, count($root->c) - 1, true);
    $root->showc[] = $show;
    return json_encode($root);
}

$changed = 0;
$total = 0;
$what = $modulenames ? 'activit(y/ies)' : 'section(s)';
foreach ($courseids as $courseid) {
    $course = $DB->get_record('course', ['id' => $courseid]);
    if (!$course) {
        mtrace("Course $courseid: not found, skipped");
        continue;
    }

    // Courses that must be left alone.
    if (in_array((int)$course->id, $excludeids, true)) {
        mtrace(sprintf('Course %d (%s): EXCLUDED (course id)', $course->id, $course->shortname));
        continue;
    }
    $haystack = core_text::strtolower($course->fullname . ' ' . $course->shortname);
    foreach ($excludenames as $needle) {
        if (core_text::strpos($haystack, core_text::strtolower($needle)) !== false) {
            mtrace(sprintf('Course %d (%s): EXCLUDED (name contains "%s") - %s', $course->id, $course->shortname,
                $needle, $course->fullname));
            continue 2;
        }
    }

    // Collect the things that get (or lose) the restriction: sections, or activities of the chosen types.
    $targets = [];
    if ($modulenames) {
        [$insql, $params] = $DB->get_in_or_equal($modulenames, SQL_PARAMS_NAMED);
        $params['course'] = $course->id;
        $sql = "SELECT cm.id, cm.availability, cs.section AS sectionnum, cs.component, cs.name AS sectionname
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                  JOIN {course_sections} cs ON cs.id = cm.section
                 WHERE cm.course = :course AND m.name $insql AND cm.deletioninprogress = 0
              ORDER BY cs.section, cm.id";
        foreach ($DB->get_records_sql($sql, $params) as $row) {
            $targets[] = (object)['table' => 'course_modules', 'id' => $row->id, 'availability' => $row->availability,
                'sectionnum' => (int)$row->sectionnum, 'component' => $row->component,
                'sectionname' => (string)$row->sectionname];
        }
    } else {
        foreach ($DB->get_records('course_sections', ['course' => $course->id], 'section') as $section) {
            $targets[] = (object)['table' => 'course_sections', 'id' => $section->id,
                'availability' => $section->availability, 'sectionnum' => (int)$section->section,
                'component' => $section->component ?? null, 'sectionname' => (string)$section->name];
        }
    }

    $count = 0;
    foreach ($targets as $target) {
        if (in_array($target->sectionnum, $skip, true)) {
            continue;
        }
        if ($skipnames && in_array(core_text::strtolower(trim($target->sectionname)), $skipnames, true)) {
            continue;
        }
        // Skip anything that belongs to a subsection activity (Moodle 4.5+).
        if (!empty($target->component)) {
            continue;
        }
        $total++;

        $root = empty($target->availability) ? null : json_decode($target->availability);
        $has = $root !== null && availability_sectionlimit_has($root);

        if ($options['remove']) {
            if (!$has) {
                continue;
            }
            $clean = availability_sectionlimit_strip($root);
            $new = $clean === null ? null : json_encode($clean);
        } else {
            if ($has) {
                continue;
            }
            $new = availability_sectionlimit_add($target->availability, $max, !$options['hide']);
        }

        $count++;
        if (!$dryrun) {
            $record = (object)['id' => $target->id, 'availability' => $new];
            if ($target->table === 'course_sections') {
                $record->timemodified = time();
            }
            $DB->update_record($target->table, $record);
        }
    }

    if ($count) {
        $changed += $count;
        if (!$dryrun) {
            rebuild_course_cache($course->id, true, true);
        }
    }
    mtrace(sprintf('Course %d (%s): %d %s %s', $course->id, $course->shortname, $count, $what,
        $dryrun ? 'would change' : 'changed'));
}

mtrace(sprintf('%s: %d of %d %s in %d course(s).', $dryrun ? 'Dry run' : 'Done', $changed, $total, $what,
    count($courseids)));