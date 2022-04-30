<?php
/**
 * Log Configuration File 
 *
 * @package core
 * @subpackage core
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

/*
 * These values define the configuration for Xaraya's loggers
 * 
 * You can change these values to suit your needs, but all of them need to be included
 *
 * Most of these settings can be updated dynamically in the modifyconfig page of the base module.
 *
 * You should not remove this file!
 */

// Simple logger
$systemConfiguration['Log.Simple.Filename'] = 'xarayalog.txt'; // The name of the logfile of this logger
$systemConfiguration['Log.Simple.Level'] = 's:1:"1";';          // The levels of messages this logger tracks.
$systemConfiguration['Log.Simple.MaxFileSize'] = '50000000';    // The maximum size of a log file in bytes. Once this value is reached Xaraya creates a new log file
$systemConfiguration['Log.Simple.Mode'] = '';              // Integer containing the logfile's permissions mode.

// Email logger
$systemConfiguration['Log.Mail.Recipient'] = 'xarayalog.txt'; // The name of the recipient that this logger sends emailds to
$systemConfiguration['Log.Mail.Level'] = 's:1:"1";';          // The levels of messages this logger tracks.

// Add further loggers here
// HTML logger
/*
$configuration['Log.HTML.Filename'] = 'xarayalog.txt';   // The name of the logfile
$configuration['Log.HTML.MaxFileSize'] = '5000000';      // The maximum size of a log file in bytes. Once this value is reached Xaraya creates a new log file
$configuration['Log.HTML.Mode'] = '0644';                // Integer containing the logfile's permissions mode.
);
*/
?>