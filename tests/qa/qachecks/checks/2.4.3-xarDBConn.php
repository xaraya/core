<?php
/**
 * File: $Id$
 *
 * @package qachecks
 * @author Roger Keays <r.keays@ninthave.net>
 * 05 May 2004
 */


class XarDBConn extends QACheckRegexp
{
    var $id = '2.4.3';
    var $name = "xarDBConn no longer returns an array";
    var $fatal = true;
    var $filetype = 'php';
    var $regexps = array('/list\(.*?\)\s*=\s*xarDBGetConn/');
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new XarDBConn();
}
?>
