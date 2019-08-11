<?php

/**
 * An abstract factory class for loading records from the database.
 *
 * @package    mod_scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\model;

defined('MOODLE_INTERNAL') || die();

/**
 * An abstract factory class for loading records from the database.
 *
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mvc_model_factory {

    /**
     * Create a new instance of a record, with no data.
     *
     *  @return mvc_model
     */
    public abstract function create();

    /**
     * Create a new record by loading it from the database.
     *
     * @param int $id the id of the record to load
     * @return mvc_model
     */
    public function create_from_id($id) {
        $new = $this->create();
        $new->load($id);
        return $new;
    }

}
