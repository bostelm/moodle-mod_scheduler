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
 * This is the MOODLE_29_STABLE branch of the scheduler module.
 */

$plugin->component = 'mod_scheduler'; // Full name of the plugin (used for diagnostics)
$plugin->version   = 2015102904;      // The current module version (Date: YYYYMMDDXX)
$plugin->release   = '2.9.2';         // Human-friendly version name
$plugin->requires  = 2015042800;      // Requires Moodle 2.9
$plugin->maturity  = MATURITY_STABLE; // Stable release

$plugin->cron     = 60;               // Period for cron to check this module (secs)
