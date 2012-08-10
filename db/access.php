<?php
/**
 * Scheduler module capability definition
 *
 * @package    mod
 * @subpackage scheduler
 */

$capabilities = array(

    'mod/scheduler:addinstance' => array(
        'riskbitmask' => RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/course:manageactivities'
    ),

    'mod/scheduler:appoint' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'coursecreator' => CAP_PREVENT,
            'manager' => CAP_PREVENT
        )
    ),

    'mod/scheduler:attend' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_PREVENT,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_PREVENT,
            'manager' => CAP_PREVENT
        )
    ),

    'mod/scheduler:manage' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_PROHIBIT,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'mod/scheduler:manageallappointments' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_PROHIBIT,
            'teacher' => CAP_PREVENT,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'mod/scheduler:canscheduletootherteachers' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_PROHIBIT,
            'teacher' => CAP_PREVENT,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'mod/scheduler:canseeotherteachersbooking' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_PROHIBIT,
            'teacher' => CAP_PREVENT,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'mod/scheduler:disengage' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'mod/scheduler:seeotherstudentsbooking' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    ),

    'mod/scheduler:seeotherstudentsresults' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_PREVENT,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    )

);


?>
