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
 *  An abstract factory class for loading child records from the database.
 *
 * @package    mod_scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\model;

/**
 * An abstract factory class for loading child records from the database.
 *
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mvc_child_model_factory extends mvc_model_factory {

    /**
     * @var mvc_model the parent record
     */
    protected $myparent;

    /**
     * Create a new factory based on a parent record
     * @param mvc_record_model $parent
     */
    public function __construct(mvc_record_model $parent) {
        $this->myparent = $parent;
    }

    /**
     * create
     *
     * @return mvc_model
     */
    public function create() {
        return $this->create_child($this->myparent);
    }

    /**
     * Create a new child record (with no data)
     *
     * @param mvc_record_model $parent
     */
    abstract public function create_child(mvc_record_model $parent);

    /**
     * Create a child record from a database entry, already loaded
     *
     * @param \stdClass $rec the record from the database
     * @return mvc_child_record_model the new child record
     */
    public function create_child_from_record(\stdClass $rec) {
        $new = $this->create_child($this->myparent);
        $new->load_record($rec);
        return $new;
    }
}

