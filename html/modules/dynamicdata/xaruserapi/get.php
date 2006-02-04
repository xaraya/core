<?php
/**
 * Dynamic Data Version Information
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 */
/**
 * Dynamic Data Version Information
 *
 * @author mikespub <mikespub@xaraya.com>
*/
function dynamicdata_userapi_get($args)
{
    return xarModAPIFunc('dynamicdata','user','getfield',$args);
}


?>