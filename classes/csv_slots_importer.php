<?php
// This file is part of a 3rd party created module for Moodle - http://moodle.org/
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
 * Slots importer from CSV.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler;
defined('MOODLE_INTERNAL') || die();

use core_user;
use csv_import_reader;
use DateTime;
use mod_scheduler\local\iterator\csv_reader_iterator;
use mod_scheduler\local\iterator\map_iterator;
use mod_scheduler\model\scheduler;
use mod_scheduler\permission\scheduler_permissions;

/**
 * Slots importer from CSV.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class csv_slots_importer implements \IteratorAggregate {

    /** @var csv_import_reader The CSV import reader. */
    protected $cir;
    /** @var object The permissions. */
    protected $permissions;
    /** @var object[]|null List of availble teachers. */
    protected $availableteachers = null;
    /** @var object[] List of teachers resolved by value. */
    protected $resolvedteachers = [];
    /** @var scheduler The scheduler. */
    protected $scheduler;

    /**
     * Constructor.
     *
     * @param scheduler $scheduler The scheduler.
     * @param scheduler_permissions $permissions The user permissions.
     * @param csv_import_reader $cir The CSV reader with content loaded.
     */
    public function __construct(scheduler $scheduler, scheduler_permissions $permissions, csv_import_reader $cir) {
        $this->scheduler = $scheduler;
        $this->permissions = $permissions;
        $this->cir = $cir;
    }

    /**
     * Convert the line to slot data.
     *
     * @param array $line The line indexed by column keys.
     * @return object
     */
    protected function convert_line($line) {

        // Mandatory columns.
        try {
            $date = new DateTime($line['date']);
        } catch (\Exception $e) {
            $date = new DateTime('@0');
        }
        try {
            $time = new DateTime($line['time']);
        } catch (\Exception $e) {
            $date = new DateTime('@0');
        }
        $duration = (int) $line['duration'];

        // Optional columns.
        $maxstudents = !empty($line['maxstudents']) ? (int) $line['maxstudents'] : 0;
        $location = $line['location'] ?: null;
        $teacher = $line['teacher'] ?: null;
        $comment = $line['comment'] ?: null;
        $displayfrom = !empty($line['displayfrom']) ? new DateTime($line['displayfrom']): new DateTime();

        // Massaging the data.
        $date->setTime($time->format('H'), $time->format('i'), 0, 0);
        $displayfrom->setTime(0, 0, 0, 0);

        return (object) [
            'starttime' => $date,
            'duration' => $duration,
            'exclusivity' => max(0, $maxstudents),
            'teacher' => $this->resolve_teacher($teacher),
            'appointmentlocation' => $location ?? '',
            'hideuntil' => $displayfrom,
            'notes' => $comment ?? '',
            'notesformat' => FORMAT_MARKDOWN
        ];
    }

    /**
     * Get the available teachers IDs.
     *
     * @return object[]
     */
    protected function get_allowed_teachers() {
        if (!isset($this->availableteachers)) {
            $teachers = $this->scheduler->get_available_teachers();
            $this->availableteachers = $teachers;
        }
        return $this->availableteachers;
    }

    /**
     * Get the iterator.
     *
     * @return \Iterator
     */
    public function getIterator() {
        return new map_iterator(
            new csv_reader_iterator($this->cir),
            function($line, $lineno) {
                return $this->process_line($line, $lineno);
            }
        );
    }

    /**
     * Make a slot from a processed line.
     *
     * @param object $info Result from {@link self::process_line}.
     * @return slot
     */
    public function make_slot_from_processed_line($info) {
        if (!empty($info->errors)) {
            throw new \coding_exception('We should not create a slot from an invalid line.');
        }

        $data = $info->data;

        $slot = $this->scheduler->create_slot();
        $slot->starttime = $data->starttime->getTimestamp();
        $slot->duration = $data->duration;
        $slot->teacherid = $data->teacher->id;
        $slot->appointmentlocation = $data->appointmentlocation;
        $slot->notes = $data->notes;
        $slot->notesformat = $data->notesformat;
        $slot->exclusivity = $data->exclusivity;
        $slot->hideuntil = $data->hideuntil->getTimestamp();
        $slot->timemodified = time();

        return $slot;
    }

    /**
     * Processes a line.
     *
     * This returned structured information about the line, its data
     * and the errors that we may have encountered while processing it.
     *
     * @param array $line Raw line from CSV.
     * @param int $lineno Line number.
     * @return object
     */
    protected function process_line($line, $lineno) {
        $line = array_combine($this->cir->get_columns(), $line);
        $data = $this->convert_line($line);
        $errors = $this->validate_data($data);
        return (object) [
            'lineno' => $lineno,
            'line' => $line,
            'data' => $data,
            'errors' => $errors,
        ];
    }

    /**
     * Resolve a teacher from the value passed.
     *
     * @param string|null $value Typically a user's username.
     * @return object|null Or null when unresolved.
     */
    protected function resolve_teacher($value) {
        if (!isset($this->resolvedteachers[$value])) {

            if (empty($value)) {
                $userid = $this->permissions->get_userid();
                $value = "#" . $userid;
                $user = core_user::get_user($userid);

            } else {
                $user = core_user::get_user_by_username($value);
            }
            if (!empty($user) && !core_user::is_real_user($user->id)) {
                $user = false;
            }
            $this->resolvedteachers[$value] = $user;
        }

        return empty($this->resolvedteachers[$value]) ? null : $this->resolvedteachers[$value];
    }

    /**
     * Validate the CSV.
     *
     * @return string[] Returns an array of errors, if any.
     */
    public function validate_csv() {
        $errors = [];
        $csvloaderror = $this->cir->get_error();

        if (!is_null($csvloaderror)) {
            $errors['csvloaderror'] = $csvloaderror;
            return $errors;
        }

        $columns = $this->cir->get_columns();
        $columns = $columns ?: [];
        $requiredcols = ['date', 'time', 'duration'];
        $diff = array_diff($requiredcols, $columns);
        if (!empty($diff)) {
            $errors['csvloaderror'] = get_string('csvmissingcolumns', 'mod_scheduler', implode(', ', $diff));
            return $errors;
        }

        return [];
    }

    /**
     * Validates the slot data.
     *
     * @param object $data Data from {@link self::convert_line}.
     * @return string[]
     */
    protected function validate_data($data) {
        $errors = [];

        if ($data->starttime->getTimestamp() < time()) {
            $errors[] = get_string('invalidorpastdate', 'mod_scheduler');
        }

        $maxduration = 24 * 60; // Copied from slotforms.php.
        if ($data->duration < 1 || $data->duration > $maxduration) {
            $errors[] = get_string('durationrange', 'mod_scheduler', ['min' => 1, 'max' => $maxduration]);
        }

        if (empty($data->teacher)) {
            $errors[] = get_string('couldnotresolveteacher', 'mod_scheduler');

        } else {
            $cansetothers = $this->permissions->can_edit_all_slots() && $this->permissions->can_schedule_slot_to_other_teachers();
            if (!$cansetothers && $data->teacher->id !== $this->permissions->get_userid()) {
                $errors[] = get_string('cannotscheduleslotforothers', 'mod_scheduler');
            }
            if (!array_key_exists($data->teacher->id, $this->get_allowed_teachers())) {
                $errors[] = get_string('invalidteacher', 'mod_scheduler');
            }
        }


        return $errors;
    }

}
