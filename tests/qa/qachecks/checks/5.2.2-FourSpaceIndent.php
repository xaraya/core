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


class FourSpaceIndent extends QACheckRegexp
{
    var $id = '5.2.2';
    var $name = "Indents are multiples of four spaces";
    var $fatal = true;
    var $score = 2;
    var $filetype = 'all';
    var $enabled = true;
    var $checkcomments = true;

    /*
     * lines must start with multiple of four spaces followed by non-space, or
     * ' *' for the middle of a multiline comment.
     */
    var $regexps = array('/^( {4})*([^ ]| \*)/');
    var $negate = true;
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new FourSpaceIndent();
}
?>
