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
 * You can change these values to suit your needs, but all of them need to be included.
 * Alternatively, you can configure the loggers using the LogConfig module.
 *
 * You should not remove this file!
 */

// Mail logger
$systemConfiguration['Log.Mail.Level'] = '1,2,4,8';                        // The levels of messages this logger tracks.
$systemConfiguration['Log.Mail.Timeformat'] = '%Y/%m/%d %H:%M:%S';         // String containing the format for the log's timestamps.
$systemConfiguration['Log.Mail.Recipient'] = 'noone@no.comxarayalog.txt';  // The recipient that this logger sends emails to
$systemConfiguration['Log.Mail.Sender'] = 'Xaraya Logger';                 // The sender that this logger uses to send emails
$systemConfiguration['Log.Mail.Subject'] = 'Log Message';                  // The subject of the emails

// HTML logger
$systemConfiguration['Log.Html.Level'] = '1,2,4,8';                        // The levels of messages this logger tracks.
$systemConfiguration['Log.Html.Timeformat'] = '%Y/%m/%d %H:%M:%S';         // String containing the format for the log's timestamps.
$systemConfiguration['Log.Html.Filename'] = 'htmllog.html';                // The name of the logfile of this logger
$systemConfiguration['Log.Html.MaxFileSize'] = '50000000';                 // The maximum size of a log file in bytes. Once this value is reached Xaraya creates a new log file
$systemConfiguration['Log.Html.Mode'] = '644';                             // Integer containing the logfile's permissions mode.

// Javascript logger
$systemConfiguration['Log.Html.Level'] = '1,2,4,8';                        // The levels of messages this logger tracks.
$systemConfiguration['Log.Html.Timeformat'] = '%Y/%m/%d %H:%M:%S';         // String containing the format for the log's timestamps.

// Mozilla logger
$systemConfiguration['Log.Html.Level'] = '1,2,4,8';                        // The levels of messages this logger tracks.
$systemConfiguration['Log.Html.Timeformat'] = '%Y/%m/%d %H:%M:%S';         // String containing the format for the log's timestamps.

// Simple logger
$systemConfiguration['Log.Simple.Filename'] = 'simplelog.txt1';            // The name of the logfile of this logger
$systemConfiguration['Log.Simple.Level'] = '1,2,4,8';                      // The levels of messages this logger tracks.
$systemConfiguration['Log.Simple.MaxFileSize'] = '50000000';               // The maximum size of a log file in bytes. Once this value is reached Xaraya creates a new log file
$systemConfiguration['Log.Simple.Mode'] = '644';                           // Integer containing the logfile's permissions mode.
$systemConfiguration['Log.Simple.Timeformat'] = '%Y/%m/%d %H:%M:%S';              // String containing the format for the log's timestamps.

// SQL logger
$systemConfiguration['Log.Sql.Level'] = '1,2,4,8';                         // The levels of messages this logger tracks.
$systemConfiguration['Log.Sql.Timeformat'] = '%Y/%m/%d %H:%M:%S';          // String containing the format for the log's timestamps.
$systemConfiguration['Log.Sql.SQLTable'] = 'log_messages';                 // The name of the SQL table of this logger

// Syslog logger
$systemConfiguration['Log.Syslog.Level'] = '1,2,4,8';                      // The levels of messages this logger tracks.
$systemConfiguration['Log.Syslog.Timeformat'] = '%Y/%m/%d %H:%M:%S';       // String containing the format for the log's timestamps.
$systemConfiguration['Log.Syslog.Facility'] = 'syslog.txt';                // The name of the logfile of this logger
$systemConfiguration['Log.Syslog.Options'] = '50000000';                   // The maximum size of a log file in bytes. Once this value is reached Xaraya creates a new log file


?>