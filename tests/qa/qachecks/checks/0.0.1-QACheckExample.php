<?php
/**
 * File: $Id$
 *
 * Example of a QA Check.
 *
 * @package qachecks
 * @copyright (C) 2004 by Ninth Avenue Software Pty Ltd
 * @link http://www.ninthave.net
 * @author Roger Keays <r.keays@ninthave.net>
 * 05 May 2004
 */


/**
 * Example QA check. We check to see if the file is smaller than 10000 bytes.
 */
class QACheckExample extends QACheck
{
    var $id = '0.0.1';
    var $name = 'Example Check - files is < 10000 bytes';
    var $fatal = false;
    var $score = 1;
    var $filetype = 'php';
    var $enabled = true;

    /**
     * Run the test.
     *
     * @return true if the file is less than 10000 bytes, false otherwise
     */
    function execute()
    {
        if (filesize($this->filename) < 10000) {
            return true;
        } else {
            return false;
        }
    }
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new QACheckExample();
}
?>
