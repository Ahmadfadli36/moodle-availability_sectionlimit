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
 * Indonesian language strings for the section limit availability condition.
 *
 * @package    availability_sectionlimit
 * @copyright  2026 Ahmad Fadli
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Pembatasan jumlah section';
$string['title'] = 'Batas section';
$string['description'] = 'Batasi berapa banyak section berpembatasan ini yang boleh dibuka tiap mahasiswa di course. Section dianggap terpilih saat mahasiswa memulai percobaan kuis atau mengirim postingan forum di dalamnya.';
$string['label_max'] = 'Jumlah maksimal section';
$string['error_max'] = 'Jumlah maksimal section harus berupa bilangan bulat minimal 1.';

$string['requires_max'] = 'Anda telah memilih kurang dari {$a->max} section (sudah dipilih: {$a->names})';
$string['requires_max_none'] = 'Anda telah memilih kurang dari {$a->max} section';
$string['requires_max_not'] = 'Anda sudah memilih {$a->max} section';
$string['requires_max_full'] = 'mahasiswa telah memilih kurang dari {$a} section';
$string['requires_max_not_full'] = 'mahasiswa sudah memilih {$a} section';

$string['sectionlimit:manage'] = 'Melihat dan melepas section yang dipilih mahasiswa';

$string['resetpagetitle'] = 'Section yang dipilih mahasiswa';
$string['nopicks'] = 'Belum ada mahasiswa yang membuka section berbatas di course ini.';
$string['student'] = 'Mahasiswa';
$string['sectionspicked'] = 'Section terpilih';
$string['countpicked'] = 'Jumlah';
$string['actions'] = 'Aksi';
$string['removepick'] = 'Lepas';
$string['resetuser'] = 'Lepas semua';
$string['resetall'] = 'Lepas section semua mahasiswa';
$string['confirmresetall'] = 'Lepas semua section terpilih dari seluruh mahasiswa di course ini? Mahasiswa dapat memilih section lagi dari awal.';
$string['resetdone'] = 'Section terpilih sudah diperbarui.';

$string['privacy:metadata:availability_sectionlimit'] = 'Mencatat section berbatas yang telah dibuka pengguna di sebuah course.';
$string['privacy:metadata:availability_sectionlimit:userid'] = 'Pengguna yang membuka section.';
$string['privacy:metadata:availability_sectionlimit:courseid'] = 'Course yang berisi section.';
$string['privacy:metadata:availability_sectionlimit:sectionid'] = 'Section yang dibuka.';
$string['privacy:metadata:availability_sectionlimit:timecreated'] = 'Waktu section pertama kali dibuka.';
