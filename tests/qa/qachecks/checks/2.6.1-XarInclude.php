<?php
/**
 * File: $Id$
 *
 * @package qachecks
 * @author Roger Keays <r.keays@ninthave.net>
 * 05 May 2004
 */


class XarInclude extends QACheckRegexp
{
    var $id = '2.6.1';
    var $name = "Use xarInclude in preference of the php equivalent";
    var $fatal = false;
    var $filetype = 'php';
    var $regexps = array('/(.*;)?\s*include/');
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new XarInclude();
}
?>
