<?php
/**
 * Handle css tag
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
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
    sys::import('modules.themes.xarclass.xarcss');
    $obj = new xarCSS($args);
    $styles = $obj->run_output();
    return xarTplModule('themes','user','additionalstyles',$styles);
}

?>
