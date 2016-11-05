Appointment Scheduler for Moodle

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details:

http://www.gnu.org/copyleft/gpl.html


=== Description ===

The Scheduler module helps you to schedule appointments with your students. 
Teachers specify time slots for meetings, students then choose one of them on Moodle.
Teacher in turn can record the outcome of the meeting - and optionally a grade - 
within the scheduler.

For further information, please see:
    http://docs.moodle.org/31/en/Scheduler_module

(Note that the information there may refer to a previous version of the module.)


=== Installation instructions ===

Place the code of the module into the mod/scheduler directory of your Moodle
directory root. That is, the present file should be located at:
mod/scheduler/README.txt

For further installation instructions please see:
    http://docs.moodle.org/en/Installing_contributed_modules_or_plugins

This module is intended for Moodle 3.1 and above.


=== Authors ===

Current maintainer:
 Henning Bostelmann, University of York <henning.bostelmann@york.ac.uk>

Based on previous work by:

* Gustav Delius <gustav.delius@york.ac.uk> (until Moodle 1.7)
* Valery Fremaux <valery.fremaux@club-internet.fr> (Moodle 1.8 - Moodle 1.9)

With further contributions taken from:

* Vivek Arora (independent migration of the module to 2.0)
* Andriy Semenets (Russian and Ukrainian localization)
* Gaël Mifsud (French localization)
* Various authors of the core Moodle code


=== Release notes ===

--- Version 3.1 ---

Intended for Moodle 3.1 and later.

New features / improvements:

An additional "confidential note" field is supplied for appointments;
the contents can be read by teachers only.

Slot notes and appointment notes can now contain attachments.

Students can now be allowed to see existing bookings of other students.
See https://docs.moodle.org/31/en/Scheduler_Module_capabilities#Student_side

Feature changes:

Sending of invitations and reminders is no longer handled via a "mailto" link
but rather via a webform, using Moodle's messaging system.

The conflict detection feature (when creating new slots) has been reworked slightly.
See https://docs.moodle.org/31/en/Scheduler:_Conflicts

Refactoring / API changes:

All email-related features now use the Messaging API.

Appointment reminders and deletion of past unused slots are now handled via
the Scheduled Tasks API.

The new Search API is supported for the activity description only.

--- Version 2.9 ---

Intended for Moodle 2.9 and later.

New features / improvements:

The export screen now allows users to choose the format of the output file,
as well as the data fields to include in the export. File format may
slightly differ from previous versions.

Improved gradebook integration: Grades overridden in the gradebook will now 
show up as such in the scheduler.

Lists of students to be scheduled now take availability conditions
(groups and groupings) into account.

Feature changes:

The handling of "group mode" in Scheduler has changed. The feature of "booking
entire groups into a slot" is now controlled by a setting "Booking in groups" 
at the level of each scheduler. The setting "Group mode" in "Common module 
settings" is now used in line with usual Moodle conventions - setting it to,
e.g., "Separate groups" will mean that students can only book slots with 
teachers in the same group. The old "Group mode" settings are automatically
migrated to "Booking in groups" and the "Group mode" set to "None".
If you have used group scheduling in previous versions, please check your data
after migration.

The student view has been redesigned. Bookable appointments are now displayed 
in pages of 25, and student select a slot by clicking a button "Book slot"
rather then selecting with a radio button and clicking "Save choice".  
 
For using the Overview screen outside the current scheduler, e.g., for displaying
all slots of a user across the site, users will now need extra permissions;
see CONTRIB-5750 for details.

Refactoring / API changes:

Config settings have been migrated to the config_plugins table.

--- Version 2.7 ---

Intended for Moodle 2.7 and later. 

New features:

Students can now be allowed to book several slots at a time.
"Volatile slots" replaced with "guard time" - students cannot change their booking
for slots closer than this time to the current time.

Feature changes:

"Notes" field will now be shown to students at booking time.

Refactoring / API changes:

Major refactoring of teacher view (slot list), student view (booking screen),
teacher view of individual appointments, as well as of the backend.
Security enhancements (sessionid parameter now used throughout).
Adapted to changes in core API and to the new logging/event system (Event 2).

--- Version 2.5 ---

Intended for Moodle 2.5 and later. 

Module adapted to API changes Moodle core.
"Add slot" and "Edit slot" forms refactored, now based on Moodle Forms.
Language packs migrated to AMOS, removed from plugin codebase.

--- Version 2.3 ---

Intended for Moodle 2.3 and later; no major functional changes, but API adapted and minor enhancements.

--- Version 2.0 --- 

No major functional changes over 1.9; bug fixes and API migration only. Requires 1.9 for database upgrades.  


=== Technical notes ===

The code of this module is rather old, some of it still predates even Moodle 1.9.
It has now largely, but not completely, been adapted to the new APIs.
The following aspects have been migrated, that is, malfunction in this respect 
should be considered a bug:

* Gradebook integration
* Moodle 2 backup
* New rich text editor and file API 
* Localization / language packs
* Logging / event system
* Scheduler tasks API
* Messaging API

The module does not use any deprecated API as of Moodle 3.1.

