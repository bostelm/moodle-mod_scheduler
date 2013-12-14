<?PHP

/**
 * Version information for mod/scheduler
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2013 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This is the Moodle 2.5 branch of the scheduler module.
 */

$module->version  = 2013092703;       // The current module version (Date: YYYYMMDDXX)
$module->release  = '2.5.1+';         // Human-friendly version name
$module->requires = 2013051400;       // Requires Moodle 2.5
$module->maturity = MATURITY_STABLE;  // Stable branch

$module->cron     = 60;               // Period for cron to check this module (secs)

?>
