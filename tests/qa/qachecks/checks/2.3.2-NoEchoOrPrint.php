<?php
/**
 * File: $Id$
 *
 * No use of echo() or print()
 *
 * @package qachecks
 * @author Roger Keays <r.keays@ninthave.net>
 * 05 May 2004
 */


class NoEchoOrPrint extends QACheckRegexp
{
    var $id = '2.1.5';
    var $name = "No use of echo() or print()";
    var $fatal = true;
    var $filetype = 'php';
    var $enabled = true;
    var $regexps = array('/echo/', '/print/');
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new NoEchoOrPrint();
}
?>
