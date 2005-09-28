<?php
/**
 * File: $Id$
 *
 * Handle css tags
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage themes module
 * @author andyv <andyv@xaraya.com>
*/
/**
 * Handle css tag
 *
 * @param $args array containing the parameters
 * @returns string
 * @return the PHP code needed to show the css tag in the BL template
 */
function themes_userapi_register($args)
{
    require_once "modules/themes/xarclass/xarcss.class.php";
    $obj = new xarCSS($args);
    return $obj->run_output();
}

?>
