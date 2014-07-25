<?php

function xmldb_scheduler_upgrade($oldversion=0) {
    // This function does anything necessary to upgrade older versions to match current functionality.

    global $CFG, $DB;

    $dbman = $DB->get_manager();

    $result = true;

    /* ******************* 2.0 upgrade line ********************** */

    if ($oldversion < 2011081302) {

        // Rename description field to intro, and define field introformat to be added to scheduler
        $table = new xmldb_table('scheduler');
        $introfield = new xmldb_field('description', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, 'name');
        $dbman->rename_field($table, $introfield, 'intro', false);

        $formatfield = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED,
            XMLDB_NOTNULL, null, '0', 'intro');

        if (!$dbman->field_exists($table, $formatfield)) {
            $dbman->add_field($table, $formatfield);
        }

        // conditionally migrate to html format in intro
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

        // savepoint reached
        upgrade_mod_savepoint(true, 2011081302, 'scheduler');
    }

    /* ******************* 2.5 upgrade line ********************** */

    if ($oldversion < 2012102903) {

        // Define fields notesformat and appointmentnote in respective tables
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

        // migrate html format
        if ($CFG->texteditors !== 'textarea') {
            upgrade_set_timeout();
            $DB->set_field('scheduler_slots', 'notesformat', FORMAT_HTML);
            $DB->set_field('scheduler_appointment', 'appointmentnoteformat', FORMAT_HTML);
        }

        // savepoint reached
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

    return true;
}