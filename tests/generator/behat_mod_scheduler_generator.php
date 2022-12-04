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
 * Behat data generator for mod_scheduler.
 *
 * @package   mod_scheduler
 * @category  test
 * @copyright 2022 Henning Bostelmann
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_scheduler_generator extends behat_generator_base {

    /**
     * Get a list of the entities that Behat can create using the generator step.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'slots' => [
                'singular' => 'slot',
                'datagenerator' => 'slot',
                'required' => ['scheduler', 'starttime', 'duration', 'teacher'],
                'switchids' => ['scheduler' => 'schedulerid', 'teacher' => 'teacherid', 'student' => 'studentid'],
            ],
        ];
    }

    /**
     * Get the scheduler CMID using an activity idnumber.
     *
     * @param string $idnumber
     * @return int The cmid
     */
    protected function get_scheduler_id(string $idnumber): int {
        return $this->get_activity_id($idnumber);
    }

    /**
     * Get the teacher user ID using a user idnumber.
     *
     * @param string $idnumber
     * @return int The user id
     */
    protected function get_teacher_id(string $idnumber): int {
        return $this->get_user_id($idnumber);
    }

    /**
     * Get the student user ID using a user idnumber.
     *
     * @param string $idnumber
     * @return int The user id
     */
    protected function get_student_id(string $idnumber): int {
        if ($idnumber) {
            return $this->get_user_id($idnumber);
        } else {
            return 0;
        }
    }

}
