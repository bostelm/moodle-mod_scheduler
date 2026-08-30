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
 * Contains the class for fetching the important dates in mod_scheduler for a given module instance and a user.
 *
 * @package    mod_scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_scheduler;

use core\activity_dates;

class dates extends activity_dates {
    /**
     * Returns a list of important dates in mod_scheduler
     *
     * @return array
     */
    protected function get_dates(): array {
        $dates = [];
        if (!empty($scheduler->bookingstart)) {
            $dates[] = (object) [
                    'label' => get_string('activitydate:bookingstart', 'scheduler'),
                    'timestamp' => $scheduler->bookingstart,
            ];
        }

        if (!empty($scheduler->bookingend)) {
            $dates[] = (object) [
                    'label' => get_string('activitydate:bookingend', 'scheduler'),
                    'timestamp' => $scheduler->bookingend,
            ];
        }
        return $dates;
    }
}