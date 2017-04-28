<?php

/**
 * Upgrade code for the scheduler module
 *
 * @package    mod_scheduler
 * @copyright  2017 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Migrate a configuration setting from global to plugin specific.
 *
 * @param string $name name of configuration setting
 */
function scheduler_migrate_config_setting($name) {
    $oldval = get_config('core', 'scheduler_'.$name);
    set_config($name, $oldval, 'mod_scheduler');
    unset_config('scheduler_'.$name);
}

/**
 * Migrate the group mode settings to new 2.9 conventions.
 *
 * @param int $sid id of the scheduler to migrate
 */
function scheduler_migrate_groupmode($sid) {
    global $DB;
    $globalenable = (bool) get_config('mod_scheduler', 'groupscheduling');
    $cm = get_coursemodule_from_instance('scheduler', $sid, 0, false, IGNORE_MISSING);
    if ($cm) {
        if ((groups_get_activity_groupmode($cm) > 0) && $globalenable) {
            $g = $cm->groupingid;
        } else {
            $g = -1;
        }
        $DB->set_field('scheduler', 'bookingrouping', $g, array('id' => $sid));
        $DB->set_field('course_modules', 'groupmode', 0, array('id' => $cm->id));
        $DB->set_field('course_modules', 'groupingid', 0, array('id' => $cm->id));
    }
}

/**
 * This function does anything necessary to upgrade older versions to match current functionality.
 *
 * @param int $oldversion version number to be migrated from
 * @return bool true if upgrade is successful
 */
function xmldb_scheduler_upgrade($oldversion=0) {

    global $CFG, $DB;

    $dbman = $DB->get_manager();

    $result = true;

    /* ******************* 2.0 upgrade line ********************** */

    if ($oldversion < 2011081302) {

        // Rename description field to intro, and define field introformat to be added to scheduler.
        $table = new xmldb_table('scheduler');
        $introfield = new xmldb_field('description', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, 'name');
        $dbman->rename_field($table, $introfield, 'intro', false);

        $formatfield = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED,
            XMLDB_NOTNULL, null, '0', 'intro');

        if (!$dbman->field_exists($table, $formatfield)) {
            $dbman->add_field($table, $formatfield);
        }

        // Conditionally migrate to html format in intro.
        if ($CFG->texteditors !== 'textarea') {
            $rs = $DB->get_recordset('scheduler', array('introformat' => FORMAT_MOODLE),
                '', 'id, intro, introformat');
            foreach ($rs as $q) {
                $q->intro       = text_to_html($q->intro, false, false, true);
                $q->introformat = FORMAT_HTML;
                $DB->update_record('scheduler', $q);
                upgrade_set_timeout();
            }
            $rs->close();
        }

        // Savepoint reached.
        upgrade_mod_savepoint(true, 2011081302, 'scheduler');
    }

    /* ******************* 2.5 upgrade line ********************** */

    if ($oldversion < 2012102903) {

        // Define fields notesformat and appointmentnote in respective tables.
        $table = new xmldb_table('scheduler_slots');
        $formatfield = new xmldb_field('notesformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED,
            XMLDB_NOTNULL, null, '0', 'notes');
        if (!$dbman->field_exists($table, $formatfield)) {
            $dbman->add_field($table, $formatfield);
        }

        $table = new xmldb_table('scheduler_appointment');
        $formatfield = new xmldb_field('appointmentnoteformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED,
            XMLDB_NOTNULL, null, '0', 'appointmentnote');
        if (!$dbman->field_exists($table, $formatfield)) {
            $dbman->add_field($table, $formatfield);
        }

        // Migrate html format.
        if ($CFG->texteditors !== 'textarea') {
            upgrade_set_timeout();
            $DB->set_field('scheduler_slots', 'notesformat', FORMAT_HTML);
            $DB->set_field('scheduler_appointment', 'appointmentnoteformat', FORMAT_HTML);
        }

        // Savepoint reached.
        upgrade_mod_savepoint(true, 2012102903, 'scheduler');
    }

    /* ******************* 2.7 upgrade line ********************** */

    if ($oldversion < 2014071300) {

        // Define field teacher to be dropped from scheduler.
        $table = new xmldb_table('scheduler');
        $field = new xmldb_field('teacher');

        // Conditionally drop field teacher.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field maxbookings to be added to scheduler.
        $table = new xmldb_table('scheduler');
        $field = new xmldb_field('maxbookings', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1', 'schedulermode');

        // Conditionally launch add field maxbookings.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field guardtime to be added to scheduler.
        $table = new xmldb_table('scheduler');
        $field = new xmldb_field('guardtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'maxbookings');

        // Conditionally launch add field guardtime.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Changing length of field staffrolename on table scheduler to (255).
        $table = new xmldb_table('scheduler');
        $field = new xmldb_field('staffrolename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'allownotifications');

        // Launch change of precision for field staffrolename.
        $dbman->change_field_precision($table, $field);

        // Changing length of field appointmentlocation on table scheduler_slots to (255).
        $table = new xmldb_table('scheduler_slots');
        $field = new xmldb_field('appointmentlocation', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'teacherid');

        // Launch change of precision for field appointmentlocation.
        $dbman->change_field_precision($table, $field);

        // Define index schedulerid-teacherid (not unique) to be added to scheduler_slots.
        $table = new xmldb_table('scheduler_slots');
        $index = new xmldb_index('schedulerid-teacherid', XMLDB_INDEX_NOTUNIQUE, array('schedulerid', 'teacherid'));

        // Conditionally launch add index schedulerid-teacherid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index slotid (not unique) to be added to scheduler_appointment.
        $table = new xmldb_table('scheduler_appointment');
        $index = new xmldb_index('slotid', XMLDB_INDEX_NOTUNIQUE, array('slotid'));

        // Conditionally add index slotid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index studentid (not unique) to be added to scheduler_appointment.
        $table = new xmldb_table('scheduler_appointment');
        $index = new xmldb_index('studentid', XMLDB_INDEX_NOTUNIQUE, array('studentid'));

        // Conditionally add index studentid.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Convert old calendar events.
        $sql = 'UPDATE {event} SET modulename = ? WHERE eventtype LIKE ? OR eventtype LIKE ?';
        $DB->execute($sql, array('scheduler', 'SSsup:%', 'SSstu:%'));

        // Savepoint reached.
        upgrade_mod_savepoint(true, 2014071300, 'scheduler');
    }

    /* ******************* 2.9 upgrade line ********************** */

    if ($oldversion < 2015050400) {

        // Migrate config settings to config_plugins table.
        scheduler_migrate_config_setting('allteachersgrading');
        scheduler_migrate_config_setting('showemailplain');
        scheduler_migrate_config_setting('groupscheduling');
        scheduler_migrate_config_setting('maxstudentlistsize');

        // Savepoint reached.
        upgrade_mod_savepoint(true, 2015050400, 'scheduler');
    }

    if ($oldversion < 2015062601) {

        // Define field bookingrouping to be added to scheduler.
        $table = new xmldb_table('scheduler');
        $field = new xmldb_field('bookingrouping', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '-1', 'gradingstrategy');

        // Conditionally launch add field bookingrouping.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Convert old group mode into instance setting for scheduler.
        $sids = $DB->get_fieldset_select('scheduler', 'id', '');
        foreach ($sids as $sid) {
            scheduler_migrate_groupmode($sid);
        }

        // Scheduler savepoint reached.
        upgrade_mod_savepoint(true, 2015062601, 'scheduler');
    }

    /* ******************* 3.1 upgrade line ********************** */

    if ($oldversion < 2016051700) {

        // Add configuration field "usenotes" to scheduler table.
        $table = new xmldb_table('scheduler');
        $field = new xmldb_field('usenotes', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'bookingrouping');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add field "teachernote" (note visible to teachers only) and corresponding format field to scheduler_appointment.
        $table = new xmldb_table('scheduler_appointment');
        $field1 = new xmldb_field('teachernote', XMLDB_TYPE_TEXT, null, null, null, null, null, 'appointmentnoteformat');
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        $field2 = new xmldb_field('teachernoteformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'teachernote');
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }

        // Drop old unused field "appointmentnote" from scheduler_slots table.
        $table = new xmldb_table('scheduler_slots');
        $field = new xmldb_field('appointmentnote');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Scheduler savepoint reached.
        upgrade_mod_savepoint(true, 2016051700, 'scheduler');
    }

    /* ******************* 3.3 upgrade line ********************** */

    if ($oldversion < 2017040100) {

        // Add new configuration fields (relating to booking form) to scheduler.
        $table = new xmldb_table('scheduler');

        $field = new xmldb_field('usebookingform', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'usenotes');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('bookinginstructions', XMLDB_TYPE_TEXT, null, null, null, null, null, 'usebookingform');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('bookinginstructionsformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1', 'bookinginstructions');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('usestudentnotes', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'bookinginstructionsformat');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('requireupload', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'usestudentnotes');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('uploadmaxfiles', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'requireupload');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('uploadmaxsize', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'uploadmaxfiles');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('usecaptcha', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'uploadmaxsize');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add field "studentnote" and corresponding format field to scheduler_appointment.
        $table = new xmldb_table('scheduler_appointment');
        $field1 = new xmldb_field('studentnote', XMLDB_TYPE_TEXT, null, null, null, null, null, 'teachernoteformat');
        if (!$dbman->field_exists($table, $field1)) {
            $dbman->add_field($table, $field1);
        }
        $field2 = new xmldb_field('studentnoteformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'studentnote');
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }

        // Scheduler savepoint reached.
        upgrade_mod_savepoint(true, 2017040100, 'scheduler');
    }
    return true;
}