<?PHP

/**
 * Version information for mod/scheduler
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This is the 2.3 branch (MOODLE_23_STABLE) of the scheduler module.
 * It is intended for use with Moodle 2.3 and above.
 */

$module->version  = 2012102803;       // The current module version (Date: YYYYMMDDXX)
$module->release  = '2.3.1+';         // Human-friendly version name
$module->requires = 2012062500;       // Requires Moodle 2.3
$module->maturity = MATURITY_STABLE;  // Stable release

$module->cron     = 60;               // Period for cron to check this module (secs)

?>
