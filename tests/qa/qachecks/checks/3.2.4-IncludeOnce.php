<?php
/**
 * File: $Id$
 *
 * @package qachecks
 * @author Roger Keays <r.keays@ninthave.net>
 * 05 May 2004
 */


class IncludeOnce extends QACheckRegexp
{
    var $id = '3.2.4';
    var $name = "Use include_once in preference to include";
    var $fatal = false;
    var $filetype = 'php';
    var $regexps = array('/(.*;)?\s*include(?!_once)/',
            '/(.*;)?\s*require(?!_once)/');
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new IncludeOnce();
}
?>
