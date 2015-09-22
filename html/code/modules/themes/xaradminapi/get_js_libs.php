<?php
/**
 * @package modules
 * @subpackage themes module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info 
 */

function themes_adminapi_get_js_libs(Array $args=array())
{
    sys::import('modules.themes.class.xarjs');
    $instance = xarJS::getInstance();
    if (empty($args['scope']) || ($args['scope'] == 'local')) {
        $args['scope'] = 'local_libs';
    } else {
        $args['scope'] = 'remote_libs';
    }
    
    // Retrieve the libraries of the chosen scope: local or remote
    $libs = $instance->$args['scope'];
    
    // If we have a specific lib we are looking for, then filter
    if (!empty($args['lib'])) {
        $result = array();
        foreach ($libs as $lib) {
            if ($lib['lib'] == $args['lib']) $result[] = $lib;
        }
    } else {
        $result = $libs;
    }
        
    return $result;
}
?>