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


/**
 * Grep for fixme/todo/checkme
 */
class NoFixMeTodo extends QACheckRegexp
{
    var $id = '3.3.2';
    var $name = "Check for FIXME / TODO / CHECKME";
    var $fatal = false;
    var $score = 1;
    var $filetype = 'all';
    var $enabled = true;
    var $checkcomments = true;
    var $regexps = array('/fixme|todo|checkme/i');
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new NoFixMeTodo();
}
?>
