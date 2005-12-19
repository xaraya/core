<?php
/**
 * Return a newCurl object
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
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
