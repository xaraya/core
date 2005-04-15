<?php

/**
 * Instead of triggering the scheduler by retrieving the web page
 * index.php?module=scheduler or by using a trigger block on your
 * site, you can also execute this script directly using the PHP
 * command line interface (CLI) : php run_scheduler.php
 */

// CHECKME: change this to your Xaraya html directory !
    $homedir = 'd:/backup/xaraya/html';

    if (!chdir($homedir)) {
        die('Please check that the $homedir variable in this script is set to your Xaraya html directory');
    }

    // initialize the Xaraya core
    include 'includes/xarCore.php';
    xarCoreInit(XARCORE_SYSTEM_ALL);

    // update the last run time
    xarModSetVar('scheduler','lastrun',time());
    xarModSetVar('scheduler','running',1);

    // call the API function to run the jobs
    echo xarModAPIFunc('scheduler','user','runjobs');

?>
