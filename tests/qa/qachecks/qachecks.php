<?php
/**
 * File: $Id$
 *
 * Run the QA checks on input files.
 *
*
 * @package qachecks
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Roger Keays <r.keays@ninthave.net>
 * 05 May 2004
 */

/* local variables */
$all = false;            /* default to run all checks?         */
$verbose = false;        /* use verbose output?                */
$weave = true;           /* execute per test or per file?      */
$fatal = false;          /* has a critical check failed?       */
$checks = array();       /* array of QACheck instances         */
$regexpchecks = array(); /* array of QACheckRegexp instances   */
$run = 0;                /* number of tests run on single file */
$passed = 0;
$failed = 0;
$fatal = 0;
$totalrun = 0;           /* number of tests run on all files   */
$totalpassed = 0;
$totalfailed = 0;
$totalfatal = 0;
$basedir = dirname(__FILE__); /* base path of qachecks source  */

/* required classes */
require_once("$basedir/classes/QACheck.php");
require_once("$basedir/classes/QACheckRegexp.php");
require_once("$basedir/classes/QACheckShellCommand.php");

/* parse command line args */
$filenames = array();
$requested = array();
foreach($_SERVER['argv'] as $index => $arg) {
    if (substr($arg, 0, 9) == '--checks=') {
        $requested = split(',', substr($arg, 9));
    } else if (substr($arg, 0, 10) == '--working=') {
        chdir(substr($arg, 10));
    } else if (substr($arg, 0, 5) == '--all') {
        $all = true;
    } else if (substr($arg, 0, 7) == '--weave') {
        $weave = true;
    } else if (substr($arg, 0, 9) == '--noweave') {
        $weave = false;
    } else if (substr($arg, 0, 9) == '--verbose') {
        $verbose = true;
    } else if ($index != 0){
        $filenames[] = $arg;
    }
}


/* display help if given no files to work with */
if (empty($filenames)) {
    echo "
Usage: php qachecks.php [options] filename1 [filename2] [filename3]...

Options:
  --checks=x,y,z   comma separated list of checker ids to run
  --working=path   path to where the script is being run from
  --verbose        use verbose output - displays offending lines
  --weave          run each test on every file (default)
  --noweave        run every test on each file
  --all            default to all checks if none are listed
                   (otherwise, only the default checks are run)
";             
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

/* filter for requested checks, or default checks */
if ($all == false) {

    /* default checks */
    if (empty($requested)) {
        $requested = array(
                '2.3.4', // Start and end with PHP tag
                '2.4.1', // Deprecated / removed functions
                '2.4.2', // Legacy functions
                '2.4.3', // $dbconn assigned correctly
                '3.4.1', // no use of short php tag
                '5.1.4', // no use of perl comments
                '5.2.1', // no tabs
                '5.2.4', // use unix line endings
                '5.2.6', // one true brace
                '5.4.9', // no use of --->
                );        
    }

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

/*
 * For multiline comments we need to know how many blank lines to
 * insert. We do this with a callback function.
 */
function blank_comments($matches)
{
    /* remove everything except newlines from match */
    return preg_replace(':[^\n]:', '', $matches[0]);
}

/* load all the files into memory */
$files = array();
foreach ($filenames as $filename) {

    /* determine filetype */
    if (preg_match('/\.php$/', $filename)) {
        $filetype = 'php';
    } else if (preg_match('/\.x[dt]$/', $filename)) {
        $filetype = 'template';
    } else {
        $filetype = 'unknown';
        echo "Warning: unknown filetype\n";
    }

    /* create a version without the comments */
    $file = fopen($filename, 'r');
    $nocomments = fread($file, filesize($filename));
    fclose($file);

    if ($filetype == 'php') {

        /* line comments are easy */
        $nocomments = preg_replace(':^[ \011]*#.*$:m', "", $nocomments);
        $nocomments = preg_replace(':^[ \011]*//.*$:m', "", $nocomments);

        /* multiline comments use a callback function */
        $nocomments = preg_replace_callback(':/\*.*?\*/:s',
                "blank_comments", $nocomments);

    } else if ($filetype == 'template') {

        /* TODO: strip comments from template */
    }
    
    /* create array from uncommented version */
    $nocommentlines = split("\n", $nocomments);

    /* split always leaves an extra empty line */
    unset($nocommentlines[count($nocommentlines) - 1]);

    /* read line by line */
    $lines = file($filename);

    /* debugging 
    var_export($lines);
    var_export($nocommentlines);
    */

    /* check the line count matches */
    if (count($lines) != count($nocommentlines) &&

            /* sometimes the line count is out by 1 (maybe due to split?) */
            count($lines) - 1 != count($nocommentlines)) {

        echo "\nERROR: line count difference after removing comments. ".
             "\n(lines = ".count($lines).", but nocommentlines = ".
             count($nocommentlines). ". This is a bug, please report at".
             "\nhttp://bugs.xaraya.com/enter_bug.cgi?product=App%20-%20Core&component=QA)\n\n";
    }

    /* add to the array */
    $thisfile = array('name' => $filename,
                      'type' => $filetype,
                      'lines' => $lines,
                      'nocommentlines' => $nocommentlines);
    $files[] = $thisfile;
}

echo "
XARAYA QA CHECKS.

These QA checks are based on the Code Review Checklist (v0.2.1), found in
/tests/qa/doc.
";

/* process each file on the command line */
if ($weave == false) {

  foreach ($files as $file) {
    echo "
---------------------------------------
Checking ". $file['name']."...
---------------------------------------\n";

    /* now run each of the requested checks */
    $run = 0;
    $passed = 0;
    $failed = 0;
    $fatal = 0;
    foreach ($checks as $index => $check) {

        /* regexp checks are handled differently */
        $checks[$index]->success = true;
        if (get_parent_class($check) == 'qacheckregexp') {
            continue;
        }

        /* run the check */
        if ($check->enabled && ($check->filetype == 'all' || 
                $check->filetype == $file['type'])) {
            $result = false;
            $run++;
            $check->filename = $file['name'];

            /* results of check */
            if (!$check->execute()) {
                $failed++;
                echo "  FAILED Check ".$check->id." (".$check->name.").";
                $checks->success = false;
                if ($check->fatal) {
                    echo " (FATAL)";
                    $fatal = true;
                }
                echo "\n";
            } else {
                $passed++;
                echo "  Passed Check ".$check->id." (".$check->name.").\n";
                $check->success = true;
            }
        } /* run check? */
    } /* each checks */

    /* now run the regexp checks */
    if (!empty($regexpchecks)) {

        foreach ($file['lines'] as $number => $line) {

            /* iterate over each check */
            foreach ($regexpchecks as $index => $check) {

                /* are we running this check? */
                if ($check->enabled && ($check->filetype == 'all' || 
                        $check->filetype == $file['type'])) {

                    /* iterate each regexp in the check */
                    foreach ($check->regexps as $regexp) {

                        /* do we check commented lines? */
                        if ($check->checkcomments == true) {
                            $fail = preg_match($regexp, $line);
                        } else {
                            $fail = preg_match($regexp,
                                    $file['nocommentlines'][$number]);
                        }
                        if ($check->negate) {
                            $fail = !$fail;
                        }
                        if ($fail) {

                            /* check failed */
                            if ($regexpchecks[$index]->success) {
                                $regexpchecks[$index]->success = false;
                                echo "  FAILED Check ".$check->id." (".
                                        $check->name.") ";
                                if ($check->fatal) {
                                    echo " (FATAL)";
                                    $fatal = true;
                                }
                                echo "\n";
                            }

                            /* don't show which line its on (bug #3232) *|
                            if ($verbose) {
                                echo "    ".sprintf("%4d", $number + 1).
                                        ":  $line";
                            } else {
                                echo "     Line ". ($number + 1)."\n";
                            } /* */
                        }
                    } /* each regexp */
                } /* run check? */
            } /* each regexpcheck */
        } /* each line */

        /* report on regexp results */
        foreach ($regexpchecks as $index => $check) {

            /* did we run this check? */
            if ($check->enabled && ($check->filetype == 'all' || 
                     $check->filetype == $file['type'])) {
                $run++;
                if (!$check->success) {
                    $failed++;
                    if ($check->fatal) {
                        $fatal++;
                    }
                } else {
                    echo "  Passed Check ".$check->id." (".$check->name.").\n";
                    $passed++;
                }
            }
        } /* report on checks */
    } /* regexpchecks */

    /* report on file */
    if ($verbose) {
      echo "\n  Finished checking ". $file['name'];
      echo "
    Checks passed: $passed
    Checks failed: $failed ($fatal fatal)
    Checks run: $run\n";
    }
    $totalpassed += $passed;
    $totalfailed += $failed;
    $totalfatal += $fatal;
    $totalrun += $run;
  } /* each file */
} else { /* weave = false */

    /* if weave is true, we run every test for each file */
    foreach ($checks as $index => $check) {
        $run = 0;
        $passed = 0;
        $failed = 0;
        $fatal = 0;

        echo "
-----------------------------------------------------------
Check #". $check->id." (". $check->name.")...
-----------------------------------------------------------\n";

        /* run the check on each file */
        foreach ($files as $file) {
            $check->success = true;
            if ($check->enabled && ($check->filetype == 'all' || 
                    $check->filetype == $file['type'])) {
                $result = false;
                $run++;
                $check->filename = $file['name'];

                /* regexp checks are handled differently */
                if (get_parent_class($check) == 'qacheckregexp') {
                   foreach ($file['lines'] as $number => $line) {
         
                      /* iterate each regexp in the check */
                      foreach ($check->regexps as $regexp) {

                        /* do we check commented lines? */
                        if ($check->checkcomments == true) {
                            $fail = preg_match($regexp, $line);
                        } else {
                            $fail = preg_match($regexp,
                                    $file['nocommentlines'][$number]);
                        }
                        if ($check->negate) {
                            $fail = !$fail;
                        }
                        if ($fail) {

                            /* check failed */
                            if ($check->success) {
                                $check->success = false;
                                echo "  FAILED on ".$file['name'];
                                $failed++;
                                if ($check->fatal) {
                                    echo " (FATAL)";
                                    $fatal++;
                                }
                                echo "\n";
                            }
                            echo "    ".sprintf("%4d", $number + 1).
                                        ":  $line";
                        } /* check failed */
                      } /* each regexp in check */
                    } /* each line */

                    /* if no lines failed, the check passed */
                    if ($check->success) {
                        $passed++;
                        echo "  Pass on ".$file['name']."\n";
                    }
                    continue;
                } /* regexp checks */

                /* results of check */
                if (!$check->execute()) {
                    $failed++;
                    echo "  FAILED on ".$file['name'];
                    $checks[$index]->success = false;
                    if ($check->fatal) {
                        echo " (FATAL)";
                        $fatal = true;
                    }
                    echo "\n";
                } else {
                    $passed++;
                    echo "  Pass on ".$file['name']."\n";
                }
            } /* run check? */
        } /* each file */

        /* report on check */
        if ($verbose) {
            echo "\n  Finished check #". $check->id;
            echo "
    Files passed: $passed
    Files failed: $failed ($fatal fatal)
    Files checked: $run\n";
        }
        $totalpassed += $passed;
        $totalfailed += $failed;
        $totalfatal += $fatal;
        $totalrun += $run;

    } /* each checks */
} /* weave? */

/* check for fatal errors */
echo "\nTotal checks passed: $totalpassed\n";
echo "Total checks failed: $totalfailed ($totalfatal fatal)\n";
echo "Total checks run: $totalrun\n";
if ($fatal) {
    echo "\nOne or more fatal errors occurred.".(($verbose) ? "" :
            " Use --verbose for more detail.") . "\n\n";
    exit(1);
} else {
    echo "\n";
    exit(0);
}
?>
