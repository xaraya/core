<?php
/**
 * File: $Id$
 *
 * @package qachecks
 * @copyright (C) 2004 by Roger Keays and the Digital Development Foundation Inc
 * @link http://www.ninthave.net
 * @author Roger Keays <r.keays@ninthave.net>
 * 05 May 2004
 */


class UnixLineEndings extends QACheckRegexp
{
    var $id = '5.2.4';
    var $name = "Use unix line endings";
    var $fatal = true;
    var $filetype = 'all';
    var $enabled = true;
    var $checkcomments = true;
    var $regexps = array('/$/');
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new UnixLineEndings();
}
?>
