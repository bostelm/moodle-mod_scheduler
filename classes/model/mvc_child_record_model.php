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
 * A model mirroring one datebase record which as a "parent-child" relationship to a record in another table.
 *
 * @package    mod_scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\model;

/**
 * A model mirroring one datebase record which as a "parent-child" relationship to a record in another table.
 *
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mvc_child_record_model extends mvc_record_model {

    /**
     * @var mvc_record_model the parent record
     */
    private $parentrec;

    /**
     * Set the parent record.
     *
     * @param mvc_record_model $newparent
     * @throws \coding_exception
     */
    protected function set_parent(mvc_record_model $newparent) {
        if (is_null($this->parentrec)) {
            $this->parentrec = $newparent;
        } else {
            throw new \coding_exception('parent record can be set only once');
        }
    }

    /**
     * Retrieve the parent record.
     *
     * @throws \coding_exception
     * @return mvc_record_model
     */
    protected function get_parent() {
        if (is_null($this->parentrec)) {
            throw new \coding_exception('parent has not been set');
        }
        return $this->parentrec;
    }

    /**
     * Retrieve the id of the parent record
     *
     * @return int
     */
    protected function get_parent_id() {
        return $this->get_parent()->get_id();
    }

}

