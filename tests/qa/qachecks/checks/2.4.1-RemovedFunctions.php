<?php
/**
 * File: $Id$
 *
 * These functions have been removed or are flagged for removal from the code
 * entirely.
 *
 * @package qachecks
 * @author Roger Keays <r.keays@ninthave.net>
 * 05 May 2004
 */


class RemovedFunctions extends QACheckRegexp
{
    var $id = '2.4.1';
    var $name = "Removed Functions";
    var $fatal = true;
    var $filetype = 'all';
    var $regexps = array(
        '/xarTpl_JavaScript/'
    );
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new RemovedFunctions();
}
?>
