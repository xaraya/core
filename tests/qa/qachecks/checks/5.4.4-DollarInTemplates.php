<?php
/**
 * File: $Id$
 *
 * @package qachecks
 * @copyright (C) 2004 by Ninth Avenue Software Pty Ltd
 * @link http://www.ninthave.net
 * @author Roger Keays <r.keays@ninthave.net>
 * 05 May 2004
 */


/**
 * Template variables must be like #$foo#, not #foo#
 */
class DollarInTemplates extends QACheckRegexp
{
    var $id = '5.4.4';
    var $name = 'Template variables like #$foo#, not #foo#';
    var $fatal = true;
    var $score = 2;
    var $filetype = 'template';
    var $enabled = true;
    var $checkcomments = false;
    var $regexps = array('/#[^$(#]+#/');
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new DollarInTemplates();
}
?>
