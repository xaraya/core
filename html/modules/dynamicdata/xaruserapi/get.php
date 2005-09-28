<?php
/**
 * File: $Id$
 *
 * Dynamic Data Version Information
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/
function dynamicdata_userapi_get($args)
{
    return xarModAPIFunc('dynamicdata','user','getfield',$args);
}


?>