<?php
/**
 * File: $Id$
 *
 * Example of a QA shell command checker.
 *
 * @package qachecks
 * @copyright (C) 2004 by Roger Keays and the Digital Development Foundation Inc
 * @link http://www.ninthave.net
 * @author Roger Keays <r.keays@ninthave.net>
 * 05 May 2004
 */


/**
 * Example checker. Grep for the word 'DDF' in the input. 
 */
class QACheckShellCommandExample extends QACheckShellCommand
{
    var $id = '0.0.3';
    var $name = "Example Shell command - grep for 'DDF'";
    var $fatal = true;
    var $score = 2;
    var $filetype = 'all';

    /* this test is disabled */
    var $enabled = false;

    /* create the command line string */
    function getCommand()
    {
        return 'grep "DDF" ' .escapeshellarg($this->filename);
    }
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new QACheckShellCommandExample();
}
?>
