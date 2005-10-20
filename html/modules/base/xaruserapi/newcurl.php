<?php

/*
 * Return a new xarCurl object.
 * $args are passed directly to the class.
 */

function base_userapi_newcurl($args) 
{
    include_once 'modules/base/xarclass/xarCurl.php';

    return new xarCurl($args);
}

?>