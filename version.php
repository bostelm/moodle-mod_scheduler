<?PHP // $Id: version.php,v 1.4.10.9 2009-06-24 23:04:21 diml Exp $

/**
 * Version information for mod/scheduler
 * 
 * @package    mod
 * @subpackage scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


$module->version  = 2011081901;  // The current module version (Date: YYYYMMDDXX)
$module->requires = 2011033000;  // Requires Moodle 2.0
$module->cron     = 60;          // Period for cron to check this module (secs)

?>
