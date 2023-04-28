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
 * Message formatting from templates.
 *
 * @package mod_scheduler
 * @copyright 2016 Henning Bostelmann and others (see README.txt)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \mod_scheduler\model\scheduler;
use \mod_scheduler\model\slot;

/**
 * Message functionality for scheduler module
 *
 * @package mod_scheduler
 * @copyright 2016 Henning Bostelmann and others (see README.txt)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduler_messenger {
    /**
     * Returns the language to be used in a message to a user
     *
     * @param stdClass $user
     *            the user to whom the message will be sent
     * @param stdClass $course
     *            the course from which the message originates
     * @return string
     */
    protected static function get_message_language($user, $course) {
        if ($course && ! empty ($course->id) && $course->id != SITEID && !empty($course->lang)) {
            // Course language overrides user language.
            $return = $course->lang;
        } else if (!empty($user->lang)) {
            $return = $user->lang;
        } else if (isset ($CFG->lang)) {
            $return = $CFG->lang;
        } else {
            $return = 'en';
        }

        return $return;
    }

    /**
     * Gets the content of an e-mail from language strings.
     *
     * Looks for the language string email_$template_$format and replaces the parameter values.
     *
     * @param string $template the template's identified
     * @param string $format the mail format ('subject', 'html' or 'plain')
     * @param array $parameters an array ontaining pairs of parm => data to replace in template
     * @param string $module module to use language strings from
     * @param string $lang language to use
     * @return a fully resolved template where all data has been injected
     *
     */
    public static function compile_mail_template($template, $format, $parameters, $module = 'scheduler', $lang = null) {
        $params = array ();
        foreach ($parameters as $key => $value) {
            $params[strtolower($key)] = $value;
        }
        $mailstr = get_string_manager()->get_string("email_{$template}_{$format}", $module, $params, $lang);
        return $mailstr;
    }

    /**
     * Sends a message based on a template.
     * Several template substitution values are automatically filled by this routine.
     *
     * @param string $modulename
     *            name of the module that sends the message
     * @param string $messagename
     *            name of the message in messages.php
     * @param int $isnotification
     *            1 for notifications, 0 for personal messages
     * @param stdClass $sender
     *            A {@see $USER} object describing the sender
     * @param stdClass $recipient
     *            A {@see $USER} object describing the recipient
     * @param object $course
     *            The course that the activity is in. Can be null.
     * @param string $template
     *            the mail template name as in language config file (without "_html" part)
     * @param array $parameters
     *            a hash containing pairs of parm => data to replace in template
     * @return bool|int Returns message id if message was sent OK, "false" if there was another sort of error.
     */
    public static function send_message_from_template($modulename, $messagename, $isnotification,
                                                      stdClass $sender, stdClass $recipient, $course,
                                                      $template, array $parameters) {
        global $CFG;
        global $SITE;

        $lang = self::get_message_language($recipient, $course);

        $defaultvars = array (
                'SITE' => $SITE->fullname,
                'SITE_SHORT' => $SITE->shortname,
                'SITE_URL' => $CFG->wwwroot,
                'SENDER' => fullname ( $sender ),
                'RECIPIENT' => fullname ( $recipient )
        );

        if ($course) {
            $defaultvars['COURSE_SHORT'] = format_string($course->shortname);
            $defaultvars['COURSE'] = format_string($course->fullname);
            $defaultvars['COURSE_URL'] = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
        }

        $vars = array_merge($defaultvars, $parameters);

        $message = new \core\message\message();
        $message->component = $modulename;
        $message->name = $messagename;
        $message->userfrom = $sender;
        $message->userto = $recipient;
        $message->subject = self::compile_mail_template($template, 'subject', $vars, $modulename, $lang);
        $message->fullmessage = self::compile_mail_template($template, 'plain', $vars, $modulename, $lang);
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = self::compile_mail_template ( $template, 'html', $vars, $modulename, $lang );
        $message->notification = '1';
        $message->courseid = $course->id;
        $message->contexturl = $defaultvars['COURSE_URL'];
        $message->contexturlname = $course->fullname;

        $msgid = message_send($message);
        return $msgid;
    }

    /**
     * Construct an array with subtitution rules for mail templates, relating to
     * a single appointment. Any of the parameters can be null.
     *
     * @param scheduler $scheduler The scheduler instance
     * @param slot $slot The slot data as an MVC object, may be null
     * @param user $teacher A {@see $USER} object describing the attendant (teacher)
     * @param user $student A {@see $USER} object describing the attendee (student)
     * @param object $course A course object relating to the ontext of the message
     * @param object $recipient A {@see $USER} object describing the recipient of the message
     *                          (used for determining the message language)
     * @return array A hash with mail template substitutions
     */
    public static function get_scheduler_variables(scheduler $scheduler,  $slot,
                                                   $teacher, $student, $course, $recipient) {

        global $CFG;

        $lang = self::get_message_language($recipient, $course);
        // Force any string formatting to happen in the target language.
        $oldlang = force_current_language($lang);

        $tz = core_date::get_user_timezone($recipient);

        $vars = array();

        if ($scheduler) {
            $vars['MODULE']     = format_string($scheduler->name);
            $vars['STAFFROLE']  = $scheduler->get_teacher_name();
            $vars['SCHEDULER_URL'] = $CFG->wwwroot.'/mod/scheduler/view.php?id='.$scheduler->cmid;
        }
        if ($slot) {
            $vars['DATE']     = userdate($slot->starttime, get_string('strftimedate'), $tz);
            $vars['TIME']     = userdate($slot->starttime, get_string('strftimetime'), $tz);
            $vars['ENDTIME']  = userdate($slot->endtime, get_string('strftimetime'), $tz);
            $vars['LOCATION'] = format_string($slot->appointmentlocation);
        }
        if ($teacher) {
            $vars['ATTENDANT']     = fullname($teacher);
            $vars['ATTENDANT_URL'] = $CFG->wwwroot.'/user/view.php?id='.$teacher->id.'&course='.$scheduler->course;
        }
        if ($student) {
            $vars['ATTENDEE']     = fullname($student);
            $vars['ATTENDEE_URL'] = $CFG->wwwroot.'/user/view.php?id='.$student->id.'&course='.$scheduler->course;
        }

        // Reset language settings.
        force_current_language($oldlang);

        return $vars;

    }


    /**
     * Send a notification message about a scheduler slot.
     *
     * @param slot $slot the slot that the notification relates to
     * @param string $messagename name of message as in db/message.php
     * @param string $template template name to use (language string up to prefix/postfix)
     * @param stdClass $sender user record for sender
     * @param stdClass $recipient  user record for recipient
     * @param stdClass $teacher user record for teacher
     * @param stdClass $student user record for student
     * @param stdClass $course course record
     */
    public static function send_slot_notification(slot $slot, $messagename, $template,
                                                  stdClass $sender, stdClass $recipient,
                                                  stdClass $teacher, stdClass $student, stdClass $course) {
        $vars = self::get_scheduler_variables($slot->get_scheduler(), $slot, $teacher, $student, $course, $recipient);
        self::send_message_from_template('mod_scheduler', $messagename, 1, $sender, $recipient, $course, $template, $vars);
    }

}
