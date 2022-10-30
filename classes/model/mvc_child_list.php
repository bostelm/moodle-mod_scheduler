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
 *  A list of child records.
 *
 * @package    mod_scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\model;

/**
 * A list of child records.
 *
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mvc_child_list {

    /**
     * @var array list of child records
     */
    private $children;

    /**
     * @var int number of child records
     */
    private $childcount;

    /**
     * @var string name of the table for child records
     */
    private $childtable;

    /**
     * @var string name of parent id field in child table
     */
    private $childfield;

    /**
     * @var mvc_child_model_factory factory for new child records
     */
    private $childfactory;

    /**
     * @var array list of child records marked for deletion
     */
    private $childrenfordeletion;

    /**
     * @var mvc_record_model parent record
     */
    private $parentmodel;

    /**
     * Create a new child list.
     *
     * @param mvc_record_model $parent parent record
     * @param string $childtable name of table for child records
     * @param string $childfield name of parent id field in child table
     * @param mvc_model_factory $factory factory for child records
     */
    public function __construct(mvc_record_model $parent, $childtable, $childfield,
                                mvc_model_factory $factory) {
        $this->children = null;
        $this->childcount = -1;
        $this->childfield = $childfield;
        $this->childtable = $childtable;
        $this->childfactory = $factory;
        $this->parentmodel = $parent;
        $this->childrenfordeletion = array();
    }

    /**
     * Retrieve the id of the parent record
     * @return int
     */
    private function get_parent_id() {
        return $this->parentmodel->get_id();
    }

    /**
     * Load the list of all children from the database
     */
    public function load() {
        global $DB;
        if (!is_null($this->children)) {
            return; // Children already loaded.
        } else if (!$this->get_parent_id()) {
            // Parent ID is invalid - not yet stored.
            $this->children = array();
        } else {
            $this->children = array();
            $childrecs = $DB->get_records($this->childtable, array($this->childfield => $this->get_parent_id()));
            $cnt = 0;
            foreach ($childrecs as $rec) {
                $app = $this->childfactory->create_child_from_record($rec, $this->parentmodel);
                $this->children[$rec->id] = $app;
                $cnt++;
            }
            $this->childcount = $cnt;
        }
    }

    /**
     * Return a child record by its id
     *
     * @param int $id
     * @return mvc_child_record_model child record, or null if none found
     */
    public function get_child_by_id($id) {
        $this->load();
        $found = null;
        foreach ($this->children as $child) {
            if ($child->id == $id) {
                $found = $child;
                break;
            }
        }
        return $found;
    }

    /**
     * Return all children in this list
     *
     * @return array
     */
    public function get_children() {
        $this->load();
        return $this->children;
    }

    /**
     * Count the children in this list.
     *
     * @return int
     */
    public function get_child_count() {
        global $DB;
        if ($this->childcount >= 0) {
            return $this->childcount;
        } else if (!$this->get_parent_id()) {
            return 0; // No valid parent.
        } else {
            $cnt = $DB->count_records($this->childtable, array($this->childfield => $this->get_parent_id()));
            $this->childcount = $cnt;
            return $cnt;
        }
    }

    /**
     * Save all child records to the database.
     */
    public function save_children() {
        if (!is_null($this->children)) {
            foreach ($this->children as $child) {
                $child->save();
            }
        }
        foreach ($this->childrenfordeletion as $delchild) {
            $delchild->delete();
        }
        $this->childrenfordeletion = array();
    }

    /**
     * Create a new, empty child record.
     * @return mvc_child_record_model the new record
     */
    public function create_child() {
        $this->load();
        $newchild = $this->childfactory->create();
        $this->children[] = $newchild;
        return $newchild;
    }

    /**
     * Remove a child record from the list
     * @param mvc_child_record_model $child the record to remove
     * @throws \coding_exception if the record does nto belong to this list
     */
    public function remove_child(mvc_child_record_model $child) {
        if (is_null($this->children) || !in_array($child, $this->children)) {
            throw new \coding_exception ('Child record to remove not found in list');
        }
        $key = array_search($child, $this->children, true);
        unset($this->children[$key]);
        $this->childrenfordeletion[] = $child;
    }

    /**
     * Delete all child records
     */
    public function delete_children() {
        $this->load();
        foreach ($this->children as $child) {
            $child->delete();
        }
    }
}
