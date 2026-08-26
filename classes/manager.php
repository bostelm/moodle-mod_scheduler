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

namespace mod_scheduler;

use cm_info;
use context_module;
use stdClass;

/**
 * Class manager for scheduler activity
 *
 * @package   mod_scheduler
 * @copyright 2025 Luca Bösch <luca.boesch@bfh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /** Module name. */
    public const MODULE = 'scheduler';

    /** @var context_module the current context. */
    private $context;

    /** @var stdClass $course record. */
    private $course;

    /** @var \moodle_database the database instance. */
    private \moodle_database $db;

    /**
     * Class constructor.
     *
     * @param cm_info $cm course module info object
     * @param stdClass $instance activity instance object.
     */
    public function __construct(
        /** @var cm_info $cm the given course module info */
        private cm_info $cm,
        /** @var stdClass $instance activity instance object */
        private stdClass $instance
    ) {
        $this->context = context_module::instance($cm->id);
        $this->db = \core\di::get(\moodle_database::class);
        $this->course = $cm->get_course();
    }

    /**
     * Create a manager instance from an instance record.
     *
     * @param stdClass $instance an activity record
     * @return manager
     */
    public static function create_from_instance(stdClass $instance): self {
        $cm = get_coursemodule_from_instance(self::MODULE, $instance->id);
        // Ensure that $this->cm is a cm_info object.
        $cm = cm_info::create($cm);
        return new self($cm, $instance);
    }

    /**
     * Create a manager instance from a course_modules record.
     *
     * @param stdClass|cm_info $cm an activity record
     * @return manager
     */
    public static function create_from_coursemodule(stdClass|cm_info $cm): self {
        // Ensure that $this->cm is a cm_info object.
        $cm = cm_info::create($cm);
        $db = \core\di::get(\moodle_database::class);
        $instance = $db->get_record(self::MODULE, ['id' => $cm->instance], '*', MUST_EXIST);
        return new self($cm, $instance);
    }

    /**
     * Return the current context.
     *
     * @return context_module
     */
    public function get_context(): context_module {
        return $this->context;
    }

    /**
     * Return the current instance.
     *
     * @return stdClass the instance record
     */
    public function get_instance(): stdClass {
        return $this->instance;
    }

    /**
     * Return the current cm_info.
     *
     * @return cm_info the course module
     */
    public function get_coursemodule(): cm_info {
        return $this->cm;
    }

    /**
     * Return the current count of users who have booked slots in this scheduler module, that the current user can see.
     *
     * @param int[] $groupids the group identifiers to filter by, empty array means no filtering
     * @param int|null $optionid the option ID to filter by, or null to count all answers
     * @return int the number of answers that the user can see
     */
    public function count_all_users_answered(
        array $groupids = [],
        ?int $optionid = null,
    ): int {
        if (!has_capability('mod/scheduler:manage', $this->context)) {
            return 0;
        }

        $tableprefix = empty($groupids) ? '' : 'of.';
        $select = $tableprefix . 'scheduler = :schedulerid';
        $params = [
            'schedulerid' => $this->instance->id,
        ];
        if ($optionid) {
            $select .= ' AND ' . $tableprefix . 'optionid = :optionid ';
            $params['optionid'] = $optionid;
        }

        if (empty($groupids)) {
            // No groups filtering, count all users answered.
            return $this->db->count_records_select('scheduler_file', $select, $params, 'COUNT(DISTINCT userid)');
        }

        // Groups filtering is applied.
        [$gsql, $gparams] = $this->db->get_in_or_equal($groupids, SQL_PARAMS_NAMED);
        $query = "SELECT COUNT(DISTINCT of.userid)
                FROM {scheduler_file} of, {groups_members} gm
               WHERE $select
                     AND (gm.groupid $gsql OR gm.groupid = 0)
                     AND of.userid = gm.userid";
        return $this->db->count_records_sql($query, $params + $gparams);
    }

    /**
     * Check if the current user has booked a slot in this scheduler.
     *
     * @return bool true if the user has booked, false otherwise
     */
    public function has_answered(): bool {
        global $USER;
        $conditions = ['scheduler' => $this->instance->id, 'userid' => $USER->id];
        return $this->db->record_exists('scheduler_file', $conditions);
    }
}
