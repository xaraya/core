<?php
/**
 * Handle css tag
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/**
 * Handle css tag
 *
 * @author andyv <andyv@xaraya.com>
 * @param $args array containing the parameters
 * @returns string
 * @return the PHP code needed to show the css tag in the BL template
 */
function themes_userapi_deliver($args)
{
    require_once "modules/themes/xarclass/xarcss.class.php";
    $obj = new xarCSS($args);
    $styles = $obj->run_output();
    return xarTplModule('themes','user','additionalstyles',$styles);
}

?>
