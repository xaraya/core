<?php
/**
 * File: $Id$
 *
 * Base class for regexp QA checks.
 *
 * @package qachecks
 * @copyright (C) 2004 by Ninth Avenue Software Pty Ltd
 * @link http://www.ninthave.net
 * @author Roger Keays <r.keays@ninthave.net>
 * 05 May 2004
 */


/**
 * This class is especially for QA checks which use the return value from a
 * shell command.
 */
class QACheckShellCommand extends QACheck
{

    /**
     * Execute the shell command.
     */
    function execute()
    {
        $result = 1;
        system($this->getCommand(), $result); 
        return ($result > 0) ? false : true;
    }
} 
?>
