Appointment Scheduler for Moodle 2.x

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
    http://docs.moodle.org/25/en/Scheduler_module

(Note that the information there may refer to a previous version of the module.)


=== Installation instructions ===

Place the code of the module into the mod/scheduler directory of your Moodle
directory root. That is, the present file should be located at:
mod/scheduler/README.txt

For further installation instructions please see:
    http://docs.moodle.org/en/Installing_contributed_modules_or_plugins

This module is intended for Moodle 2.5 and above.


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

The code of this module is rather old, much of it predates even Moodle 1.9.
It has partially been adapted to the new APIs. The following aspects have been migrated,
that is, malfunction in this respect should be considered a bug:

* Gradebook integration
* Moodle 2 backup
* New rich text editor and file API (activity intro and slot add/edit forms) 
* Localization / language packs

The module does not use any deprecated API as of Moodle 2.5.

