<?php

/**
 *  An abstract factory class for loading child records from the database.
 *
 * @package    mod_scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\model;

defined('MOODLE_INTERNAL') || die();

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

    public function create() {
        return $this->create_child($this->myparent);
    }

    /**
     * Create a new child record (with no data)
     *
     * @param mvc_record_model $parent
     */
    public abstract function create_child(mvc_record_model $parent);

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

