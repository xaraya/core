<?php
/**
 * File: $Id$
 *
 * All php files start and end with <?php and ?> respectively.
 *
 * @package qachecks
 * @author Roger Keays <r.keays@ninthave.net>
 * 05 May 2004
 */


/**
 * Example QA check. We check to see if the file is smaller than 10000 bytes.
 */
class StartAndEndPHP extends QACheck
{
    var $id = '2.3.4';
    var $name = 'File begins with <?php and ends with ?>';
    var $fatal = true;
    var $filetype = 'php';
    var $enabled = true;

    /**
     * Run the test.
     */
    function execute()
    {
        $file = fopen($this->filename, 'r');
        $text = fread($file, filesize($this->filename));
        fclose($file);

        if (!preg_match('/^<\?php/', $text) || !preg_match('/\?>$/', $text)) {
            return false;
        } else {
            return true;
        }
    }
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new StartAndEndPHP();
}
?>
