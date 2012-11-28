<?php

/**
 * E-mail formatting from templates.
 * 
 * @package    mod
 * @subpackage scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
* Gets the content of an e-mail from language strings
* 
* Looks for the language string email_$template_$format and replaces the parameter values.
* 
* @param template the template's identified
* @param string $format tthe mail format ('subject', 'html' or 'plain') 
* @param infomap a hash containing pairs of parm => data to replace in template
* @return a fully resolved template where all data has been injected
*/
function scheduler_compile_mail_template($template, $format, $infomap, $module = 'scheduler') {
	$params = array();
	foreach ($infomap as $key=>$value) {
	    $params[strtolower($key)] = $value;
	}
	$mailstr = get_string( "email_{$template}_{$format}", $module, $params);
    return $mailstr;
}


/**
 * Sends an e-mail based on a template. 
 * Several template substitution values are automatically filled by this routine.
 *  
 * @uses $CFG 
 * @uses $SITE
 * @param user $recipient A {@link $USER} object describing the recipient
 * @param user $sender A {@link $USER} object describing the sender
 * @param object $course The course that the activity is in. Can be null.
 * @param string $title the identifier for the e-mail subject. 
 *        Value can include one parameter, which will be substituted 
 *        with the course shortname. 
 * @param string $template the virtual mail template name (without "_html" part)
 * @param array $infomap a hash containing pairs of parm => data to replace in template
 * @param string $modulename the current module
 * @param string $lang language to be used, if default language must be overriden
 * @return bool|string Returns "true" if mail was sent OK, "emailstop" if email
 *         was blocked by user and "false" if there was another sort of error.
 */
function scheduler_send_email_from_template($recipient, $sender, $course, $title, $template, $infomap, $modulename , $lang = '') {
    
    global $CFG;
    global $SITE;

    $defaultvars = array(
        'SITE' => $SITE->fullname,
        'SITE_SHORT' => $SITE->shortname,
        'SITE_URL' => $CFG->wwwroot,
        'SENDER'  => fullname($sender),
        'RECIPIENT'  => fullname($recipient)
    );

    $subjectPrefix = $SITE->shortname;
    
    if ($course) {
        $subjectPrefix = $course->shortname;
        $defaultvars['COURSE_SHORT'] = $course->shortname;
        $defaultvars['COURSE']       = $course->fullname;
        $defaultvars['COURSE_URL']   = $CFG->wwwroot.'/course/view.php?id='.$course->id;
    }
    
    $vars = array_merge($defaultvars,$infomap);
    
    $subject = scheduler_compile_mail_template($template, 'subject', $vars, $modulename);    
    $plainMail = scheduler_compile_mail_template($template, 'plain', $vars, $modulename);
    $htmlMail = scheduler_compile_mail_template($template, 'html', $vars, $modulename);
    
    $res = email_to_user ($recipient, $sender, $subject, $plainMail, $htmlMail); 
    return $res;
}

?>
