<?php
/**
 * File: $Id$
 *
 * Run the QA checks on input files.
 *
 * Usage: php qachecks.php [options] filename1 [filename2] [filename3]...
 *
 * Options:
 *   --checks=x,y,z   comma separated list of checker ids to run
 *
 * @package qachecks
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Roger Keays <r.keays@ninthave.net>
 * 05 May 2004
 */

/* local variables */
$fatal = false;          /* has a critical check failed?       */
$checks = array();       /* array of QACheck instances         */
$regexpchecks = array(); /* array of QACheckRegexp instances   */
$run = 0;                /* number of tests run on single file */
$passed = 0;
$failed = 0;
$totalrun = 0;           /* number of tests run on all files   */
$totalpassed = 0;
$totalfailed = 0;
$basedir = dirname(__FILE__); /* base path of qachecks source  */

/* required classes */
require_once("$basedir/classes/QACheck.php");
require_once("$basedir/classes/QACheckRegexp.php");

/* parse command line args */
$filenames = array();
$requested = array();
foreach($_SERVER['argv'] as $index => $arg) {
    if (substr($arg, 0, 9) == '--checks=') {
        $requested = split(',', substr($arg, 9));
    } else if ($index != 0){
        $filenames[] = $arg;
    }
}

/* failure conditions */
if (empty($filenames)) {
    echo "No files provided!\n";
    exit(1);
}

/* all qachecks expected to be in checks subdirectory */
$dir = opendir($basedir."/checks");
if (!$dir) {
    echo "Couldn't open $basedir/checks!\n";
    exit(1);
}
$filelist = array();
while (($file = readdir($dir)) !== false) {
    if (preg_match('/\.php$/', $file)) {
        $filelist[] = $file;
    }
}
asort($filelist);
foreach($filelist as $file) {
    include_once "$basedir/checks/$file";
}

/* no requested checks means all checks */
if (!empty($requested)) {

    /* filter checks to only those that were requested */
    foreach ($checks as $index => $check) {
        if (!in_array($check->id, $requested)) {
            unset($checks[$index]);
        }

    }
} /* filter checks */

/* references to regexp checks */
foreach ($checks as $index => $check) {
    if (get_parent_class($check) == 'qacheckregexp') {
        $regexpchecks[] =& $checks[$index];
    }
}

/* process each file on the command line */
foreach ($filenames as $filename) {
    echo "
---------------------------------------
Checking $filename...
---------------------------------------\n";

    /* determine filetype */
    if (preg_match('/\.php$/', $filename)) {
        $filetype = 'php';
    } else if (preg_match('/\.x[dt]$/', $filename)) {
        $filetype = 'template';
    } else {
        $filetype = 'unknown';
        echo "Warning: unknown filetype\n";
    }

    /* now run each of the requested checks */
    $run = 0;
    $passed = 0;
    $failed = 0;
    foreach ($checks as $index => $check) {

        /* regexp checks are handled differently */
        $checks[$index]->success = true;
        if (get_parent_class($check) == 'qacheckregexp') {
            continue;
        }

        /* run the check */
        if ($check->enabled && ($check->filetype == 'all' || 
                $check->filetype == $filetype)) {
            $run++;
            $check->filename = $filename;
            if (!$check->execute()) {
                $failed++;
                echo "  Check ".$check->id." (".$check->name.") FAILED.";
                $checks->success = false;
                if ($check->fatal) {
                    echo " (FATAL)";
                    $fatal = true;
                }
                echo "\n";
            } else {
                $passed++;
                echo "  Check ".$check->id." (".$check->name.") passed.\n";
                $check->success = true;
            }
        } /* run check? */
    } /* each checks */

    /* now run the regexp checks */
    if (!empty($regexpchecks)) {

        /* read line by line */
        $lines = file($filename);
        foreach ($lines as $number => $line) {

            /* iterate over each check */
            foreach ($regexpchecks as $index => $check) {

                /* are we running this check? */
                if ($check->enabled && ($check->filetype == 'all' || 
                        $check->filetype == $filetype)) {

                    /* iterate each regexp in the check */
                    foreach ($check->regexps as $regexp) {
                        if (preg_match($regexp, $line)) {

                            /* check failed */
                            $regexpchecks[$index]->success = false;
                            $regexpchecks[$index]->failedlines[] = $number;
                            echo "  Check ".$check->id." (".$check->name.") ".
                                    "FAILED";
                            if ($check->fatal) {
                                echo " (FATAL)";
                                $fatal = true;
                            }
                            echo "\n    ".($number + 1).":  $line\n";
                        }
                    } /* each regexp */
                } /* run check? */
            } /* each regexpcheck */
        } /* each line */

        /* report on regexp results */
        foreach ($regexpchecks as $index => $check) {

            /* did we running this check? */
            if ($check->enabled && ($check->filetype == 'all' || 
                     $check->filetype == $filetype)) {
                $run++;
                if (!$check->success) {
                    $failed++;
                } else {
                    echo "  Check ".$check->id." (".$check->name.") passed.\n";
                    $passed++;
                }
            }
        } /* report on checks */
    } /* regexpchecks */

    /* report on file */
    echo "\nFinished checking $filename\n";
    echo "Checks passed: $passed\nChecks failed: $failed\nChecks run: $run\n";
    $totalpassed += $passed;
    $totalfailed += $failed;
    $totalrun += $run;
} /* each file */

/* check for fatal errors */
echo "\nTotal checks passed: $totalpassed\n";
echo "Total checks failed: $totalfailed\n";
echo "Total checks run: $totalrun\n";
if ($fatal) {
    echo "\nOne or more fatal errors occurred.\n\n";
    exit(1);
} else {
    echo "\n";
    exit(0);
}
?>
