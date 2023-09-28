<?php
/**
 * Xaraya JavaScript class library
 *
 * @package modules\themes
 * @subpackage themes
 * @copyright see the html/credits.html file in this release
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/70.html
**/
/**
 * Renderjs function
 *
 * Render queued javascript
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @param array<string, mixed> $args array of optional parameters<br/>
 *        string  $args[position] position to render, optional<br/>
 *        string  $args[index] index to render, optional<br/>
 *        string  $args[type] type to render, optional
 * @return string templated output of js to render
**/    
function themes_userapi_renderjs($args)
{    
    sys::import('modules.themes.class.xarjs');
    $javascript = xarJS::getInstance();
    return $javascript->render($args);           
}
