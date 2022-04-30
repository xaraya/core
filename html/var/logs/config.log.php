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
 * These values let you define the configuration for Xaraya's loggers
 * 
 * You can change these values to suit your needs, but all of them need to be included
 *
 * You can remove this file, at which point Xaraya will fall back to default values that correspond 
 * to the initial ones in this file, and attempt to recreate this file.
 *
 * The fileName setting can be overridden in the modifyconfig page of the base module.
 */

$config[] = array(
    'type' => 'simple',
    'config' => array(
    	'fileName' => "xarayalog.txt",  // The name of the logfile
    	'maxFileSize'  => "5000000",    // The maximum size of a log file in bytes. Once this value is reached Xaraya creates a new log file
    	'mode'  => "0644",              // Integer containing the logfile's permissions mode.
	)
);
// Add further loggers here
/*
$config[] = array(
    'type' => 'html',
    'config' => array(
    	'fileName' => "xarayalog.txt",  // The name of the logfile
    	'maxFileSize'  => "5000000",    // The maximum size of a log file in bytes. Once this value is reached Xaraya creates a new log file
    	'mode'  => "0644",              // Integer containing the logfile's permissions mode.
	)
);
*/
?>