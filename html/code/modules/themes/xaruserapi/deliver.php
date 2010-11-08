<?php
/**
 * Handle css tag
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Handle css tag
 *
 * @author andyv <andyv@xaraya.com>
 * @param $args array containing the parameters/**
 * @return string output display string
 */
function themes_userapi_deliver($args)
{
    sys::import('modules.themes.class.xarcss');
    $obj = new xarCSS($args);
    $styles = $obj->run_output();
    return xarTplModule('themes','user','additionalstyles',$styles);
}

?>
