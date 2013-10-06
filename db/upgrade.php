<?php

function xmldb_scheduler_upgrade($oldversion=0) {
/// This function does anything necessary to upgrade 
/// older versions to match current functionality 

    global $CFG, $DB;
    
    $result = true;

    if ($oldversion < 2003121200) {

       table_column('scheduler_slots', '', 'reuse', 'smallint', '4', 'unsigned', '0', '', 'student');

    }
    if ($oldversion < 2005091100) {
       table_column('scheduler_slots', '', 'exclusive', 'smallint', '4', 'unsigned', '1', '', 'timemodified');
       table_column('scheduler_slots', '', 'hideuntil', 'int', '10', '', '', '', 'notes');
       table_column('scheduler_slots', '', 'emaildate', 'int', '10', '', '', '', 'notes');
       table_column('scheduler_slots', '', 'teacher', 'int', '10', '', '', '', 'student');
       table_column('scheduler', '', 'schedulermode', 'varchar', '10', '', 'oneonly', '', 'description');
    }
    if ($oldversion < 2005091101) {
       table_column('scheduler_slots', '', 'appointmentlocation', 'varchar', '50', '', '', '', 'teacher');
    }
    if ($oldversion < 2005100901) {
       table_column('scheduler_slots', '', 'notes', 'text', '', '', '', '', 'timemodified');
    }
    if ($oldversion < 2007092901){
        table_column('scheduler', '', 'staffrolename', 'varchar', '40', '', '', '', 'schedulermode') ;
    }
    if ($oldversion < 2007092903){
        modify_database('', "ALTER TABLE `{$CFG->prefix}scheduler_slots` CHANGE `exclusive` `exclusivity` SMALLINT( 4 ) UNSIGNED DEFAULT '1'  ; ");
    }
    if ($oldversion < 2007101900){
       table_column('scheduler', '', 'allownotifications', 'int', '4', 'unsigned', '0', '', 'schedulermode');
    }

    if ($oldversion < 2007110200){
        modify_database('', "ALTER TABLE `{$CFG->prefix}scheduler_slots` DROP `student` ; ");
        modify_database('', "ALTER TABLE `{$CFG->prefix}scheduler_slots` DROP `attended` ; ");
        modify_database('', "ALTER TABLE `{$CFG->prefix}scheduler_slots` CHANGE scheduler schedulerid BIGINT(10) NOT NULL; "); 
        modify_database('', "ALTER TABLE `{$CFG->prefix}scheduler_slots` CHANGE starttime starttime BIGINT(10) NOT NULL; "); 
        modify_database('', "ALTER TABLE `{$CFG->prefix}scheduler_slots` CHANGE duration duration BIGINT(10) NOT NULL; "); 
        modify_database('', "ALTER TABLE `{$CFG->prefix}scheduler_slots` CHANGE teacher teacherid BIGINT(10) NULL; "); 
        modify_database('', "ALTER TABLE `{$CFG->prefix}scheduler_slots` CHANGE reuse reuse MEDIUMINT(5) NULL UNSIGNED; "); 
        modify_database('', "ALTER TABLE `{$CFG->prefix}scheduler_slots` CHANGE timemodified timemodified BIGINT(10) NULL; "); 
        modify_database('', "ALTER TABLE `{$CFG->prefix}scheduler_slots` CHANGE exclusivity exclusivity SMALLINT(4) NOT NULL; "); 
        modify_database('', "ALTER TABLE `{$CFG->prefix}scheduler_slots` CHANGE emaildate emaildate BIGINT(11) NOT NULL; "); 
        modify_database('', "ALTER TABLE `{$CFG->prefix}scheduler_slots` CHANGE hideuntil hideuntil BIGINT(11) NOT NULL; "); 
        modify_database('', "ALTER TABLE `{$CFG->prefix}scheduler` CHANGE id id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT; "); 
        modify_database('', "ALTER TABLE `{$CFG->prefix}scheduler` CHANGE course course BIGINT(10) NOT NULL; ");
        modify_database('', "ALTER TABLE `{$CFG->prefix}scheduler` CHANGE staffrolename staffrolename VARCHAR(40) NOT NULL; ");
        modify_database('', "ALTER TABLE `{$CFG->prefix}scheduler` CHANGE teacher teacher BIGINT(10) NOT NULL; ");
        modify_database('', "ALTER TABLE `{$CFG->prefix}scheduler` CHANGE timemodified timemodified BIGINT(10); ");

    }   
    if ($oldversion < 2007110201){
        table_column('scheduler', '', 'reuseguardtime', 'int', '10', 'unsigned', '0', '', 'schedulermode');
        table_column('scheduler', '', 'defaultslotduration', 'int', '4', 'unsigned', '15', '', 'reuseguardtime');
        table_column('scheduler', '', 'scale', 'int', '10', '', '0', '', 'teacher');
        
    /// Define table scheduler_appointment to be created
        $table = new XMLDBTable('scheduler_appointment');

    /// Adding fields to table scheduler_appointment
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, 0);
        $table->addFieldInfo('slotid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null, null, 0);
        $table->addFieldInfo('studentid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null, null, 0);
        $table->addFieldInfo('attended', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null, null, 0);
        $table->addFieldInfo('grade', XMLDB_TYPE_INTEGER, '4', null, null, null, null, null, null);
        $table->addFieldInfo('appointmentnote', XMLDB_TYPE_TEXT, 'small', null, null, null, null, null, null);
        $table->addFieldInfo('timecreated', XMLDB_TYPE_INTEGER, '11', null, null, null, null, null, null);
        $table->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '11', null, null, null, null, null, null);

    /// Adding keys to table scheduler_appointment
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));

    /// Launch create table for scheduler_appointment
        $result = $result && create_table($table);
    }
    if (!$result) return false;
    
    if ($oldversion < 2007110208){
        table_column('scheduler', '', 'gradingstrategy', 'int', '2', 'unsigned', '0', '', 'scale');
        modify_database('', "ALTER TABLE `{$CFG->prefix}scheduler` CHANGE reuseguardtime reuseguardtime BIGINT(10) DEFAULT NULL; "); 
        modify_database('', "ALTER TABLE `{$CFG->prefix}scheduler` CHANGE gradingstrategy gradingstrategy TINYINT(2); ");
        modify_database('', "ALTER TABLE `{$CFG->prefix}scheduler` CHANGE scale scale BIGINT(10); ");
    }
    
    if ($oldversion < 2008040100){
    /// Changing nullability of field grade on table scheduler_appointment to not null
        $table = new XMLDBTable('scheduler_appointment');
        $field = new XMLDBField('grade');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '4', null, null, null, null, null, null, 'attended');

    /// Launch change of nullability for field grade
        change_field_notnull($table, $field);
    }
    
    /* ******************* 2.0 upgrade line ********************** */ 
    
        if ($oldversion < 2011081302) {

		$dbman = $DB->get_manager();

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
    
        $dbman = $DB->get_manager();
    
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
    
    return true;
}

?>
