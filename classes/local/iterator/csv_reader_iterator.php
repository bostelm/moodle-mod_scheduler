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
 * CSV iterator.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\local\iterator;
defined('MOODLE_INTERNAL') || die();

use csv_import_reader;

/**
 * CSV iterator.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class csv_reader_iterator implements \Iterator {

    /** @var csv_import_reader The CSV import reader. */
    protected $cir;
    /** @var mixed Current value. */
    protected $current;
    /** @var int The current position. */
    protected $pos = 0;
    /** @var bool Whether the reader was initialised. */
    protected $initialised = false;

    /**
     * Constructor.
     *
     * @param csv_import_reader $cir The CSV reader.
     */
    public function __construct(csv_import_reader $cir) {
        $this->cir = $cir;
    }

    /**
     * Return current value.
     *
     * @return mixed
     */
    public function current() {
        if ($this->current === null) {
            $this->next();
        }
        return $this->current;
    }

    /**
     * Ensure the CSV reader was initialised.
     *
     * @return void
     */
    protected function ensure_initialised() {
        if (!$this->initialised) {
            $this->initialised = true;
            $this->cir->init();
            $this->current = null;
        }
    }

    /**
     * Return the line number.
     *
     * Note that the reader handles the CSV headers for us, so offset this value by 1.
     *
     * @return int
     */
    public function key() {
        return $this->pos + 1;
    }

    /**
     * Go to the next value.
     *
     * @return void
     */
    public function next() {
        $this->ensure_initialised();
        $this->pos++;
        $this->current = $this->cir->next();
    }

    /**
     * Rewind the reader.
     *
     * @return void
     */
    public function rewind() {
        $this->pos = 0;
        $this->initialised = false;
        $this->current = null;
        $this->cir->close();
    }

    /**
     * Whether the reader is still in a valid state.
     *
     * @return bool
     */
    public function valid() {
        return $this->current !== false;
    }
}
