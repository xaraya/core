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


class NoBadCommentTags extends QACheckRegExp
{
    var $id = '5.4.9';
    var $name = "No ---> tags";
    var $fatal = true;
    var $filetype = 'template';
    var $enabled = true;
    var $regexps = array('/--->/');
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new NoBadCommentTags();
}
?>
