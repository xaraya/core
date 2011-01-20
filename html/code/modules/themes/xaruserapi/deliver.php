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
 *         boolean $args[comments] show comments, optional, default false
 * @todo option to turn on/off style comments in UI, cfr template comments
 * @return string templated output of css to render
 * @throws none
**/
function themes_userapi_deliver(Array $args=array())
{
    sys::import('modules.themes.class.xarcss');
    $css = xarCSS::getInstance();
    return $css->render($args);
}
?>