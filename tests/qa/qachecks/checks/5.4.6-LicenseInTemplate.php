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


class LicenseInTemplate extends QACheckShellCommand
{
    var $id = '5.4.6';
    var $name = "License included in template";
    var $fatal = false;
    var $filetype = 'template';
    var $enabled = true;

    /* create the command line string */
    function getCommand()
    {
        return 'grep "<!--- License" ' .escapeshellarg($this->filename);
    }
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new LicenseInTemplate();
}
?>
