<?php
/**
 * File: $Id$
 *
 * Check use of = vs == in comparisons.
 *
 * @package qachecks
 * @copyright (C) 2004 by Ninth Avenue Software Pty Ltd
 * @link http://www.ninthave.net
 * @author Roger Keays <r.keays@ninthave.net>
 * 05 May 2004
 */


/**
 * Check use of = vs == in comparisons.
 */
class EqualEqual extends QACheckRegexp
{
    var $id = '3.1.2';
    var $name = "Check use of = vs == in comparisons.";
    var $fatal = false;
    var $score = 2;
    var $filetype = 'php';
    var $enabled = true;
    var $checkcomments = false;

    /*
     * An equals inbetween brackets which is not ==, =>, =<, != 
     * and not a part of a function or for statement
     */
    var $regexps = array('/^([^f]|f(?!or|unction))*\([^)]*[^=!>]=[^=><][^)]*\).*$/');

    /*
     * note: different ways to negate a substring using regexps (see
     * http://mini.net/cgi-bin/wikit/989.html)
     */
    //var $regexps = array('/^([^f]|f[^o]|fo[^r])*.{0,2}$/');
    //var $regexps = array('/^([^f]|f(?!or))*$/');
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new EqualEqual();
}
?>
