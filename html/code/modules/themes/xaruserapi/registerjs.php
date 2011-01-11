<?php
/**
 * Xaraya JavaScript class library
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
function themes_userapi_registerjs($args)
{
    extract($args);
    
    if (empty($code) && empty($filename)) return;
    if (empty($position)) $args['position'] = 'head';
    if (empty($index)) $args['index'] = null;
    
    sys::import('modules.themes.class.xarjs');
    $javascript = xarJS::getInstance();
    return $javascript->register($args);           
}
?>