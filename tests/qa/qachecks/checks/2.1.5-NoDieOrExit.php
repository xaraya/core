<?php
/**
 * File: $Id$
 *
 * No use of die() or exit()
 *
 * @package qachecks
 * @author Roger Keays <r.keays@ninthave.net>
 * 05 May 2004
 */


class NoDieOrExit extends QACheckRegexp
{
    var $id = '2.1.5';
    var $name = "No use of die() or exit()";
    var $fatal = true;
    var $filetype = 'php';
    var $enabled = true;
    var $regexps = array('/die\(/', '/exit\(/');
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new NoDieOrExit();
}
?>
