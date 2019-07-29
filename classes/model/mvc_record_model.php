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
 * A model mirroring one datebase record in a specific table of the Moodle DB.
 *
 * @package    mod_scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\model;

defined('MOODLE_INTERNAL') || die();


/**
 * A model mirroring one datebase record in a specific table of the Moodle DB.
 *
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mvc_record_model extends mvc_model {

    /**
     * @var \stdClass the underlying data record
     */
    protected $data = null;

    /**
     * Retrieve the name of the underlying database table
     *
     * @return string
     */
    abstract protected function get_table();

    /**
     * Create a new model. (To be used in subclass constructors.)
     */
    protected function __construct() {
        $data = new \stdClass();
    }

    /**
     * Load data from database. Should be used only in constructors / factory methods.
     *
     * @param int $id
     */
    public function load($id) {
        global $DB;
        $rec = $DB->get_record($this->get_table(), array('id' => $id), '*', MUST_EXIST);
        $this->data = $rec;
    }

    /**
     * Load data from a database record
     *
     * @param \stdClass $rec the database record
     */
    public function load_record(\stdClass $rec) {
        $this->data = $rec;
    }

    /**
     * Magic get method
     *
     * Attempts to call a get_$key method to return the property.
     * If not possible, returns the property from the internal record.
     * If even that is not possible, fails with an exception.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key) {
        if (method_exists($this, 'get_'.$key)) {
            return $this->{'get_'.$key}();
        } else if (property_exists($this->data, $key)) {
            return $this->data->{$key};
        } else {
            throw new \coding_exception('unknown property: '.$key);
        }
    }

    /**
     * Magic set method
     *
     * Attempts to call a set_$key method to set the property.
     * If not possible, sets the property directly in the internal record.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value) {
        if (method_exists($this, 'set_'.$key)) {
            $this->{'set_'.$key}($value);
        } else {
            $this->data->{$key} = $value;
        }
    }

    /**
     * Save any changes to the database
     */
    public function save() {
        global $DB;
        if (is_null($this->data)) {
            throw new \coding_exception('Missing data, cannot save');
        } else if (property_exists($this->data, 'id') && ($this->data->id)) {
            $DB->update_record($this->get_table(), $this->data);
        } else {
            $newid = $DB->insert_record($this->get_table(), $this->data);
            $this->data->id = $newid;
        }
    }

    /**
     * Retrieve the id number of the record
     *
     * @return int
     */
    public function get_id() {
        if (is_null($this->data)) {
            return 0;
        } else {
            return $this->data->id;
        }
    }

    /**
     * Retrieve the associated data record
     *
     * Note that this is a copy (clone) of the data,
     * changes to the returned record object will not lead to changes in the
     * data of the present record.
     *
     * @return \stdClass
     */
    public function get_data() {
        return clone($this->data);
    }

    /**
     * Set a number of properties at once.
     *
     * @param mixed $data either an array or an object describing the properties to be set
     * @param array $propnames list of properties to be set,
     *        or null if all properties in the input should be used
     */
    public function set_data($data, $propnames = null) {
        $data = (array) $data;
        if (is_null($propnames)) {
            $propnames = array_keys($data);
        }
        foreach ($propnames as $propname) {
            $this->{$propname} = $data[$propname];
        }
    }

    /**
     * Delete this model (from the database).
     */
    public function delete() {
        global $DB;

        $id = $this->get_id();
        if ($id != 0) {
            $DB->delete_records($this->get_table(), array('id' => $id));
        }
    }

}
