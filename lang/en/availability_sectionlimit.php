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
 * Language strings for the section limit availability condition.
 *
 * @package    availability_sectionlimit
 * @copyright  2026 Ahmad Fadli
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Restriction by section limit';
$string['title'] = 'Section limit';
$string['description'] = 'Limit how many of the sections with this restriction each student can open in the course. A section is picked when the student starts a quiz attempt or posts in a forum in it.';
$string['label_max'] = 'Maximum number of sections';
$string['error_max'] = 'The maximum number of sections must be a whole number of at least 1.';

$string['requires_max'] = 'you have chosen fewer than {$a->max} sections (chosen so far: {$a->names})';
$string['requires_max_none'] = 'you have chosen fewer than {$a->max} sections';
$string['requires_max_not'] = 'you have already chosen {$a->max} sections';
$string['requires_max_full'] = 'the student has chosen fewer than {$a} sections';
$string['requires_max_not_full'] = 'the student has already chosen {$a} sections';

$string['sectionlimit:manage'] = 'View and release the sections students have picked';

$string['resetpagetitle'] = 'Sections picked by students';
$string['nopicks'] = 'No student has opened a limited section in this course yet.';
$string['student'] = 'Student';
$string['sectionspicked'] = 'Sections picked';
$string['countpicked'] = 'Total';
$string['actions'] = 'Actions';
$string['removepick'] = 'Release';
$string['resetuser'] = 'Release all';
$string['resetall'] = 'Release everyone\'s sections';
$string['confirmresetall'] = 'Release the picked sections of every student in this course? Students will be able to choose their sections again.';
$string['resetdone'] = 'Picked sections updated.';

$string['privacy:metadata:availability_sectionlimit'] = 'Records which limited sections a user has opened in a course.';
$string['privacy:metadata:availability_sectionlimit:userid'] = 'The user who opened the section.';
$string['privacy:metadata:availability_sectionlimit:courseid'] = 'The course containing the section.';
$string['privacy:metadata:availability_sectionlimit:sectionid'] = 'The section that was opened.';
$string['privacy:metadata:availability_sectionlimit:timecreated'] = 'When the section was first opened.';
