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
 * Don't use <?php in templates.
 */
class NoPHPInTemplates extends QACheckRegexp
{
    var $id = '5.4.2';
    var $name = 'Dont use php in templates';
    var $fatal = true;
    var $score = 2;
    var $filetype = 'template';
    var $enabled = true;
    var $checkcomments = true;
    var $regexps = array('/<\?php|<\?=|<\?[^x]/');
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new NoPHPInTemplates();
}
?>
