<?php

/**
 * Library for basic Model-View-Controller (MVC) structures
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * A generic MVC model
 *
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mvc_model {

}


/**
 * A model mirroring one datebase record in a specific table of the Moodle DB
 *
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mvc_record_model extends mvc_model {

    protected $data = null;

    abstract protected function get_table();

    protected function __construct() {
        $data = new stdClass();
    }

    /**
     * Load data from database. Should be used only in constructors / factory methods.
     */
    public function load($id) {
        global $DB;
        $rec = $DB->get_record($this->get_table(), array('id' => $id), '*', MUST_EXIST);
        $this->data = $rec;
    }

    public function load_record(stdClass $rec) {
        $this->data = $rec;
    }

    /**
     * Magic get method
     *
     * Attempts to call a get_$key method to return the property.
     * If not possible, returns the property from the internal record.
     * If even that is not possible, fails with an exception.
     *
     * @param str $key
     * @return mixed
     */
    public function __get($key) {
        if (method_exists($this, 'get_'.$key)) {
            return $this->{'get_'.$key}();
        } else if (property_exists($this->data, $key)) {
            return $this->data->{$key};
        } else {
            throw new coding_exception('unknown property: '.$key);
        }
    }

    /**
     * Magic set method
     *
     * Attempts to call a set_$key method to set the property.
     * If not possible, sets the property directly in the internal record.
     *
     * @param str $key
     * @return mixed
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
            throw new coding_exception('Missing data, cannot save');
        } else if (property_exists($this->data, 'id') && ($this->data->id)) {
            $DB->update_record($this->get_table(), $this->data);
        } else {
            $newid = $DB->insert_record($this->get_table(), $this->data);
            $this->data->id = $newid;
        }
    }

    /**
     * Retrieve the id number of the record
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
     */
    public function get_data() {
        return clone($this->data);
    }

    /**
     * Set a number of properties at once.
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

    public function delete() {
        global $DB;

        $id = $this->get_id();
        if ($id != 0) {
            $DB->delete_records($this->get_table(), array('id' => $id));
        }
    }

}


abstract class mvc_child_record_model extends mvc_record_model {

    private $parentrec;

    protected function set_parent(mvc_record_model $newparent) {
        if (is_null($this->parentrec)) {
            $this->parentrec = $newparent;
        } else {
            throw new coding_exception('parent record can be set only once');
        }
    }

    protected function get_parent() {
        if (is_null($this->parentrec)) {
            throw new coding_exception('parent has not been set');
        }
        return $this->parentrec;
    }

    protected function get_parent_id() {
        return $this->get_parent()->get_id();
    }

}

abstract class mvc_model_factory {
    public abstract function create();

    public function create_from_id($id) {
        $new = $this->create();
        $new->load($id);
        return $new;
    }

}

abstract class mvc_child_model_factory extends mvc_model_factory {

    protected $myparent;

    public function __construct(mvc_record_model $parent) {
        $this->myparent = $parent;
    }

    public function create() {
        return $this->create_child($this->myparent);
    }

    public abstract function create_child(mvc_record_model $parent);

    public function create_child_from_record(stdClass $rec) {
        $new = $this->create_child($this->myparent);
        $new->load_record($rec);
        return $new;
    }
}


class mvc_child_list {

    private $children;
    private $childcount;
    private $childtable;
    private $childfield;
    private $childfactory;
    private $childrenfordeletion;
    private $parentmodel;

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

    private function get_parent_id() {
        return $this->parentmodel->get_id();
    }

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

    public function get_children() {
        $this->load();
        return $this->children;
    }

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

    public function create_child() {
        $this->load();
        $newchild = $this->childfactory->create();
        $this->children[] = $newchild;
        return $newchild;
    }

    public function remove_child(mvc_child_record_model $child) {
        if (is_null($this->children) || !in_array($child, $this->children)) {
            throw new coding_exception ('Child record to remove not found in list');
        }
        $key = array_search($child, $this->children, true);
        unset($this->children[$key]);
        $this->childrenfordeletion[] = $child;
    }

    public function delete_children() {
        $this->load();
        foreach ($this->children as $child) {
            $child->delete();
        }
    }

}
