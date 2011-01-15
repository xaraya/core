<?php
/**
 * Handle place-css tag
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
 * Handle place-css tag
 *
 * @author andyv <andyv@xaraya.com>
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @params array   $args array of optional paramaters<br/>
 *         array   $args[scopeorder] order to render scopes, optional<br/>
 *                 default scope rendering order common->theme->module->block->property<br/>
 *         boolean $args[comments] show comments, optional, default false
 * @todo support targetting combination of scope and/or method
 *         string  $args[scope] scope to render, optional, default render all 
 *         string  $args[method] method to render, optional, default all
 *                 default method rendering order link->import->embed
 * @todo option to turn on/off style comments in UI, cfr template comments
 * @return string templated output of css to render
 * @throws none
**/
function themes_userapi_deliver(Array $args=array())
{
    sys::import('modules.themes.class.xarcss');
    $css = xarCSS::getInstance();
    return $css->render($args);
    /*
    sys::import('modules.themes.class.xarcssold');
    $obj = new xarCSS($args);
    $styles = $obj->run_output();
    return xarTplModule('themes','user','additionalstyles',$styles).$css->render($args);;
    */
}

?>
