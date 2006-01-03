<?php
/**
 * Get all items
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 */
/**
 * Get all items
 * @author mikespub <mikespub@xaraya.com>
*/

function dynamicdata_userapi_getall($args)
{
    return xarModAPIFunc('dynamicdata','user','getitem',$args);
}

?>
