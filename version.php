<?PHP

/**
 * Version information for mod/scheduler
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This is the development branch (master) of the scheduler module.
 */

$module->component = 'mod_scheduler'; // Full name of the plugin (used for diagnostics)
$module->version   = 2014032400;        // The current module version (Date: YYYYMMDDXX)
$module->release   = '2.x dev';        // Human-friendly version name
$module->requires  = 2013111800;        // Requires Moodle 2.6
$module->maturity  = MATURITY_ALPHA;    // Alpha development code - not for production sites

$module->cron     = 60;               // Period for cron to check this module (secs)

?>
