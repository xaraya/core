<?php
/**
 * File: $Id$
 *
 * Example of a QA Regexp checker.
 *
 * @package qachecks
 * @copyright (C) 2004 by Roger Keays and the Digital Development Foundation Inc
 * @link http://www.ninthave.net
 * @author Roger Keays <r.keays@ninthave.net>
 * 05 May 2004
 */


/**
 * Example checker. Looks for the word 'Xaraya' in the input. 
 */
class QACheckRegexpExample extends QACheckRegexp
{
    var $id = '0.0.2';
    var $name = "Example Regexp Check - file does not contains 'Xaraya'";
    var $fatal = true;
    var $score = 2;
    var $filetype = 'all';

    /* this test is disabled */
    var $enabled = false;
    var $checkcomments = true;

    /* any lines which match the regexp will be considered a failure */
    var $regexps = array('/Xaraya/');

    /* set this to true to fail on lines that DO NOT contain 'Xaraya' */
    var $negate = false;
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new QACheckRegexpExample();
}
?>
