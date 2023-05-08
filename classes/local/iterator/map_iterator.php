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

/**
 * Iterator map.
 *
 * To iterate over an iterator applying a function to its returned values.
 *
 * The callback receives the iterator's value as first argument, and the
 * index of the value as second argument.
 */
class map_iterator implements \Iterator {

    /** @var Iterator The iterator. */
    protected $iterator;
    /** @var callable The callback. */
    protected $callback;

    /**
     * Constructor.
     *
     * @param Iterator $iterator The iterator.
     * @param callable $callback The callback to apply to each item.
     */
    public function __construct(\Iterator $iterator, callable $callback) {
        $this->iterator = $iterator;
        $this->callback = $callback;
    }

    #[\ReturnTypeWillChange]
    /**
     * Current.
     *
     * @return mixed
     */
    public function current() {
        $cb = $this->callback;
        return $cb($this->iterator->current(), $this->iterator->key());
    }

    #[\ReturnTypeWillChange]
    /**
     * Key.
     *
     * @return mixed
     */
    public function key() {
        return $this->iterator->key();
    }

    /**
     * Next.
     */
    public function next(): void {
        $this->iterator->next();
    }

    /**
     * Rewing.
     */
    public function rewind(): void {
        $this->iterator->rewind();
    }

    /**
     * Valid.
     *
     * @return bool
     */
    public function valid(): bool {
        return $this->iterator->valid();
    }

}
