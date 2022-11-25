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
 * Library with functions that are intended for local customizations.
 *
 * @package    mod_scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Get a list of fields to be displayed in lists of users, etc.
 *
 * The input of the function is a user record;
 * possibly null, in this case the function should return only the field titles.
 *
 * The function returns an array of objects that describe user data fields.
 * Each of these objects has the following properties:
 *  $field->title : Displayable title of the field
 *  $field->value : Value of the field for this user (not set if $user is null)
 *
 * @param stdClass $user the user record; may be null
 * @param context $context context for permission checks
 * @return array an array of field objects
 */
function scheduler_get_user_fields($user, $context) {

    $fields = array();

    if (has_capability('moodle/site:viewuseridentity', $context)) {
        $emailfield = new stdClass();
        $fields[] = $emailfield;
        $emailfield->title = get_string('email');
        if ($user) {
            $emailfield->value = obfuscate_mailto($user->email);
        }
    }

    /*
     * As an example: Uncomment the following lines in order to display the user's city and country.
     */

    /*
    $cityfield = new stdClass();
    $cityfield->title = get_string('city');
    $fields[] = $cityfield;

    $countryfield = new stdClass();
    $countryfield->title = get_string('country');
    $fields[] = $countryfield;

    if ($user) {
        $cityfield->value = $user->city;
        if ($user->country) {
            $countryfield->value = get_string($user->country, 'countries');
        }
        else {
            $countryfield->value = '';
        }
    }
    */
    return $fields;
}
