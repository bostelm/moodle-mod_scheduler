<?php

/**
 * Scheduled background tasks in the scheduler module
 *
 * @package    mod_scheduler
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
            array(
                'classname' => 'mod_scheduler\task\send_reminders',
                'minute' => 'R',
                'hour' => '*',
                'day' => '*',
                'dayofweek' => '*',
                'month' => '*'
            ),
            array(
                'classname' => 'mod_scheduler\task\purge_unused_slots',
                'minute' => '*/5',
                'hour' => '*',
                'day' => '*',
                'dayofweek' => '*',
                'month' => '*'
            )
);