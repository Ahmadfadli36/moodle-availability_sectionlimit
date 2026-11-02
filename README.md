# availability_sectionlimit

Moodle availability condition: each student may open at most **N** of the sections that carry this restriction. A section is *picked* the first time the student really works in it: **starts a quiz attempt** or **posts in a forum** (new discussion or reply). Only opening a page never picks a section. After N picks, every other restricted section stays locked for that student.

- Teachers/admins (anyone with `moodle/course:ignoreavailabilityrestrictions`) are never limited and never consume picks.
- Opening an activity of an already picked section is always allowed.
- Sections without the restriction (for example section 0, announcements) are not counted.
- Teachers can release picks per student, per section, or for everyone (see "Releasing picks").

Built for Moodle 5.0+ (uses the YUI form pattern of the 5.0 availability API).

## Install

Copy the folder `sectionlimit` to:

| Moodle version | Path |
|---|---|
| 5.0 | `<moodle>/availability/condition/sectionlimit` |
| 5.1 and later | `<moodle>/public/availability/condition/sectionlimit` |

Then run the upgrade (Site administration > Notifications, or `php admin/cli/upgrade.php`) and purge caches (`php admin/cli/purge_caches.php`).
Alternatively use "Install plugins from ZIP" with plugin type *Availability restriction* (`availability`).

## Two ways to use it

- **On sections:** the whole section is locked once the limit is reached. Opening any activity in the section picks it.
- **On activities (for example only forums and quizzes):** add the restriction to those activities instead. Other activities, such as URL resources, stay free and never count. Doing the work in a restricted activity (quiz attempt, forum post) picks its section, and every restricted activity of a picked section stays open.

Do not mix both in one course. The CLI option `--modules=forum,quiz` of `apply.php` does the second way in bulk.

## Use in one course (UI)

Turn editing on > edit a section > Restrict access > Add restriction > **Section limit** > set the number (default 4).
Repeat for each section students can choose from. Do not add it to section 0.

## Use in many courses (CLI)

```bash
# See what would change
php availability/condition/sectionlimit/cli/apply.php --categoryid=5 --max=4 --dry-run
# Apply to every course in a category (and subcategories)
php availability/condition/sectionlimit/cli/apply.php --categoryid=5 --max=4
# Apply to specific courses, hiding locked sections instead of greying them out
php availability/condition/sectionlimit/cli/apply.php --courseids=12,13 --max=4 --hide
# Remove again
php availability/condition/sectionlimit/cli/apply.php --categoryid=5 --remove
```

By default section 0 is skipped (`--skip-sections=0`). Sections that already have other restrictions are kept; the limit is added on top (AND). Run the CLI as the web server user (for example `sudo -u www-data php ...`) so cache and file permissions stay correct.

## Releasing picks

Teachers (capability `availability/sectionlimit:manage`, granted to Teacher and Manager by default) open:

`/availability/condition/sectionlimit/reset.php?id=<courseid>`

From there a single section, all sections of one student, or all students' sections can be released. Released sections can be chosen again.

## Notes

- The plugin reacts to three events only: `mod_quiz\event\attempt_started`, `mod_forum\event\discussion_created` and `mod_forum\event\post_created`. Other activity types (for example assignments) do not pick a section yet.
- Two simultaneous requests from the same student into two different new sections can, in rare cases, both succeed and exceed the limit by one.
- Deleting a user, section or course removes the related picks.
