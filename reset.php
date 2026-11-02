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
 * Lets teachers see and release the sections each student has picked.
 *
 * URL: /availability/condition/sectionlimit/reset.php?id=<courseid>
 *
 * @package    availability_sectionlimit
 * @copyright  2026 Ahmad Fadli
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

$courseid = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$userid = optional_param('userid', 0, PARAM_INT);
$sectionid = optional_param('sectionid', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$course = get_course($courseid);
require_login($course);
$context = context_course::instance($course->id);
require_capability('availability/sectionlimit:manage', $context);

$url = new moodle_url('/availability/condition/sectionlimit/reset.php', ['id' => $course->id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('resetpagetitle', 'availability_sectionlimit'));
$PAGE->set_heading($course->fullname);

$table = 'availability_sectionlimit';

if ($action) {
    require_sesskey();

    if ($action === 'removepick' && $userid && $sectionid) {
        $DB->delete_records($table, ['courseid' => $course->id, 'userid' => $userid, 'sectionid' => $sectionid]);
    } else if ($action === 'resetuser' && $userid) {
        $DB->delete_records($table, ['courseid' => $course->id, 'userid' => $userid]);
    } else if ($action === 'resetall') {
        if (!$confirm) {
            echo $OUTPUT->header();
            echo $OUTPUT->confirm(
                get_string('confirmresetall', 'availability_sectionlimit'),
                new moodle_url($url, ['action' => 'resetall', 'confirm' => 1]),
                $url
            );
            echo $OUTPUT->footer();
            die;
        }
        $DB->delete_records($table, ['courseid' => $course->id]);
    }

    \availability_sectionlimit\condition::clear_cache();
    redirect($url, get_string('resetdone', 'availability_sectionlimit'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

// Section names, keyed by section id.
$names = [];
foreach (get_fast_modinfo($course)->get_section_info_all() as $section) {
    $names[$section->id] = get_section_name($course, $section);
}

$sql = "SELECT p.id, p.userid, p.sectionid, p.timecreated,
               u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename
          FROM {availability_sectionlimit} p
          JOIN {user} u ON u.id = p.userid
         WHERE p.courseid = :courseid
      ORDER BY u.lastname, u.firstname, p.userid, p.timecreated";
$records = $DB->get_records_sql($sql, ['courseid' => $course->id]);

$byuser = [];
foreach ($records as $record) {
    if (!isset($byuser[$record->userid])) {
        $byuser[$record->userid] = ['name' => fullname($record), 'picks' => []];
    }
    $byuser[$record->userid]['picks'][] = $record;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('resetpagetitle', 'availability_sectionlimit'));

if (!$byuser) {
    echo $OUTPUT->notification(get_string('nopicks', 'availability_sectionlimit'), 'info');
    echo $OUTPUT->footer();
    die;
}

$out = new html_table();
$out->attributes['class'] = 'generaltable';
$out->head = [
    get_string('student', 'availability_sectionlimit'),
    get_string('sectionspicked', 'availability_sectionlimit'),
    get_string('countpicked', 'availability_sectionlimit'),
    get_string('actions', 'availability_sectionlimit'),
];

foreach ($byuser as $uid => $info) {
    $picks = [];
    foreach ($info['picks'] as $pick) {
        $label = $names[$pick->sectionid] ?? ('#' . $pick->sectionid);
        $button = $OUTPUT->single_button(
            new moodle_url($url, ['action' => 'removepick', 'userid' => $uid, 'sectionid' => $pick->sectionid]),
            get_string('removepick', 'availability_sectionlimit'),
            'post',
            ['class' => 'd-inline-block ms-1']
        );
        $picks[] = html_writer::div($label . ' ' . $button, 'mb-1');
    }
    $out->data[] = [
        s($info['name']),
        implode('', $picks),
        count($info['picks']),
        $OUTPUT->single_button(
            new moodle_url($url, ['action' => 'resetuser', 'userid' => $uid]),
            get_string('resetuser', 'availability_sectionlimit'),
            'post'
        ),
    ];
}

echo html_writer::table($out);

echo $OUTPUT->single_button(
    new moodle_url($url, ['action' => 'resetall']),
    get_string('resetall', 'availability_sectionlimit'),
    'get'
);

echo $OUTPUT->footer();
