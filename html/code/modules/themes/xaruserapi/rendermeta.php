<?php
/**
 * Xaraya Meta class library
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
**/
/**
 * Render meta function
 *
 * Render queued meta tags
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @param array   $args array of optional parameters (todo)
 * @return string templated output of meta tags to render
 * @throws none
**/    
function themes_userapi_rendermeta($args)
{    
    sys::import('modules.themes.class.xarmeta');
    $meta = xarMeta::getInstance();
    return $meta->render($args);           
}
?>