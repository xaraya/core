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
class GoodPHPTags extends QACheckRegexp
{
    var $id = '3.4.1';
    var $name = 'Use <?php in preference to <?';
    var $fatal = true;
    var $filetype = 'php';
    var $enabled = true;
    var $checkcomments = true;
    var $regexps = array('/<\?=|<\?(?!php|xml|xar)/');
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new GoodPHPTags();
}
?>
