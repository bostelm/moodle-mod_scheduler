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
 * This is the Moodle 2.7 branch of the scheduler module.
 */

$plugin->component = 'mod_scheduler'; // Full name of the plugin (used for diagnostics)
$plugin->version   = 2014090100;      // The current module version (Date: YYYYMMDDXX)
$plugin->release   = '2.7.0';         // Human-friendly version name
$plugin->requires  = 2014050600;      // Requires Moodle 2.7
$plugin->maturity  = MATURITY_STABLE; // Alpha development code - not for production sites

$plugin->cron     = 60;               // Period for cron to check this module (secs)
