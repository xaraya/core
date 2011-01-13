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
/**
 * Registerjs function
 *
 * Register javascript in the queue for later rendering
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @param  array   $args array of optional parameters<br/>
 *         string  $args[type] type of js to include, either src or code, optional, default src<br/>
 *         string  $args[code] code to include if $type is code<br/>
 *         mixed   $args[filename] array containing filename(s) or string comma delimited list
 *                 name of file(s) to include, required if $type is src, or<br/> 
 *                 file(s) to get contents from if $type is code and $code isn't supplied<br/>
 *         string  $args[module] name of module to look for file(s) in, optional, default current module<br/>
 *         string  $args[position] position to render the js, eg head or body, optional, default head<br/>
 *         string  $args[index] optional index in queue relative to other scripts<br/>
 * @return boolean true on success
 * @throws none
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