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


class NoPerlComments extends QACheckRegexp
{
    var $id = '5.1.4';
    var $name = "No perl-style comments!";
    var $fatal = false;
    var $score = 2;
    var $filetype = 'php';
    var $enabled = true;
    var $checkcomments = true;
    var $regexps = array('/^\s*#/');
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new NoPerlComments();
}
?>
